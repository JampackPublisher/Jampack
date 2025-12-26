<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

define('JMP_MEPR_PATH', get_stylesheet_directory() . '/memberpress');
define('JMP_MEPR_CTRLS_PATH', JMP_MEPR_PATH . '/controllers');
define('JMP_MEPR_READY_LAUNCH_PATH', JMP_MEPR_PATH . '/readylaunch');

/**
 * Autoload MemberPress controllers in the Jampack theme.
 * This function will look for all Mepr*Ctrl.php files in the JMP_MEPR_CTRLS_PATH directory
 * and load them using the MeprCtrlFactory.
 */
function autoload_jampack_memberpress_controllers() {
	$ctrls = @glob(JMP_MEPR_CTRLS_PATH . '/Mepr*Ctrl.php', GLOB_NOSORT);
	foreach ($ctrls as $ctrl) {
		require_once($ctrl);
		$class = preg_replace('#\.php#', '', basename($ctrl));
		MeprCtrlFactory::fetch($class, []);
	}
}

/**
 * Extended from MeprUsersCtrl::maybe_redirect_member_from_admin() Memberpress function
 * It decides whether a user has access to the admin paneel or not based on their role.
 */
function maybe_redirect_member_from_admin_jampack($roles = []) {
	$mepr_options = MeprOptions::fetch();

	// Don't mess up AJAX requests
	if (defined('DOING_AJAX')) {
		return;
	}

	// Don't mess up admin_post.php requests
	if (strpos($_SERVER['REQUEST_URI'], 'admin-post.php') !== false && isset($_REQUEST['action'])) {
		return;
	}

	$user = wp_get_current_user();

	if ($mepr_options->lock_wp_admin && !current_user_can('delete_posts') && !array_intersect($roles, (array) $user->roles)) {
		if (isset($mepr_options->login_redirect_url) && !empty($mepr_options->login_redirect_url)) {
			MeprUtils::wp_redirect($mepr_options->login_redirect_url);
		} else {
			MeprUtils::wp_redirect(home_url());
		}
	}
}

/**
 * Force single session on user login by retaining only the latest session token
 *
 * @param string $user_login
 * @param WP_User $user
 */
function force_single_session_on_login($user_login, $user)
{
	// Check if the user is logged in and valid
	if (is_a($user, 'WP_User')) {
		$user_id = $user->ID;
		// Retrieve the session tokens array from the user meta
		$sessions = get_user_meta($user_id, 'session_tokens', true);

		if ($sessions && is_array($sessions)) {
			// Keep only the last session (most recent login)
			$sessions = array_slice($sessions, -1);
			// Update the user meta with the new session array
			update_user_meta($user_id, 'session_tokens', $sessions);
		}
	}
}