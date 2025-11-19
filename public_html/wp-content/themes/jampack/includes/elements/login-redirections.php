<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}


function redirection_after_mepr_login($url, $user)
{
    if(!in_array('administrator', (array) $user->roles)) {
        return home_url('/play-pass/');
    }

    return $url;
}

add_filter( 'mepr-process-login-redirect-url', 'redirection_after_mepr_login', 10, 2 );
