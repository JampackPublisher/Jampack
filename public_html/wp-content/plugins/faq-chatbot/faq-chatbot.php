<?php
/**
 * Plugin Name: FAQ Chatbot
 * Plugin URI: https://jampack.org
 * Description: A floating chat widget that uses FAQ content with deterministic key-phrase matching.
 * Version: 1.0.0
 * Author: Jampack
 * Author URI: https://jampack.org
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: faq-chatbot
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'FAQ_CHATBOT_VERSION', '1.0.0' );
define( 'FAQ_CHATBOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FAQ_CHATBOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class FAQ_Chatbot {
	
	/**
	 * Instance of this class
	 *
	 * @var FAQ_Chatbot
	 */
	private static $instance = null;
	
	/**
	 * Get instance of this class
	 *
	 * @return FAQ_Chatbot
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
		$this->load_dependencies();
		$this->init_hooks();
	}
	
	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-repository.php';
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-matcher.php';
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-query-guard.php';
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-ajax.php';
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-settings.php';
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-widget.php';
		require_once FAQ_CHATBOT_PLUGIN_DIR . 'includes/class-faq-llm-adapter.php';
	}
	
	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		// Initialize components
		add_action( 'init', array( $this, 'init' ) );
	}
	
	/**
	 * Initialize plugin components
	 */
	public function init() {
		// Initialize Settings
		FAQ_Settings::get_instance();
		
		// Initialize AJAX handlers
		FAQ_Ajax::get_instance();
		
		// Initialize Widget (frontend)
		if ( ! is_admin() ) {
			FAQ_Widget::get_instance();
		}
	}
	
	/**
	 * Plugin activation
	 */
	public function activate() {
		// Set default settings
		$default_settings = array(
			'allowed_pages' => array(),
			'match_threshold' => 1,
			'enable_claude_fallback' => 0,
			'claude_api_key' => '',
			'claude_max_per_hour' => 5,
			'claude_max_per_day' => 20,
			'topic_allowlist_extra' => '',
			'fallback_message' => 'I\'m sorry, I couldn\'t find a matching answer. Please try rephrasing your question or contact support for assistance.',
		);
		
		if ( ! get_option( 'faq_chatbot_settings' ) ) {
			add_option( 'faq_chatbot_settings', $default_settings );
		} else {
			$existing = get_option( 'faq_chatbot_settings', array() );
			update_option( 'faq_chatbot_settings', wp_parse_args( $existing, $default_settings ) );
		}
	}
	
	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// No deactivation actions required.
	}
}

/**
 * Initialize the plugin
 */
function faq_chatbot_init() {
	return FAQ_Chatbot::get_instance();
}

// Start the plugin
faq_chatbot_init();
