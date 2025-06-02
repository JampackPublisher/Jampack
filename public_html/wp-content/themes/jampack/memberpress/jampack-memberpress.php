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