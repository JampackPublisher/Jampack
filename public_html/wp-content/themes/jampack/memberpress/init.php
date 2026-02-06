<?php

/**
 * Jampack MemberPress Integration
 *
 * This file is part of the Jampack for WordPress, which integrates with MemberPress.
 * It sets up paths and autoloads controllers for MemberPress functionality within Jampack.
 *
 * @package Jampack
 * @subpackage MemberPress
 * @since 1.0.0
 */

include_once ABSPATH . 'wp-admin/includes/plugin.php';

if(!is_plugin_active('memberpress/memberpress.php') || !class_exists('MeprCtrlFactory')){
	error_log('MemberPress is not active or MeprCtrlFactory class does not exist.' . __FILE__);
	return;
}

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
	//$current_post = get_post();
	if (is_page('account')) {
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

/**
 * Custom redirect handler to replace MemberPress's default behavior.
 * It allows access to the admin panel depending on the role.
 */
add_action('init', function() {
    // Remove MemberPress behavior that redirects users from admin
    remove_action('admin_init', 'MeprUsersCtrl::maybe_redirect_member_from_admin');

	$jampack_account = MeprCtrlFactory::fetch('JampackAccount');
	// IMPORTANT: Add the roles you want to allow access to the admin panel // TODO: Store in a config option
	$roles = [$jampack_account->judge_role_to_string()]; 

    add_action('admin_init', function() use ($roles) {
        maybe_redirect_member_from_admin_jampack($roles);
    });
});

/**
 * [TEMPORAL WORKAROUND]
 * Temporal callback to adjust subscription expiration dates.
 */
add_action('mepr-txn-status-confirmed', function($txn) {
	$grace_period = MeprUtils::db_date_to_ts($txn->created_at) + MeprUtils::days(2);
	$expires_at = MeprUtils::db_date_to_ts($txn->expires_at);
	if($txn->txn_type == 'subscription_confirmation' && $expires_at < $grace_period) {
		$txn->expires_at = MeprUtils::ts_to_mysql_date(time() + MeprUtils::days(30), 'Y-m-d 23:59:59');
		$txn->store();
	}
});

/**
 * Force single session on user login by retaining only the latest session token
 */
add_action('wp_login', 'force_single_session_on_login', 10, 2);

/**
 * Enqueue Toastify for notifications
 */
add_action('wp_enqueue_scripts', 'add_toastify');

/**
 * Show message if user was logged out due to single session enforcement
 */
add_action('wp_footer', 'force_single_session_message_new_login');

/**
 * Kick other sessions after a new session is created
 */
add_action('template_redirect', 'kick_session');

/**
 * Show message if user was kicked from other sessions
 */
add_action('wp_footer', 'show_kicked_session_message');