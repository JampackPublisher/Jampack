<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

function children_restrictions($content) {
    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;
    $jampack_account = MeprCtrlFactory::fetch('JampackAccount');
    if (is_singular('games') && !empty(array_intersect($jampack_account->children_roles_to_string(), $user_roles)) && !is_user_admin()) {
        $post_id = get_the_ID();
        $genres = wp_get_post_terms($post_id, 'genre', ['fields' => 'slugs']);
        if (!empty(array_intersect(JPCK_CHILDREN_RESTRICTED_GENRES, $genres))) {
            // TODO: Redirect to a custom page
            wp_die('This content is restricted. You are not allowed to play this game.', 'Access denied', [
                'response' => 403,
            ]);
        }
    }

    return $content;
}

add_filter('template_redirect', 'children_restrictions');
