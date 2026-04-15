<?php
/**
 * Frontend Widget Renderer
 *
 * @package FAQ_Chatbot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ Widget Class
 */
class FAQ_Widget {
	
	/**
	 * Instance of this class
	 *
	 * @var FAQ_Widget
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return FAQ_Widget
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'render_widget' ) );
	}
	
	/**
	 * Check if widget should be displayed on current page
	 *
	 * @return bool
	 */
	private function should_display_widget() {
		$settings = get_option( 'faq_chatbot_settings', array() );
		$allowed_pages = isset( $settings['allowed_pages'] ) ? $settings['allowed_pages'] : array();
		
		if ( empty( $allowed_pages ) ) {
			return false;
		}
		
		// Check if current page ID is in allowed pages
		$current_page_id = get_queried_object_id();
		
		if ( in_array( $current_page_id, $allowed_pages, true ) ) {
			return true;
		}
		
		// Also check by slug for flexibility
		$current_page = get_queried_object();
		if ( $current_page && isset( $current_page->post_name ) ) {
			foreach ( $allowed_pages as $page_id ) {
				$page = get_post( $page_id );
				if ( $page && $page->post_name === $current_page->post_name ) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		if ( ! $this->should_display_widget() ) {
			return;
		}
		
		// Enqueue CSS
		wp_enqueue_style(
			'faq-chatbot-css',
			FAQ_CHATBOT_PLUGIN_URL . 'assets/css/chatbot.css',
			array(),
			FAQ_CHATBOT_VERSION
		);
		
		// Enqueue JS
		wp_enqueue_script(
			'faq-chatbot-js',
			FAQ_CHATBOT_PLUGIN_URL . 'assets/js/chatbot.js',
			array(),
			FAQ_CHATBOT_VERSION,
			true
		);
		
		// Localize script with AJAX URL and nonce
		wp_localize_script(
			'faq-chatbot-js',
			'faqChatbot',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'faq_chatbot_query' ),
				'errorMessage' => __( 'An error occurred. Please try again.', 'faq-chatbot' ),
			)
		);
	}
	
	/**
	 * Render widget HTML
	 */
	public function render_widget() {
		if ( ! $this->should_display_widget() ) {
			return;
		}

		$template = FAQ_CHATBOT_PLUGIN_DIR . 'templates/chat-widget.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
