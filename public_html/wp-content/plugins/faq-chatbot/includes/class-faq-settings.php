<?php
/**
 * Settings Page
 *
 * @package FAQ_Chatbot
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQ Settings Class
 */
class FAQ_Settings {
	
	/**
	 * Instance of this class
	 *
	 * @var FAQ_Settings
	 */
	private static $instance = null;
	
	/**
	 * Option name
	 *
	 * @var string
	 */
	private $option_name = 'faq_chatbot_settings';
	
	/**
	 * Get instance of this class
	 *
	 * @return FAQ_Settings
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}
	
	/**
	 * Add settings page
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'FAQ Chatbot Settings', 'faq-chatbot' ),
			__( 'FAQ Chatbot', 'faq-chatbot' ),
			'manage_options',
			'faq-chatbot-settings',
			array( $this, 'render_settings_page' )
		);
	}
	
	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'faq_chatbot_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
		
		add_settings_section(
			'faq_chatbot_general_section',
			__( 'General Settings', 'faq-chatbot' ),
			array( $this, 'render_section_description' ),
			'faq-chatbot-settings'
		);
		
		add_settings_field(
			'allowed_pages',
			__( 'Allowed Pages', 'faq-chatbot' ),
			array( $this, 'render_allowed_pages_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);
		
		add_settings_field(
			'match_threshold',
			__( 'Match Threshold', 'faq-chatbot' ),
			array( $this, 'render_match_threshold_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);
		
		add_settings_field(
			'fallback_message',
			__( 'Fallback Message', 'faq-chatbot' ),
			array( $this, 'render_fallback_message_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);

		add_settings_field(
			'enable_claude_fallback',
			__( 'Enable Claude Fallback', 'faq-chatbot' ),
			array( $this, 'render_enable_claude_fallback_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);

		add_settings_field(
			'claude_api_key',
			__( 'Claude API Key', 'faq-chatbot' ),
			array( $this, 'render_claude_api_key_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);

		add_settings_field(
			'claude_max_per_hour',
			__( 'Claude max calls per IP (hour)', 'faq-chatbot' ),
			array( $this, 'render_claude_max_per_hour_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);

		add_settings_field(
			'claude_max_per_day',
			__( 'Claude max calls per IP (day)', 'faq-chatbot' ),
			array( $this, 'render_claude_max_per_day_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);

		add_settings_field(
			'topic_allowlist_extra',
			__( 'Extra topic allowlist (one phrase per line)', 'faq-chatbot' ),
			array( $this, 'render_topic_allowlist_extra_field' ),
			'faq-chatbot-settings',
			'faq_chatbot_general_section'
		);
	}
	
	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input
	 * @return array Sanitized settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();
		
		// Sanitize allowed pages
		if ( isset( $input['allowed_pages'] ) && is_array( $input['allowed_pages'] ) ) {
			$sanitized['allowed_pages'] = array_map( 'intval', $input['allowed_pages'] );
		} else {
			$sanitized['allowed_pages'] = array();
		}
		
		// Sanitize match threshold
		if ( isset( $input['match_threshold'] ) ) {
			$sanitized['match_threshold'] = max( 0, intval( $input['match_threshold'] ) );
		} else {
			$sanitized['match_threshold'] = 1;
		}

		$sanitized['enable_claude_fallback'] = ! empty( $input['enable_claude_fallback'] ) ? 1 : 0;

		if ( isset( $input['claude_api_key'] ) ) {
			$sanitized['claude_api_key'] = sanitize_text_field( $input['claude_api_key'] );
		} else {
			$existing = get_option( $this->option_name, array() );
			$sanitized['claude_api_key'] = isset( $existing['claude_api_key'] ) ? sanitize_text_field( $existing['claude_api_key'] ) : '';
		}

		$existing = get_option( $this->option_name, array() );
		if ( isset( $input['claude_max_per_hour'] ) ) {
			$sanitized['claude_max_per_hour'] = max( 1, min( 100, intval( $input['claude_max_per_hour'] ) ) );
		} else {
			$sanitized['claude_max_per_hour'] = isset( $existing['claude_max_per_hour'] ) ? max( 1, min( 100, intval( $existing['claude_max_per_hour'] ) ) ) : 5;
		}
		if ( isset( $input['claude_max_per_day'] ) ) {
			$sanitized['claude_max_per_day'] = max( 1, min( 500, intval( $input['claude_max_per_day'] ) ) );
		} else {
			$sanitized['claude_max_per_day'] = isset( $existing['claude_max_per_day'] ) ? max( 1, min( 500, intval( $existing['claude_max_per_day'] ) ) ) : 20;
		}
		if ( isset( $input['topic_allowlist_extra'] ) ) {
			$sanitized['topic_allowlist_extra'] = sanitize_textarea_field( $input['topic_allowlist_extra'] );
		} else {
			$sanitized['topic_allowlist_extra'] = isset( $existing['topic_allowlist_extra'] ) ? sanitize_textarea_field( (string) $existing['topic_allowlist_extra'] ) : '';
		}
		
		// Sanitize fallback message
		if ( isset( $input['fallback_message'] ) ) {
			$sanitized['fallback_message'] = wp_kses_post( $input['fallback_message'] );
		} else {
			$sanitized['fallback_message'] = __( 'I\'m sorry, I couldn\'t find a matching answer. Please try rephrasing your question or contact support for assistance.', 'faq-chatbot' );
		}
		
		return $sanitized;
	}
	
	/**
	 * Render section description
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure the FAQ Chatbot settings below.', 'faq-chatbot' ) . '</p>';
		echo '<p><code>' . esc_html( FAQ_CHATBOT_PLUGIN_DIR . 'data/faqs.json' ) . '</code> ';
		echo esc_html__( 'is the primary FAQ source. Each item must include id, question, answer, and phrases[] (multi-word key phrases users might type).', 'faq-chatbot' ) . '</p>';
	}
	
	/**
	 * Render allowed pages field
	 */
	public function render_allowed_pages_field() {
		$settings = get_option( $this->option_name, array() );
		$allowed_pages = isset( $settings['allowed_pages'] ) ? $settings['allowed_pages'] : array();
		
		// Get all pages
		$pages = get_pages( array(
			'sort_column' => 'post_title',
			'sort_order'  => 'ASC',
		) );
		
		if ( empty( $pages ) ) {
			echo '<p>' . esc_html__( 'No pages found.', 'faq-chatbot' ) . '</p>';
			return;
		}
		
		echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">';
		
		foreach ( $pages as $page ) {
			$checked = in_array( $page->ID, $allowed_pages, true ) ? 'checked="checked"' : '';
			printf(
				'<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="%s[allowed_pages][]" value="%d" %s> %s (ID: %d)</label>',
				esc_attr( $this->option_name ),
				esc_attr( $page->ID ),
				$checked,
				esc_html( $page->post_title ),
				esc_attr( $page->ID )
			);
		}
		
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Select the pages where the chat widget should appear.', 'faq-chatbot' ) . '</p>';
	}
	
