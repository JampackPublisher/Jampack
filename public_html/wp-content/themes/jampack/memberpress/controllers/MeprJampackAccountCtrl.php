<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class MeprJampackAccountCtrl extends MeprBaseCtrl
{
    public function __construct() {
        parent::__construct();
    }

    public function statistics() {
        $mepr_options = MeprOptions::fetch();
        return MeprView::render('/account/statistics', get_defined_vars(), [JMP_MEPR_READY_LAUNCH_PATH]);
    }

    public function load_hooks() {
        // Implement hook loading logic here if needed
    }
}
