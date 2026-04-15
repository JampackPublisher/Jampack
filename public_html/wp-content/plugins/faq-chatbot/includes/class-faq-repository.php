<?php
/**
 * FAQ JSON repository.
 *
 * @package FAQ_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FAQ_Repository {

	/**
	 * Singleton instance.
	 *
	 * @var FAQ_Repository|null
	 */
	private static $instance = null;

	/**
	 * Cache group name.
	 *
	 * @var string
	 */
	private $cache_group = 'faq_chatbot';

	/**
	 * Get singleton instance.
	 *
	 * @return FAQ_Repository
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Return valid FAQ entries from JSON file.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_faqs() {
		$cache_key = $this->build_cache_key();
		$cached    = wp_cache_get( $cache_key, $this->cache_group );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$faqs = $this->load_faqs_from_file();
		wp_cache_set( $cache_key, $faqs, $this->cache_group, 5 * MINUTE_IN_SECONDS );

		return $faqs;
	}

	/**
	 * Read and validate JSON file content.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function load_faqs_from_file() {
		$file_path = FAQ_CHATBOT_PLUGIN_DIR . 'data/faqs.json';

		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return array();
		}

		$raw = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $raw || '' === trim( $raw ) ) {
			return array();
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$validated = array();
		foreach ( $decoded as $entry ) {
			$item = $this->validate_entry( $entry );
			if ( ! empty( $item ) ) {
				$validated[] = $item;
			}
		}

		return $validated;
	}

	/**
	 * Validate FAQ schema and sanitize fields.
	 *
	 * @param mixed $entry Raw decoded item.
	 * @return array<string, mixed>
	 */
	private function validate_entry( $entry ) {
		if ( ! is_array( $entry ) ) {
			return array();
		}

		$id       = isset( $entry['id'] ) ? sanitize_key( (string) $entry['id'] ) : '';
		$question = isset( $entry['question'] ) ? sanitize_text_field( (string) $entry['question'] ) : '';
		$answer   = isset( $entry['answer'] ) ? wp_kses_post( (string) $entry['answer'] ) : '';

		if ( '' === $id || '' === $question || '' === $answer ) {
			return array();
		}

		$keywords = array();
		if ( isset( $entry['keywords'] ) && is_array( $entry['keywords'] ) ) {
			foreach ( $entry['keywords'] as $keyword ) {
				$keyword = sanitize_text_field( (string) $keyword );
				if ( '' !== $keyword ) {
					$keywords[] = strtolower( $keyword );
				}
			}
		}

		if ( empty( $keywords ) ) {
			return array();
		}

		return array(
			'id'       => $id,
			'question' => $question,
			'answer'   => $answer,
			'keywords' => array_values( array_unique( $keywords ) ),
		);
	}

	/**
	 * Build cache key from file mtime.
	 *
	 * @return string
	 */
	private function build_cache_key() {
		$file_path = FAQ_CHATBOT_PLUGIN_DIR . 'data/faqs.json';
		$mtime     = file_exists( $file_path ) ? (int) filemtime( $file_path ) : 0;

		return 'faq_json_' . $mtime;
	}
}
