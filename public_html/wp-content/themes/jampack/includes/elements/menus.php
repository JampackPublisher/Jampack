<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

function developer_account_menu($items, $args)
{
    if (is_user_logged_in()) {
        if ($args->menu == JPCK_USER_MENU_ID) {
            $menu = wp_get_nav_menu_object($args->menu);
            if ($menu->name == JPCK_USER_MENU_NAME) {
                $user = wp_get_current_user();
                $jampack_account = MeprCtrlFactory::fetch('JampackAccount');
                if (in_array($jampack_account->developer_role_to_string(), (array) $user->roles) || current_user_can('administrator')) {
                    // Add the "Game Submission" link to the menu after the first menu item
                    $gamesub_menu_link = JPCK_GAMESUBMISSION_LINK;
                    $string_to_find = '</li>';
                    $position_in_menu = 5;
                    $pos = 0;
                    for ($i = 0; $i < $position_in_menu - 1; $i++) {
                        $pos = strpos($items, $string_to_find, $pos);
                        if ($pos === false) {
                            break;
                        }
                        $pos += strlen($string_to_find);
                    }
                    if ($pos !== false) {
                        $items = substr_replace($items, $gamesub_menu_link, $pos, 0);
                    }
                }
            }
        }
    }
    return $items;
}


add_filter('wp_nav_menu_items', 'developer_account_menu', 10, 2);