	/**
	 * Render match threshold field
	 */
	public function render_match_threshold_field() {
		$settings = get_option( $this->option_name, array() );
		$threshold = isset( $settings['match_threshold'] ) ? intval( $settings['match_threshold'] ) : 1;
		
		printf(
			'<input type="number" name="%s[match_threshold]" value="%d" min="0" step="1" class="small-text">',
			esc_attr( $this->option_name ),
			esc_attr( $threshold )
		);
		
		echo '<p class="description">' . esc_html__( 'Minimum phrase-match score required to return an FAQ answer (higher = stricter). Phrases must be at least two words. Set to 0 to allow the best-scoring FAQ even if low.', 'faq-chatbot' ) . '</p>';
	}
	
	/**
	 * Render fallback message field
	 */
	public function render_fallback_message_field() {
		$settings = get_option( $this->option_name, array() );
		$fallback_message = isset( $settings['fallback_message'] ) ? $settings['fallback_message'] : __( 'I\'m sorry, I couldn\'t find a matching answer. Please try rephrasing your question or contact support for assistance.', 'faq-chatbot' );
		
		printf(
			'<textarea name="%s[fallback_message]" rows="4" cols="50" class="large-text">%s</textarea>',
			esc_attr( $this->option_name ),
			esc_textarea( $fallback_message )
		);
		
		echo '<p class="description">' . esc_html__( 'Message displayed when no matching FAQ is found.', 'faq-chatbot' ) . '</p>';
	}

