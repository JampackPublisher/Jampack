<?php
/**
 * LLM Adapter (Placeholder for Future Expansion)
 *
 * @package FAQ_Chatbot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ LLM Adapter Class.
 */
class FAQ_LLM_Adapter {
	
	/**
	 * Instance of this class
	 *
	 * @var FAQ_LLM_Adapter
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return FAQ_LLM_Adapter
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
		// No hooks needed - called directly
	}

	/**
	 * Whether fallback is enabled and configured.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$settings = get_option( 'faq_chatbot_settings', array() );
		$enabled  = ! empty( $settings['enable_claude_fallback'] );
		$api_key  = isset( $settings['claude_api_key'] ) ? trim( (string) $settings['claude_api_key'] ) : '';

		return $enabled && '' !== $api_key;
	}
	
	/**
	 * Get Claude fallback response.
	 *
	 * @param string $query User query.
	 * @param array  $context_faqs Context FAQ list.
	 * @return string|null
	 */
	public function get_llm_response( $query, $context_faqs = array() ) {
		if ( ! $this->is_enabled() ) {
			return null;
		}

		$settings = get_option( 'faq_chatbot_settings', array() );
		$api_key  = isset( $settings['claude_api_key'] ) ? trim( (string) $settings['claude_api_key'] ) : '';
		if ( '' === $api_key ) {
			return null;
		}

		$context = $this->prepare_context( $context_faqs );
		return $this->call_llm_api( $api_key, $query, $context );
	}
	
	/**
	 * Build prompt context from FAQ entries.
	 *
	 * @param array $faq_posts FAQ arrays.
	 * @return string
	 */
	private function prepare_context( $faq_posts ) {
		$context = '';
		
		foreach ( $faq_posts as $faq_post ) {
			if ( is_array( $faq_post ) ) {
				$question = isset( $faq_post['question'] ) ? wp_strip_all_tags( (string) $faq_post['question'] ) : '';
				$answer   = isset( $faq_post['answer'] ) ? wp_strip_all_tags( (string) $faq_post['answer'] ) : '';
				if ( '' !== $question && '' !== $answer ) {
					$context .= 'Q: ' . $question . "\n";
					$context .= 'A: ' . $answer . "\n\n";
				}
			}
		}
		
		return $context;
	}
	
	/**
	 * Call Claude API.
	 *
	 * @param string $api_key API key.
	 * @param string $query Query text.
	 * @param string $context FAQ context.
	 * @return string|null
	 */
	private function call_llm_api( $api_key, $query, $context ) {
		$endpoint = apply_filters( 'faq_chatbot_claude_endpoint', 'https://api.anthropic.com/v1/messages' );
		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 10,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => 'claude-3-5-haiku-latest',
						'max_tokens' => 300,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $this->build_prompt( $query, $context ),
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['content'][0]['text'] ) ) {
			return null;
		}

		return wp_kses_post( $body['content'][0]['text'] );
	}
	
	/**
	 * Build fallback prompt.
	 *
	 * @param string $query User query.
	 * @param string $context Context FAQ text.
	 * @return string
	 */
	private function build_prompt( $query, $context ) {
		$prompt = "You are a support assistant for Jampack.\n";
		$prompt .= "Answer succinctly and avoid inventing unavailable policies.\n\n";

		if ( '' !== trim( $context ) ) {
			$prompt .= "Known FAQ context:\n" . $context . "\n";
		}

		$prompt .= 'User question: ' . $query . "\n";
		$prompt .= 'Answer:';
		
		return $prompt;
	}
}
