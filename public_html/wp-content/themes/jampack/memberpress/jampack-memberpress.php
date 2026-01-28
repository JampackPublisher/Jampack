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
		$manager  = WP_Session_Tokens::get_instance( $user_id );
		$sessions = $manager->get_all();
		if ($sessions && is_array($sessions) && count($sessions) > 1) {
			// Flag to show message
			update_user_meta($user_id, 'force_single_session_message_new_login', true);
		}
	}
}

/**
 * Enqueue Toastify for notifications
 */
function add_toastify()
{
	wp_enqueue_style(
		'toastify-css',
		'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css'
	);

	wp_enqueue_script(
		'toastify-js',
		'https://cdn.jsdelivr.net/npm/toastify-js',
		array(),
		null,
		true
	);
}

/**
 * Show message if user was logged out due to single session enforcement
 */
function force_single_session_message_new_login()
{
	$user_id = get_current_user_id();
	if (get_user_meta($user_id, 'force_single_session_message_new_login', true)) {
		// TODO: localize the message and create a toas manager
		$js = "
		Toastify({
			text: 'You are now logged in. All other active sessions for your account will be closed.',
			duration: 8000,
			gravity: 'bottom',
			position: 'right',
			close: true,
			style: {
				background: '#fe6a48',
			},
		}).showToast();
	";
		wp_add_inline_script('toastify-js', $js);
		delete_user_meta($user_id, 'force_single_session_message_new_login');
	}
}

/**
 * Kick other sessions after a new session is created
 */
function kick_session()
{
	$user_id = get_current_user_id();
	$manager = WP_Session_Tokens::get_instance($user_id);
	$sessions = $manager->get_all();
	if (count($sessions) <= 1) {
		return;
	}

	$last_session = null;
    $last_login = 0;

	foreach ($sessions as $id => $data) {
		if ($data['login'] > $last_login) {
			$last_login = $data['login'];
			$last_session = $data;
		}
	}

	$current_session_token = wp_get_session_token();
	$current_session = $manager->get($current_session_token);

	// == instead of === becasue we don't care about the order of the values
	if ($last_session != $current_session) {
        $sessions = array_slice($sessions, -1);
        update_user_meta($user_id, 'session_tokens', $sessions);
		$home_url = esc_url( home_url('?kicked_session_message=1') );
		// TODO: Find a better way to redirect
        echo '<script>window.location.href="' . $home_url . '"</script>';
		exit;
	}
}

/**
 * Show message if user was kicked from other sessions
 */
function show_kicked_session_message()
{
	if (isset($_GET['kicked_session_message']) && $_GET['kicked_session_message'] == '1') {
		// TODO: localize the message and create a toas manager
		$js = "
        Toastify({
            text: 'You have been logged out because your account was used on another device.',
            duration: 8000,
			gravity: 'bottom',
			position: 'right',
			close: true,
			style: {
				background: '#fe6a48',
			},
        }).showToast();
    	";
		wp_add_inline_script('toastify-js', $js);
	}
}
