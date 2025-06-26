<?php
require_once get_stylesheet_directory() . '/memberpress/jampack-memberpress.php';

/**
 * Autoload Jampack MemberPress controllers in the theme.
 */
spl_autoload_register('autoload_jampack_memberpress_controllers');

/**
 * Enqueue styles for the MemberPress account page in the Jampack theme.
 * This function checks if the user is logged in and if the current page is a MemberPress account page.
 * If so, it enqueues the styles from the specified path.
 */
function jampack_account_memberpress_styles(){
	$current_post = get_post();
	if (is_user_logged_in() && class_exists('MeprUser') && MeprUser::is_account_page($current_post)) {
		$handle = 'account-memberpress-styles';
		$relative_path = '/assets/css/account-memberpress.css';
		$file_path = get_stylesheet_directory() . $relative_path;
		$file_url = get_stylesheet_directory_uri() . $relative_path;
		if (file_exists($file_path)) {
			wp_enqueue_style(
				$handle,
				$file_url,
				array(),
				filemtime($file_path)
			);
		} else {
			error_log("(account_memberpress_styles) File NOT found: $file_path");
		}
	}
}

/**
 * This is a workaround to load the styles in the footer.
 * We need to load styles in the footer because memberpress add or modify the styles dinamically
 * Check theme_style() from MeprReadyLaunchCtrl.php 
 */
// add_action('wp_enqueue_scripts', 'jampack_account_memberpress_styles'); 
add_action('wp_footer', 'jampack_account_memberpress_styles');

/**
 * Add a custom action to the MemberPress account navigation.
 */
add_action('mepr_account_nav_content', function ($action, $atts) {
    if ($action === 'statistics') {
        $jampack_account = MeprCtrlFactory::fetch('JampackAccount');
        $jampack_account->statistics();
    }
}, 10, 2);

/**
 * Register REST API routes for the Jampack account.
 * This function registers the routes for the Jampack account controller.
 */
add_action('rest_api_init', function () {
    $jampack_account = MeprCtrlFactory::fetch('JampackAccount');
	$jampack_account->register_routes();
});

/**
 * Enqueue scripts for the MemberPress account analytics page.
 * This function enqueues the analytics script and localizes it with the REST API URL and nonce.
 */
function analytics_enqueue_scripts(){
	if (is_page('account')) {
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
		if ($action == 'statistics') {
			wp_enqueue_script('jampack-analytics-script', get_stylesheet_directory_uri() . '/assets/js/jpck-analytics.js', ['jquery'], null, true);
			wp_localize_script('jampack-analytics-script', 'AnalyticsData', [
				'restUrl' => esc_url_raw(rest_url(MeprCtrlFactory::fetch('JampackAccount')->analitycs_rest_route())),
				'nonce'   => wp_create_nonce('wp_rest')
			]);
		}
	}
}

// Hook to enqueue the analytics scripts
add_action('wp_enqueue_scripts', 'analytics_enqueue_scripts');
