<?php
/**
 * Pre-LLM query validation and per-IP rate limiting.
 *
 * @package FAQ_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ_Query_Guard
 */
class FAQ_Query_Guard {

	/**
	 * Cached baseline topic terms from data file.
	 *
	 * @var array<string>|null
	 */
	private static $baseline_topic_terms = null;

	/**
	 * Cached abuse needles from data file.
	 *
	 * @var array<string>|null
	 */
	private static $abuse_needles = null;

	/**
	 * Short message when the query is blocked (topic, shape, abuse) or rate-limited.
	 *
	 * @return string
	 */
	public static function get_minimal_refusal_message() {
		return __( 'I can only help with Jampack questions. Please ask something specific about Jampack.', 'faq-chatbot' );
	}

	/**
	 * Best-effort client IP for rate limiting.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip = '';
		if ( function_exists( 'rest_get_client_ip' ) ) {
			$ip = (string) rest_get_client_ip();
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = (string) wp_unslash( $_SERVER['REMOTE_ADDR'] );
		}

		$ip = trim( $ip );
		$ip = preg_replace( '/[^0-9a-f.:]/i', '', $ip );

		return $ip;
	}

	/**
	 * Validate query for on-topic Jampack intent, question shape, and cheap abuse heuristics.
	 *
	 * @param string $query Raw user query (already length-capped by caller).
	 * @param array  $settings Plugin settings (topic_allowlist_extra optional).
	 * @return array{ ok: bool, reason: string } reason is shape|abuse|ok
	 */
	public static function validate_query( $query, array $settings ) {
		$trimmed = trim( (string) $query );
		if ( '' === $trimmed ) {
			return array( 'ok' => false, 'reason' => 'shape' );
		}

		$lower = self::mb_lower( $trimmed );
		$compact = preg_replace( '/\s+/u', ' ', $lower );
		$compact = (string) $compact;

		if ( self::looks_abusive( $trimmed, $compact ) ) {
			return array( 'ok' => false, 'reason' => 'abuse' );
		}

		// Topic match is advisory here; Claude makes final scope decisions using approved topics.
		$has_topic_signal = self::has_topic_signal( $compact, $settings );

		if ( ! self::looks_like_question( $trimmed, $compact, $has_topic_signal ) ) {
			return array( 'ok' => false, 'reason' => 'shape' );
		}

		return array( 'ok' => true, 'reason' => 'ok' );
	}

	/**
	 * Whether the visitor may consume one LLM call (not in backoff, under caps). Increments counters on success.
	 *
	 * @param string $ip     Sanitized IP.
	 * @param array  $settings claude_max_per_hour, claude_max_per_day.
	 * @return bool
	 */
	public static function try_consume_llm_slot( $ip, array $settings ) {
		$hash = self::get_rate_limit_subject_hash( (string) $ip );
		if ( self::is_in_backoff( $hash ) ) {
			return false;
		}

		$max_hour = isset( $settings['claude_max_per_hour'] ) ? max( 1, min( 100, (int) $settings['claude_max_per_hour'] ) ) : 5;
		$max_day  = isset( $settings['claude_max_per_day'] ) ? max( 1, min( 500, (int) $settings['claude_max_per_day'] ) ) : 20;

		$hour_bucket = (string) (int) floor( current_time( 'timestamp' ) / HOUR_IN_SECONDS );
		$day_bucket  = current_time( 'Y-m-d' );

		$h_key = 'faq_cb_llm_h_' . $hash . '_' . $hour_bucket;
		$d_key = 'faq_cb_llm_d_' . $hash . '_' . $day_bucket;

		$h_count = (int) get_transient( $h_key );
		$d_count = (int) get_transient( $d_key );

		if ( $h_count >= $max_hour || $d_count >= $max_day ) {
			self::arm_backoff( $hash );
			return false;
		}

		$h_next = $h_count + 1;
		$d_next = $d_count + 1;

		set_transient( $h_key, $h_next, HOUR_IN_SECONDS );
		$day_ttl = max( 60, strtotime( 'tomorrow', current_time( 'timestamp' ) ) - current_time( 'timestamp' ) );
		set_transient( $d_key, $d_next, $day_ttl );

		return true;
	}