	/**
	 * Render Claude fallback toggle.
	 */
	public function render_enable_claude_fallback_field() {
		$settings = get_option( $this->option_name, array() );
		$enabled  = ! empty( $settings['enable_claude_fallback'] );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_claude_fallback]" value="1" <?php checked( $enabled ); ?> />
			<?php esc_html_e( 'Enable Claude API fallback when deterministic FAQ matching fails.', 'faq-chatbot' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Disabled by default. Keep disabled unless you have configured an API key.', 'faq-chatbot' ); ?></p>
		<?php
	}

	/**
	 * Render Claude API key field.
	 */
	public function render_claude_api_key_field() {
		$settings = get_option( $this->option_name, array() );
		$api_key  = isset( $settings['claude_api_key'] ) ? (string) $settings['claude_api_key'] : '';

		printf(
			'<input type="password" name="%s[claude_api_key]" value="%s" class="regular-text" autocomplete="new-password" />',
			esc_attr( $this->option_name ),
			esc_attr( $api_key )
		);

		echo '<p class="description">' . esc_html__( 'Stored in WordPress options. Leave empty to keep deterministic-only behavior.', 'faq-chatbot' ) . '</p>';
	}

	/**
	 * Max Claude calls per IP per rolling hour bucket.
	 */
	public function render_claude_max_per_hour_field() {
		$settings = get_option( $this->option_name, array() );
		$value    = isset( $settings['claude_max_per_hour'] ) ? intval( $settings['claude_max_per_hour'] ) : 5;
		printf(
			'<input type="number" name="%s[claude_max_per_hour]" value="%d" min="1" max="100" step="1" class="small-text" />',
			esc_attr( $this->option_name ),
			$value
		);
		echo '<p class="description">' . esc_html__( 'After deterministic FAQ matching fails, each visitor IP is limited before the Claude API is called. Lower values reduce API cost.', 'faq-chatbot' ) . '</p>';
	}

	/**
	 * Max Claude calls per IP per site calendar day.
	 */
	public function render_claude_max_per_day_field() {
		$settings = get_option( $this->option_name, array() );
		$value    = isset( $settings['claude_max_per_day'] ) ? intval( $settings['claude_max_per_day'] ) : 20;
		printf(
			'<input type="number" name="%s[claude_max_per_day]" value="%d" min="1" max="500" step="1" class="small-text" />',
			esc_attr( $this->option_name ),
			$value
		);
		echo '<p class="description">' . esc_html__( 'Hard cap per IP per day for Claude calls (rolling day bucket by site timezone).', 'faq-chatbot' ) . '</p>';
	}

	/**
	 * Optional extra substrings that must match (any line) for a query to be considered on-topic for Claude.
	 */
	public function render_topic_allowlist_extra_field() {
		$settings = get_option( $this->option_name, array() );
		$extra    = isset( $settings['topic_allowlist_extra'] ) ? (string) $settings['topic_allowlist_extra'] : '';
		printf(
			'<textarea name="%s[topic_allowlist_extra]" rows="5" class="large-text" placeholder="%s">%s</textarea>',
			esc_attr( $this->option_name ),
			esc_attr__( 'e.g. mighty pack', 'faq-chatbot' ),
			esc_textarea( $extra )
		);
		echo '<p class="description">' . esc_html__( 'Phrases are matched case-insensitively as substrings. The built-in list already includes common Jampack terms; add lines here to broaden without editing code.', 'faq-chatbot' ) . '</p>';
	}
	
	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'faq_chatbot_settings_group' );
				do_settings_sections( 'faq-chatbot-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Get settings
	 *
	 * @return array Settings array
	 */
	public function get_settings() {
		return get_option( $this->option_name, array() );
	}
}
