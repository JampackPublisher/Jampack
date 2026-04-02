<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

// TODO: Refactor this file for player-rewards redirection

/**
 * Play Pass Subscription Redirections
 * 
 * This file handles redirecting users to the Play Pass page after successful subscription
 * using MemberPress hooks and filters. Follows existing Jampack patterns.
 */

/**
 * MemberPress product ID => WordPress page ID for post-checkout / home / login landing.
 * Order matters: first match wins when a user has multiple tiers.
 * Kept in sync with filter_menu_by_membership() in functions.php.
 *
 * @return array<int, int>
 */
function jampack_subscription_tier_landing_map() {
    return [
        1271 => 1285,
        1270 => 1248,
        1269 => 81,
    ];
}

/**
 * Landing URL for a specific membership product (e.g. thank-you redirect).
 *
 * @param int $product_id MemberPress product post ID.
 * @return string|null Permalink or null if not a mapped tier.
 */
function jampack_landing_url_for_membership_product( $product_id ) {
    $map = jampack_subscription_tier_landing_map();
    $product_id = (int) $product_id;
    if ( isset( $map[ $product_id ] ) ) {
        $link = get_permalink( $map[ $product_id ] );
        return is_string( $link ) ? $link : null;
    }
    return null;
}

/**
 * Default landing for Play Pass products without a tier row in jampack_subscription_tier_landing_map().
 */
function jampack_playpass_default_landing_url() {
    return home_url( '/play-pass/' );
}

/**
 * Resolved landing URL for a user’s active subscriptions (login, home redirect, body data).
 *
 * @param int|null $user_id Optional user ID for login redirect before current user is set.
 * @return string Empty string if user should use default app behavior (no Play Pass).
 */
function jampack_get_user_subscription_landing_url( $user_id = null ) {
    $uid = $user_id !== null ? (int) $user_id : get_current_user_id();
    if ( ! $uid ) {
        return '';
    }
    $ids = get_current_user_subscription_ids( $uid );
    if ( empty( $ids ) ) {
        return '';
    }
    $ids = array_map( 'intval', (array) $ids );
    foreach ( jampack_subscription_tier_landing_map() as $product_id => $page_id ) {
        if ( in_array( (int) $product_id, $ids, true ) ) {
            $link = get_permalink( (int) $page_id );
            return is_string( $link ) ? $link : jampack_playpass_default_landing_url();
        }
    }
    if ( jampack_user_has_active_subscription( $uid ) ) {
        return jampack_playpass_default_landing_url();
    }
    return '';
}

/**
 * Get Play Pass product IDs
 * 
 * @return array Array of Play Pass product IDs
 */
function jampack_get_playpass_product_ids() {
    $playpass_products = get_posts([
        'post_type' => 'memberpressproduct',
        'meta_query' => [
            [
                'key' => 'playpass_product', // Custom meta field
                'value' => 'yes',
                'compare' => '='
            ]
        ],
        'fields' => 'ids',
        'numberposts' => -1
    ]);
    
    return $playpass_products;
}

/**
 * Check if a product is a Play Pass product
 * Uses the same pattern as your existing membership checks
 * 
 * @param MeprProduct|int $product The product to check (object or ID)
 * @return bool True if this is a Play Pass product
 */
function jampack_is_playpass_product($product) {
    $product_id = is_object($product) ? $product->ID : (int) $product;

    return in_array($product_id, jampack_get_playpass_product_ids());
}

/**
 * Redirect users to Play Pass page after successful subscription
 * Uses the mepr-thankyou-page-url filter (most reliable method)
 * 
 * @param string $url The default thank you page URL
 * @param array $args Arguments containing membership and transaction details
 * @return string Modified URL for Play Pass subscribers
 */
function jampack_playpass_playerrewards_thankyou_redirect($url, $args = []) {
    // Check if we have membership information
    if (!isset($args['membership_id']) || empty($args['membership_id'])) {
        return $url;
    }

    $mid = (int) $args['membership_id'];
    if ( ! jampack_is_playpass_product( $mid ) ) {
        return $url;
    }

    $tier_url = jampack_landing_url_for_membership_product( $mid );
    if ( $tier_url ) {
        return $tier_url;
    }

    return jampack_playpass_default_landing_url();
}

/**
 * Redirect home page visits to Play Pass for users with active subscriptions
 * This ensures any "back to home" or home page visits go to Play Pass
 * ONLY if the user has a valid, active subscription
 */
function jampack_home_to_playpass_playerrewards_redirect() {
    // Only redirect if user is on the home page
    if (is_home() || is_front_page()) {
        //TODO: This logic is used to skip admins during redirection to play pass page,I don't see a reason to skip admins, but i'm leaving here for now in case it is needed.
        // if (current_user_can('administrator')) {
        //     return;
        // }

        // Check if user has active subscription
        if ( jampack_user_has_active_subscription() ) {
            $dest = jampack_get_user_subscription_landing_url();
            wp_redirect( $dest ?: jampack_playpass_default_landing_url() );
            exit;
        }
    }
}

/**
 * Check if current user has an active subscription to any Play Pass product
 * Uses the existing subscription checking logic from functions.php
 * 
 * @param int|null $user_id Optional user ID.
 * @return bool True if user has active subscription, false otherwise
 */
function jampack_user_has_active_subscription( $user_id = null ) {
    $uid = $user_id !== null ? (int) $user_id : get_current_user_id();
    if ( ! $uid ) {
        return false;
    }

    $active_subscription_ids = get_current_user_subscription_ids( $uid );

    // If no active subscriptions, return false
    if (empty($active_subscription_ids)) {
        return false;
    }

    // Check if any active subscription matches our Play Pass products
    $playpass_product_ids = jampack_get_playpass_product_ids();
    $has_playpass_subscription = !empty(array_intersect($active_subscription_ids, $playpass_product_ids));

    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            'Jampack: User subscription check - Active IDs: [%s], Play Pass IDs: [%s], Has subscription: %s',
            implode(', ', $active_subscription_ids),
            implode(', ', $playpass_product_ids),
            $has_playpass_subscription ? 'YES' : 'NO'
        ));
    }

    return $has_playpass_subscription;
}
add_action('template_redirect', 'jampack_home_to_playpass_playerrewards_redirect', 1);

/**
 * Add subscription status data to body tag for JavaScript access
 * This allows the back button to conditionally redirect based on subscription status
 */
function jampack_add_subscription_data_to_body() {
    $has_active = jampack_user_has_active_subscription();
    $has_active_subscription = $has_active ? 'true' : 'false';
    $landing = '';
    if ( $has_active ) {
        $landing = jampack_get_user_subscription_landing_url() ?: jampack_playpass_default_landing_url();
    }
    $landing_json = wp_json_encode( $landing );
    echo '<script>document.body.dataset.hasActiveSubscription = "' . esc_js( $has_active_subscription ) . '";document.body.dataset.subscriptionLanding = ' . $landing_json . ';</script>';
}
add_action('wp_footer', 'jampack_add_subscription_data_to_body');

add_filter('mepr-thankyou-page-url', 'jampack_playpass_playerrewards_thankyou_redirect', 10, 2);

/**
 * Debug logging for Play Pass redirections
 * Only active when WP_DEBUG is enabled
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    function jampack_log_playpass_redirect($txn) {
        $product = $txn->product();
        if (jampack_is_playpass_product($product)) {
            error_log(sprintf(
                'Jampack Play Pass redirect triggered for product: %s (ID: %d)',
                $product->post_title,
                $product->ID
            ));
        }
    }
    add_action('mepr-signup', 'jampack_log_playpass_redirect', 5, 1);
}
