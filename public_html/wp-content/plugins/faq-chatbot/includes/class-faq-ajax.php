<?php
/**
 * AJAX Handlers
 *
 * @package FAQ_Chatbot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ Ajax Class
 */
class FAQ_Ajax {
	
	/**
	 * Instance of this class
	 *
	 * @var FAQ_Ajax
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return FAQ_Ajax
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
		add_action( 'wp_ajax_faq_chatbot_query', array( $this, 'handle_query' ) );
		add_action( 'wp_ajax_nopriv_faq_chatbot_query', array( $this, 'handle_query' ) );
	}
	
	/**
	 * Handle AJAX query
	 */
	public function handle_query() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'faq_chatbot_query' ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'faq-chatbot' ),
			) );
			return;
		}
		
		// Sanitize input
		if ( ! isset( $_POST['query'] ) || empty( $_POST['query'] ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please enter a question.', 'faq-chatbot' ),
			) );
			return;
		}
		
		$query = sanitize_text_field( wp_unslash( $_POST['query'] ) );
		
		if ( empty( trim( $query ) ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please enter a question.', 'faq-chatbot' ),
			) );
			return;
		}

		if ( strlen( $query ) > 500 ) {
			wp_send_json_error( array(
				'message' => __( 'Your question is too long. Please keep it under 500 characters.', 'faq-chatbot' ),
			) );
			return;
		}
		
		// Get settings
		$settings = get_option( 'faq_chatbot_settings', array() );
		$threshold = isset( $settings['match_threshold'] ) ? intval( $settings['match_threshold'] ) : 1;
		$fallback_message = isset( $settings['fallback_message'] ) ? $settings['fallback_message'] : __( 'I\'m sorry, I couldn\'t find a matching answer. Please try rephrasing your question or contact support for assistance.', 'faq-chatbot' );
		
		// Get matcher instance
		$matcher = FAQ_Matcher::get_instance();
		
		// Get answer
		$result = $matcher->get_answer( $query, $threshold );
		
		if ( ! empty( $result['matched'] ) && ! empty( $result['answer'] ) ) {
			wp_send_json_success( array(
				'answer'   => wp_kses_post( $result['answer'] ),
				'fallback' => false,
				'source'   => 'faq',
				'faq_id'   => sanitize_key( $result['faq_id'] ),
				'score'    => intval( $result['score'] ),
			) );
			return;
		}

		$adapter = FAQ_LLM_Adapter::get_instance();
		if ( ! $adapter->is_enabled() ) {
			wp_send_json_success( array(
				'answer'   => wp_kses_post( $fallback_message ),
				'fallback' => true,
				'source'   => 'fallback',
			) );
			return;
		}

		$debug_bypass_guards = (bool) apply_filters( 'faq_chatbot_debug_bypass_guards', false, $query, $settings );
		if ( ! $debug_bypass_guards ) {
			$gate = FAQ_Query_Guard::validate_query( $query, $settings );
			if ( empty( $gate['ok'] ) ) {
				wp_send_json_success( array(
					'answer'   => wp_kses_post( FAQ_Query_Guard::get_minimal_refusal_message() ),
					'fallback' => true,
					'source'   => 'gate',
				) );
				return;
			}

			$ip = FAQ_Query_Guard::get_client_ip();
			if ( ! FAQ_Query_Guard::try_consume_llm_slot( $ip, $settings ) ) {
				wp_send_json_success( array(
					'answer'   => wp_kses_post( FAQ_Query_Guard::get_minimal_refusal_message() ),
					'fallback' => true,
					'source'   => 'rate_limit',
				) );
				return;
			}
		}

		$context_faqs = FAQ_Repository::get_instance()->get_faqs();
		$llm_response = $adapter->get_llm_response( $query, $context_faqs );
		if ( ! empty( $llm_response ) ) {
			wp_send_json_success( array(
				'answer'   => wp_kses_post( $llm_response ),
				'fallback' => true,
				'source'   => 'claude',
			) );
			return;
		}

		wp_send_json_success( array(
			'answer'   => wp_kses_post( $fallback_message ),
			'fallback' => true,
			'source'   => 'fallback',
		) );
	}
}
