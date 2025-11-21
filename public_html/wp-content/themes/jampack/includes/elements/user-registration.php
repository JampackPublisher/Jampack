<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Jampack User Registration
 * Handles user registration through Bricks forms with proper validation, security, and integration with WordPress.
 */

// Constants for email verification
define('JAMPACK_VERIFICATION_EXPIRY', 24 * HOUR_IN_SECONDS); // 24 hours expiration

/**
 * Handle user registration form submission
 * Stores registration data temporarily and sends verification email
 *
 * @param Bricks_Form $form The form instance.
 */
function handle_user_registration_form($form)
{
    error_log("Jampack: Registration handler called");
    
    $fields = $form->get_fields();
    
    error_log("Jampack: Form fields received: " . print_r($fields, true));
    
    // Get form field values (Bricks prefixes field names with 'form-field-')
    $user_name = sanitize_text_field($fields['form-field-fullname'] ?? '');
    $user_email = sanitize_email($fields['form-field-email'] ?? '');
    $user_password = $fields['form-field-password'] ?? '';
    
    error_log("Jampack: Extracted - Name: '$user_name', Email: '$user_email', Password length: " . strlen($user_password));
    
    // Validate inputs
    $errors = [];
    
    if (empty($user_name)) {
        $errors[] = 'Please enter your full name.';
    }
    
    if (empty($user_email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!is_email($user_email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (email_exists($user_email)) {
        $errors[] = 'This email address is already registered. Please use a different email or log in.';
    }
    
    if (empty($user_password)) {
        $errors[] = 'Please enter a password.';
    } elseif (strlen($user_password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if (!empty($errors)) {
        jampack_signup_set_form_result($form, 'UserRegistrationAction', 'error', implode(' ', $errors));
        return;
    }
    
    // Check for pending registration with email during sign up
    $existing_pending = jampack_signup_get_pending_registration($user_email);
    if ($existing_pending) {
        // Resend verification email instead of creating duplicate
        jampack_signup_send_verification_email($user_email, $existing_pending['token'], $user_name);
        jampack_signup_set_form_result($form, 'UserRegistrationAction', 'success', 
            'A confirmation email has been sent to ' . esc_html($user_email) . '. Please check your inbox and click the verification link to complete your registration.');
        return;
    }
    
    // Generate unique username from email (for when we create the account)
    $base_username = explode('@', $user_email)[0];
    $username = sanitize_user($base_username);
    $counter = 1;
    
    while (username_exists($username)) {
        $username = sanitize_user($base_username . $counter);
        $counter++;
    }
    
    // Generate secure verification token
    $verification_token = jampack_signup_generate_verification_token();
    
    // Store registration data temporarily (password stored securely)
    $registration_data = [
        'user_name'     => $user_name,
        'user_email'    => $user_email,
        'user_login'    => $username,
        'user_password' => $user_password, // Will be hashed by wp_insert_user
        'display_name'  => $user_name,
        'first_name'    => $user_name,
        'role'          => get_option('default_role', 'subscriber'),
        'token'         => $verification_token,
        'created_at'    => current_time('mysql'),
        'ip_address'    => jampack_signup_get_client_ip()
    ];
    
    // Store in transient with expiration
    $transient_key = 'jampack_pending_registration_' . md5($user_email . $verification_token);
    set_transient($transient_key, $registration_data, JAMPACK_VERIFICATION_EXPIRY);
    
    // Also store by email for quick lookup
    set_transient('jampack_pending_email_' . md5($user_email), [
        'token' => $verification_token,
        'transient_key' => $transient_key
    ], JAMPACK_VERIFICATION_EXPIRY);
    
    // Send verification email
    $email_sent = jampack_signup_send_verification_email($user_email, $verification_token, $user_name);
    
    if (!$email_sent) {
        // Clean up on email failure
        delete_transient($transient_key);
        delete_transient('jampack_pending_email_' . md5($user_email));
        jampack_signup_set_form_result($form, 'UserRegistrationAction', 'error', 
            'Failed to send verification email. Please try again or contact support.');
        return;
    }
    
    error_log("Jampack: Pending registration created - Email: {$user_email}, Token: {$verification_token}");
    
    // Success message - displayed in Bricks form
    jampack_signup_set_form_result($form, 'UserRegistrationAction', 'success', 
        'A confirmation email has been sent to ' . esc_html($user_email) . '. Please check your inbox and click the verification link to complete your registration. The link will expire in 24 hours.');
}

/**
 * Generate a secure verification token
 * 
 * @return string Verification token
 */
function jampack_signup_generate_verification_token() {
    // Generate a secure random token (32 characters)
    return bin2hex(random_bytes(16));
}

/**
 * Get pending registration data by email
 * 
 * @param string $email User email
 * @return array|false Registration data or false if not found
 */
function jampack_signup_get_pending_registration($email) {
    $email_hash = md5($email);
    $pending_info = get_transient('jampack_pending_email_' . $email_hash);
    
    if (!$pending_info) {
        return false;
    }
    
    $registration_data = get_transient($pending_info['transient_key']);
    return $registration_data ? $registration_data : false;
}

/**
 * Get pending registration by token
 * 
 * @param string $token Verification token
 * @return array|false Registration data or false if not found
 */
function jampack_signup_get_pending_registration_by_token($token) {
    global $wpdb;
    
    // Search through transients for the token
    $transient_keys = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_jampack_pending_registration_%' 
         AND option_value LIKE '%" . esc_sql($token) . "%'"
    );
    
    foreach ($transient_keys as $transient_key) {
        $key = str_replace('_transient_', '', $transient_key);
        $data = get_transient($key);
        if ($data && isset($data['token']) && $data['token'] === $token) {
            return $data;
        }
    }
    
    return false;
}

/**
 * Send verification email to user
 * 
 * @param string $email User email address
 * @param string $token Verification token
 * @param string $name User's name
 * @return bool True if email sent successfully
 */
function jampack_signup_send_verification_email($email, $token, $name) {
    // Get verification URL
    $verification_url = jampack_signup_get_verification_url($token);
    
    // Email subject
    $subject = sprintf(
        __('[%s] Verify your email address', 'jampack'),
        get_bloginfo('name')
    );
    
    // HTML version of email
    $html_message = sprintf(
        '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #0073aa;">Verify Your Email Address</h2>
                <p>Hello %s,</p>
                <p>Thank you for registering with <strong>%s</strong>!</p>
                <p>To complete your registration, please verify your email address by clicking the button below:</p>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="%s" style="display: inline-block; padding: 12px 30px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Email Address</a>
                </div>
                <p>Or copy and paste this link into your browser:</p>
                <p style="word-break: break-all; color: #0073aa;">%s</p>
                <p style="color: #666; font-size: 12px;">This link will expire in 24 hours.</p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="color: #666; font-size: 12px;">If you did not register for an account, please ignore this email.</p>
                <p style="color: #666; font-size: 12px;">Best regards,<br>%s</p>
            </div>
        </body></html>',
        esc_html($name),
        esc_html(get_bloginfo('name')),
        esc_url($verification_url),
        esc_url($verification_url),
        esc_html(get_bloginfo('name'))
    );
    
    // Set email headers for HTML
    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . get_bloginfo('name') . " <" . get_option('admin_email') . ">\r\n";
    $headers .= "Reply-To: " . get_option('admin_email') . "\r\n";
    
    // Send email
    $sent = wp_mail($email, $subject, $html_message, $headers);
    
    if ($sent) {
        error_log("Jampack: Verification email sent to {$email}");
    } else {
        error_log("Jampack: Failed to send verification email to {$email}");
    }
    
    return $sent;
}

/**
 * Send welcome email to newly verified user
 * 
 * @param int $user_id User ID
 * @param string $email User email address
 * @param string $name User's name
 * @return bool True if email sent successfully
 */
function jampack_signup_send_welcome_email($user_id, $email, $name) {
    $user = get_userdata($user_id);
    $login_url = wp_login_url();
    $site_name = get_bloginfo('name');
    $site_url = home_url();
    
    // Email subject
    $subject = sprintf(
        __('Welcome to %s!', 'jampack'),
        $site_name
    );
    
    // HTML welcome email
    $html_message = sprintf(
        '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
            <div style="max-width: 600px; margin: 0 auto; padding: 0; background-color: #ffffff;">
                <!-- Header -->
                <div style="background-color: #0073aa; padding: 30px 20px; text-align: center;">
                    <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">Welcome to %s!</h1>
                </div>
                
                <!-- Main Content -->
                <div style="padding: 40px 20px;">
                    <p style="font-size: 16px; margin: 0 0 20px 0;">Hello %s,</p>
                    
                    <p style="font-size: 16px; margin: 0 0 20px 0;">Thank you for joining <strong>%s</strong>! We\'re excited to have you as part of our community.</p>
                    
                    <p style="font-size: 16px; margin: 0 0 20px 0;">Your account has been successfully created and verified. You can now log in and start exploring everything we have to offer.</p>
                    
                    <!-- CTA Button -->
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="%s" style="display: inline-block; padding: 14px 32px; background-color: #0073aa; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: bold;">Log In to Your Account</a>
                    </div>
                    
                    <p style="font-size: 16px; margin: 20px 0;">If you have any questions or need assistance, please don\'t hesitate to reach out to our support team.</p>
                    
                    <p style="font-size: 16px; margin: 20px 0 0 0;">We look forward to seeing you around!</p>
                    
                    <p style="font-size: 16px; margin: 30px 0 0 0;">Best regards,<br><strong>The %s Team</strong></p>
                </div>
                
                <!-- Footer -->
                <div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;">
                    <p style="font-size: 12px; color: #666; margin: 0 0 10px 0;">This email was sent to %s</p>
                    <p style="font-size: 12px; color: #666; margin: 0;">
                        <a href="%s" style="color: #0073aa; text-decoration: none;">%s</a>
                    </p>
                </div>
            </div>
        </body></html>',
        esc_html($site_name),
        esc_html($name),
        esc_html($site_name),
        esc_url($login_url),
        esc_html($site_name),
        esc_html($email),
        esc_url($site_url),
        esc_html($site_name)
    );
    
    // Set email headers for HTML
    $headers = "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $site_name . " <" . get_option('admin_email') . ">\r\n";
    $headers .= "Reply-To: " . get_option('admin_email') . "\r\n";
    
    // Send email
    $sent = wp_mail($email, $subject, $html_message, $headers);
    
    if ($sent) {
        error_log("Jampack: Welcome email sent to {$email}");
    } else {
        error_log("Jampack: Failed to send welcome email to {$email}");
    }
    
    return $sent;
}

/**
 * Get verification URL
 * 
 * @param string $token Verification token
 * @return string Verification URL
 */
function jampack_signup_get_verification_url($token) {
    // Use home URL with query parameters
    return add_query_arg([
        'jampack_verify' => 'email',
        'token' => $token
    ], home_url('/'));
}

/**
 * Handle email verification
 * This function is called when user clicks verification link
 */
function jampack_signup_handle_email_verification() {
    // Check if this is a verification request
    if (!isset($_GET['jampack_verify']) || $_GET['jampack_verify'] !== 'email') {
        return;
    }
    
    if (!isset($_GET['token']) || empty($_GET['token'])) {
        wp_die('Invalid verification link. Please check your email and try again.', 'Verification Error', ['response' => 400]);
    }
    
    $token = sanitize_text_field($_GET['token']);
    
    // Get pending registration
    $registration_data = jampack_signup_get_pending_registration_by_token($token);
    
    if (!$registration_data) {
        wp_die(
            'This verification link is invalid or has expired. Please register again.',
            'Verification Error',
            ['response' => 400]
        );
    }
    
    // Check if email is still available (in case someone registered in the meantime)
    if (email_exists($registration_data['user_email'])) {
        // Clean up pending registration
        $transient_key = 'jampack_pending_registration_' . md5($registration_data['user_email'] . $token);
        delete_transient($transient_key);
        delete_transient('jampack_pending_email_' . md5($registration_data['user_email']));
        
        wp_die(
            'This email address is already registered. Please log in instead.',
            'Verification Error',
            ['response' => 400]
        );
    }
    
    // Check if username is still available
    if (username_exists($registration_data['user_login'])) {
        // Generate new username
        $base_username = $registration_data['user_login'];
        $counter = 1;
        while (username_exists($base_username . $counter)) {
            $counter++;
        }
        $registration_data['user_login'] = $base_username . $counter;
    }
    
    // Create the user account
    $user_data = [
        'user_login'    => $registration_data['user_login'],
        'user_email'    => $registration_data['user_email'],
        'user_pass'     => $registration_data['user_password'],
        'display_name'  => $registration_data['display_name'],
        'first_name'    => $registration_data['first_name'],
        'role'          => $registration_data['role']
    ];
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        error_log("Jampack: Failed to create user after verification - " . $user_id->get_error_message());
        wp_die(
            'An error occurred while creating your account. Please contact support.',
            'Registration Error',
            ['response' => 500]
        );
    }
    
    // Clean up pending registration
    $transient_key = 'jampack_pending_registration_' . md5($registration_data['user_email'] . $token);
    delete_transient($transient_key);
    delete_transient('jampack_pending_email_' . md5($registration_data['user_email']));
    
    // Send custom welcome email
    jampack_signup_send_welcome_email($user_id, $registration_data['user_email'], $registration_data['user_name']);
    
    // Notify admin of new registration
    wp_new_user_notification($user_id, null, 'admin');
    
    error_log("Jampack: User verified and created - ID: {$user_id}, Email: {$registration_data['user_email']}");
    
    // Redirect to success page or login
    $redirect_url = add_query_arg('verified', 'success', wp_login_url());
    
    // Set a transient message for display
    set_transient('jampack_verification_success_' . $user_id, true, 300); // 5 minutes
    
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('template_redirect', 'jampack_signup_handle_email_verification');

/**
 * Display verification success message on login page
 */
function jampack_signup_display_verification_success() {
    if (isset($_GET['verified']) && $_GET['verified'] === 'success') {
        ?>
        <div class="jampack-verification-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <strong>Email Verified Successfully!</strong><br>
            Your account has been created. Please log in with your email and password.
        </div>
        <?php
    }
}
add_action('login_form', 'jampack_signup_display_verification_success');

/**
 * Clean up expired pending registrations
 * This runs daily via WordPress cron
 */
function jampack_signup_cleanup_expired_registrations() {
    global $wpdb;
    
    // WordPress automatically cleans up expired transients, but we can add custom cleanup here
    // For a more robust solution, consider using a custom database table
    
    error_log("Jampack: Running cleanup for expired registrations");
}
add_action('jampack_signup_daily_cleanup', 'jampack_signup_cleanup_expired_registrations');

// Schedule daily cleanup if not already scheduled
if (!wp_next_scheduled('jampack_signup_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'jampack_signup_daily_cleanup');
}

/**
 * Get client IP address (for logging/security)
 * 
 * @return string IP address
 */
function jampack_signup_get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
}

/**
 * Helper function to set form results
 * 
 * @param object $form The form instance
 * @param string $action Action name
 * @param string $type Result type (success/error)
 * @param string $message Message to display
 */
function jampack_signup_set_form_result($form, $action, $type, $message) {
    $form->set_result([
        'action' => $action,
        'type'   => $type,
        'message' => esc_html__($message, 'bricks'),
    ]);
}

/**
 * Add register button to MemberPress login forms using proper hooks
 * This is the OPTIMAL approach as recommended by MemberPress documentation
 */
function jampack_signup_add_register_button_to_mepr_login($atts = []) {
    // Only show if user is not logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Get registration page URL
    $registration_page = get_page_by_path('register');
    $registration_url = $registration_page ? get_permalink($registration_page->ID) : site_url('/register');
    
    ?>
    <div class="jampack-register-section" style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
        <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Don't have an account?</p>
        <a href="<?php echo esc_url($registration_url); ?>" 
           class="jampack-register-btn" 
           style="display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 500; transition: all 0.3s ease;">
            Create Account
        </a>
    </div>
    
    <style>
    .jampack-register-btn:hover {
        background: #005a87 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,115,170,0.3);
    }
    </style>
    <?php
}

// Hook into MemberPress login form - this is the RECOMMENDED approach
add_action('mepr-login-form-after-submit', 'jampack_signup_add_register_button_to_mepr_login');

/**
 * Add register button to Bricks login forms using proper hooks
 * This handles Bricks forms with login action
 */
function jampack_signup_add_register_button_to_bricks_login($form) {
    // Only show if user is not logged in
    if (is_user_logged_in()) {
        return;
    }
    
    $settings = $form->get_settings();
    
    // Check if this form has login action
    if (!isset($settings['actions']) || !in_array('login', $settings['actions'])) {
        return;
    }
    
    // Get registration page URL
    $registration_page = get_page_by_path('register');
    $registration_url = $registration_page ? get_permalink($registration_page->ID) : site_url('/register');
    
    ?>
    <div class="jampack-register-section" style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
        <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Don't have an account?</p>
        <a href="<?php echo esc_url($registration_url); ?>" 
           class="jampack-register-btn" 
           style="display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 500; transition: all 0.3s ease;">
            Create Account
        </a>
    </div>
    
    <style>
    .jampack-register-btn:hover {
        background: #005a87 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,115,170,0.3);
    }
    </style>
    <?php
}

add_action('bricks/form/custom_action', 'jampack_signup_add_register_button_to_bricks_login');

/**
 * Add register button to WordPress default login forms using proper hooks
 * This handles wp-login.php and other WordPress login forms
 */
function jampack_signup_add_register_button_to_wp_login() {
    // Only show if user is not logged in
    if (is_user_logged_in()) {
        return;
    }
    
    // Get registration page URL
    $registration_page = get_page_by_path('register');
    $registration_url = $registration_page ? get_permalink($registration_page->ID) : site_url('/register');
    
    ?>
    <div class="jampack-register-section" style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
        <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Don't have an account?</p>
        <a href="<?php echo esc_url($registration_url); ?>" 
           class="jampack-register-btn" 
           style="display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 500; transition: all 0.3s ease;">
            Create Account
        </a>
    </div>
    
    <style>
    .jampack-register-btn:hover {
        background: #005a87 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,115,170,0.3);
    }
    </style>
    <?php
}

// Hook into WordPress login forms
add_action('login_form', 'jampack_signup_add_register_button_to_wp_login');

/**
 * Enable user registration by default
 */
function jampack_signup_enable_registration() {
    if (!get_option('users_can_register')) {
        update_option('users_can_register', 1);
    }
}
add_action('init', 'jampack_signup_enable_registration');

/**
 * Validate registration data (can be used for additional validation)
 * 
 * @param string $name User's full name
 * @param string $email User's email
 * @param string $password User's password
 * @return array Array of validation errors (empty if valid)
 */
function jampack_signup_validate_registration_data($name, $email, $password) {
    $errors = [];
    
    // TODO: Add Extra Validation here with Email, Password, or name whatever is necessary, Default validation by Memberpress seem sufficent for now
    
    return $errors;
}

/**
 * Log registration attempts for security monitoring
 * TODO: Not yet used, We should Implement logging so that account creation failures can be traced by team
 * 
 * @param string $email Email attempted
 * @param bool $success Whether registration was successful
 * @param string $error_message Error message if failed
 */
function jampack_signup_log_registration_attempt($email, $success = true, $error_message = '') {
    $log_message = $success 
        ? "Registration successful for: {$email}" 
        : "Registration failed for: {$email} - Error: {$error_message}";
    
    error_log("Jampack Registration: {$log_message}");
}