	/**
	 * Builds a stable hash input for rate limits.
	 *
	 * Falls back to lightweight request fingerprinting when an IP is not available
	 * (common behind some reverse-proxy setups).
	 *
	 * @param string $ip Sanitized IP if available.
	 * @return string md5 hash input.
	 */
	private static function get_rate_limit_subject_hash( $ip ) {
		$ip = trim( (string) $ip );
		if ( '' !== $ip ) {
			return md5( $ip );
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$language   = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$fingerprint = trim( $user_agent . '|' . $language );

		if ( '' === $fingerprint ) {
			$fingerprint = 'anonymous';
		}

		return md5( 'anon:' . $fingerprint );
	}

	/**
	 * @param string $hash md5(ip).
	 * @return bool
	 */
	private static function is_in_backoff( $hash ) {
		$key = 'faq_cb_llm_bo_' . $hash;
		return (bool) get_transient( $key );
	}

	/**
	 * @param string $hash md5(ip).
	 */
	private static function arm_backoff( $hash ) {
		set_transient( 'faq_cb_llm_bo_' . $hash, 1, 30 * MINUTE_IN_SECONDS );
	}

	/**
	 * @param string $text Text.
	 * @return string
	 */
	private static function mb_lower( $text ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $text, 'UTF-8' );
		}
		return strtolower( $text );
	}

	/**
	 * @param string $compact lower single-spaced.
	 * @param array  $settings Settings.
	 * @return bool
	 */
	private static function has_topic_signal( $compact, array $settings ) {
		$terms = self::get_baseline_topic_terms();

		$extra = isset( $settings['topic_allowlist_extra'] ) ? (string) $settings['topic_allowlist_extra'] : '';
		if ( '' !== $extra ) {
			$lines = preg_split( '/\r\n|\r|\n/', $extra );
			if ( is_array( $lines ) ) {
				foreach ( $lines as $line ) {
					$line = trim( self::mb_lower( sanitize_text_field( $line ) ) );
					if ( strlen( $line ) >= 2 ) {
						$terms[] = $line;
					}
				}
			}
		}

		$terms = apply_filters( 'faq_chatbot_topic_allowlist', $terms, $compact, $settings );
		if ( ! is_array( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			$term = trim( (string) $term );
			if ( '' === $term ) {
				continue;
			}
			$t = self::mb_lower( $term );
			if ( strlen( $t ) < 2 ) {
				continue;
			}
			if ( false !== strpos( $compact, $t ) ) {
				return true;
			}
		}

		return self::has_fuzzy_topic_signal( $compact, $terms );
	}

	/**
	 * @param string $trimmed Original trimmed query.
	 * @param string $compact Lower single-spaced.
	 * @return bool
	 */
	private static function looks_like_question( $trimmed, $compact, $has_topic_signal ) {
		$len = strlen( $trimmed );
		if ( $len < 3 ) {
			return false;
		}

		if ( $len > 0 ) {
			$chars = count_chars( $trimmed, 1 );
			$max_run = 0;
			foreach ( $chars as $count ) {
				if ( $count > $max_run ) {
					$max_run = $count;
				}
			}
			if ( $max_run > max( 8, (int) ( $len * 0.45 ) ) ) {
				return false;
			}
		}

		if ( false !== strpos( $trimmed, '?' ) ) {
			return true;
		}

		$lead = preg_replace( '/^\W+/u', '', $trimmed );
		$lead = is_string( $lead ) ? self::mb_lower( $lead ) : '';
		if ( preg_match( '/^(how|what|when|where|why|who|which|can|could|would|should|is|are|do|does|did|will|may)\b/u', $lead ) ) {
			return true;
		}

		if ( preg_match( '/^(help me understand|help me|tell me about|tell me|explain|i want to know|i need to know|can you explain|can you tell me)\b/u', $lead ) ) {
			return true;
		}

		// Allow short, topic-focused keyword searches such as "cancel subscription".
		if ( $has_topic_signal && preg_match( '/^[\p{L}\p{N}\s\'".,\-!?():\/&]+$/u', $trimmed ) ) {
			$tokens = preg_split( '/\s+/u', trim( $compact ) );
			if ( is_array( $tokens ) && count( array_filter( $tokens ) ) >= 2 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Allow minor typos for longer topic words (e.g. "jamapck" -> "jampack").
	 *
	 * @param string       $compact Normalized query.
	 * @param array<mixed> $terms   Topic terms.
	 * @return bool
	 */
	private static function has_fuzzy_topic_signal( $compact, array $terms ) {
		$query_tokens = preg_split( '/\s+/u', $compact );
		if ( ! is_array( $query_tokens ) || empty( $query_tokens ) ) {
			return false;
		}
		$query_tokens = array_values( array_unique( array_filter( array_map( 'trim', $query_tokens ) ) ) );

		$candidate_topic_words = array();
		foreach ( $terms as $term ) {
			$term_norm = self::mb_lower( trim( (string) $term ) );
			if ( '' === $term_norm ) {
				continue;
			}
			$parts = preg_split( '/\s+/u', $term_norm );
			if ( ! is_array( $parts ) ) {
				continue;
			}
			foreach ( $parts as $part ) {
				$part = trim( (string) $part );
				if ( strlen( $part ) >= 5 ) {
					$candidate_topic_words[] = $part;
				}
			}
		}
		$candidate_topic_words = array_values( array_unique( $candidate_topic_words ) );
		if ( empty( $candidate_topic_words ) ) {
			return false;
		}

		foreach ( $query_tokens as $q_token ) {
			$q_len = strlen( $q_token );
			if ( $q_len < 5 ) {
				continue;
			}
			foreach ( $candidate_topic_words as $t_word ) {
				$t_len = strlen( $t_word );
				if ( abs( $q_len - $t_len ) > 2 ) {
					continue;
				}
				$distance = levenshtein( $q_token, $t_word );
				$allowed  = ( $t_len <= 6 ) ? 1 : 2;
				if ( $distance <= $allowed ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $trimmed Original trimmed.
	 * @param string $compact Lower single-spaced.
	 * @return bool True if abusive / injection-like.
	 */
	private static function looks_abusive( $trimmed, $compact ) {
		$needles = apply_filters( 'faq_chatbot_abuse_needles', self::get_abuse_needles(), $trimmed, $compact );
		if ( ! is_array( $needles ) ) {
			$needles = self::get_abuse_needles();
		}

		foreach ( $needles as $n ) {
			if ( false !== stripos( $compact, $n ) ) {
				return true;
			}
		}

		$url_hits = preg_match_all( '#https?://#i', $trimmed );
		if ( $url_hits > 2 ) {
			return true;
		}

		$alnum = preg_match_all( '/[a-z0-9]/i', $trimmed );
		$total = strlen( $trimmed );
		if ( $total > 40 && $alnum < $total * 0.25 ) {
			return true;
		}

		return (bool) apply_filters( 'faq_chatbot_query_abusive', false, $trimmed, $compact );
	}

	/**
	 * Baseline topic terms shipped with the plugin.
	 *
	 * Source of truth:
	 * - data/claude-approved-topics.php (high-level topic labels)
	 *
	 * @return array<int, string>
	 */
	private static function get_baseline_topic_terms() {
		if ( null !== self::$baseline_topic_terms ) {
			return self::$baseline_topic_terms;
		}

		$terms = array();

		$topics_file = FAQ_CHATBOT_PLUGIN_DIR . 'data/claude-approved-topics.php';
		if ( is_readable( $topics_file ) ) {
			$loaded_topics = include $topics_file;
			if ( is_array( $loaded_topics ) ) {
				foreach ( $loaded_topics as $topic ) {
					$topic = trim( self::mb_lower( sanitize_text_field( (string) $topic ) ) );
					if ( strlen( $topic ) >= 2 ) {
						$terms[] = $topic;
					}
				}
			}
		}

		self::$baseline_topic_terms = array_values( array_unique( $terms ) );

		return self::$baseline_topic_terms;
	}

	/**
	 * Abuse / injection substring list (editable via data/query-guard-abuse-needles.php).
	 *
	 * @return array<int, string>
	 */
	private static function get_abuse_needles() {
		if ( null !== self::$abuse_needles ) {
			return self::$abuse_needles;
		}

		$file = FAQ_CHATBOT_PLUGIN_DIR . 'data/query-guard-abuse-needles.php';
		if ( is_readable( $file ) ) {
			$loaded = include $file;
			self::$abuse_needles = is_array( $loaded ) ? array_values( $loaded ) : array();
		} else {
			self::$abuse_needles = array();
		}

		return self::$abuse_needles;
	}
}
