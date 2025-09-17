<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Play Pass Subscription Redirections
 * 
 * This file handles redirecting users to the Play Pass page after successful subscription
 * using MemberPress hooks and filters. Follows existing Jampack patterns.
 */

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
function jampack_playpass_thankyou_redirect($url, $args = []) {
    // Check if we have membership information
    if (!isset($args['membership_id']) || empty($args['membership_id'])) {
        return $url;
    }

    // Check if this is a Play Pass product
    if (jampack_is_playpass_product((int) $args['membership_id'])) {
        return home_url('/play-pass/');
    }

    return $url;
}

/**
 * Redirect home page visits to Play Pass for users with active subscriptions
 * This ensures any "back to home" or home page visits go to Play Pass
 * ONLY if the user has a valid, active subscription
 */
function jampack_home_to_playpass_redirect() {
    // Only redirect if user is on the home page
    if (is_home() || is_front_page()) {
        //TODO: This logic is used to skip admins during redirection to play pass page,I don't see a reason to skip admins, but i'm leaving here for now in case it is needed.
        // if (current_user_can('administrator')) {
        //     return;
        // }

        // Check if user has active subscription
        if (jampack_user_has_active_subscription()) {
            wp_redirect(home_url('/play-pass/'));
            exit;
        }
    }
}

/**
 * Check if current user has an active subscription to any Play Pass product
 * Uses the existing subscription checking logic from functions.php
 * 
 * @return bool True if user has active subscription, false otherwise
 */
function jampack_user_has_active_subscription() {
    // Must be logged in
    if (!is_user_logged_in()) {
        return false;
    }

    // Get active subscription IDs using existing function
    $active_subscription_ids = get_current_user_subscription_ids();

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
add_action('template_redirect', 'jampack_home_to_playpass_redirect', 1);

/**
 * Add subscription status data to body tag for JavaScript access
 * This allows the back button to conditionally redirect based on subscription status
 */
function jampack_add_subscription_data_to_body() {
    $has_active_subscription = jampack_user_has_active_subscription() ? 'true' : 'false';
    echo '<script>document.body.dataset.hasActiveSubscription = "' . $has_active_subscription . '";</script>';
}
add_action('wp_footer', 'jampack_add_subscription_data_to_body');

add_filter('mepr-thankyou-page-url', 'jampack_playpass_thankyou_redirect', 10, 2);

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
