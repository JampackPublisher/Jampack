<?php
/**
 * FAQ Matching Engine
 *
 * @package FAQ_Chatbot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ Matcher Class
 */
class FAQ_Matcher {
	
	/**
	 * Instance of this class
	 *
	 * @var FAQ_Matcher
	 */
	private static $instance = null;
	
	/**
	 * FAQ repository.
	 *
	 * @var FAQ_Repository
	 */
	private $repository;
	
	/**
	 * Get instance of this class
	 *
	 * @return FAQ_Matcher
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor
	 */
	private function __construct() {
		$this->repository = FAQ_Repository::get_instance();
	}
	
	/**
	 * Normalize input text
	 *
	 * @param string $text Input text
	 * @return string Normalized text
	 */
	public function normalize_input( $text ) {
		$text = strtolower( (string) $text );
		$text = preg_replace( '/[^\w\s]/u', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );

		return trim( $text );
	}

	/**
	 * Match a query against JSON FAQs.
	 *
	 * @param string $query User question.
	 * @param int    $threshold Minimum weighted score to count as match.
	 * @return array<string, mixed>
	 */
	public function get_answer( $query, $threshold = 1 ) {
		$normalized_query = $this->normalize_input( $query );
		$tokens           = $this->tokenize( $normalized_query );

		if ( empty( $normalized_query ) || empty( $tokens ) ) {
			return $this->empty_result();
		}

		$cache_key = 'faq_match_' . md5( $normalized_query . '|' . (int) $threshold );
		$cached    = wp_cache_get( $cache_key, 'faq_chatbot' );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$faqs = $this->repository->get_faqs();
		if ( empty( $faqs ) ) {
			return $this->empty_result();
		}

		// Exact question match short-circuit.
		foreach ( $faqs as $faq ) {
			if ( $this->normalize_input( $faq['question'] ) === $normalized_query ) {
				$exact = array(
					'matched'  => true,
					'faq_id'   => $faq['id'],
					'score'    => 100,
					'answer'   => $faq['answer'],
					'fallback' => false,
				);
				wp_cache_set( $cache_key, $exact, 'faq_chatbot', 5 * MINUTE_IN_SECONDS );
				return $exact;
			}
		}

		$best_item  = null;
		$best_score = 0;

		foreach ( $faqs as $faq ) {
			$score = $this->score_faq( $faq, $normalized_query, $tokens );
			if ( $score > $best_score ) {
				$best_score = $score;
				$best_item  = $faq;
			}
		}

		if ( null === $best_item || $best_score < (int) $threshold ) {
			$result = $this->empty_result();
			wp_cache_set( $cache_key, $result, 'faq_chatbot', MINUTE_IN_SECONDS );
			return $result;
		}

		$result = array(
			'matched'  => true,
			'faq_id'   => $best_item['id'],
			'score'    => $best_score,
			'answer'   => $best_item['answer'],
			'fallback' => false,
		);

		wp_cache_set( $cache_key, $result, 'faq_chatbot', 5 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Tokenize normalized query text.
	 *
	 * @param string $normalized_query Normalized query.
	 * @return array<int, string>
	 */
	private function tokenize( $normalized_query ) {
		$parts = explode( ' ', $normalized_query );
		$parts = array_map( 'trim', $parts );
		$parts = array_filter( $parts );

		return array_values( array_unique( $parts ) );
	}

	/**
	 * Score a FAQ entry based on keyword matches.
	 *
	 * @param array<string, mixed> $faq FAQ item.
	 * @param string               $normalized_query Normalized user query.
	 * @param array<int, string>   $tokens Tokenized query terms.
	 * @return int
	 */
	private function score_faq( $faq, $normalized_query, $tokens ) {
		$score = 0;

		foreach ( $faq['keywords'] as $keyword ) {
			$keyword_norm = $this->normalize_input( $keyword );
			if ( '' === $keyword_norm ) {
				continue;
			}

			if ( false !== strpos( $normalized_query, $keyword_norm ) ) {
				$score += 3;
				continue;
			}

			if ( in_array( $keyword_norm, $tokens, true ) ) {
				$score += 2;
				continue;
			}

			$keyword_tokens = $this->tokenize( $keyword_norm );
			$overlap        = array_intersect( $tokens, $keyword_tokens );
			if ( ! empty( $overlap ) ) {
				$score += 1;
			}
		}

		return $score;
	}

	/**
	 * Empty fallback result payload.
	 *
	 * @return array<string, mixed>
	 */
	private function empty_result() {
		return array(
			'matched'  => false,
			'faq_id'   => '',
			'score'    => 0,
			'answer'   => '',
			'fallback' => true,
		);
	}
}
