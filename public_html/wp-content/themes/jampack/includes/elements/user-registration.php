<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * JaamPack User Registration
 * Handles user registration through Bricks forms with proper validation, security, and integration with WordPress.
 */

/**
 * Handle user registration form submission
 *
 * @param Bricks_Form $form The form instance.
 */
function handle_user_registration_form($form)
{
    // Add debugging
    error_log("JaamPack: Registration handler called");
    
    $fields = $form->get_fields();
    
    // Debug: Log all form fields
    error_log("JaamPack: Form fields received: " . print_r($fields, true));
    
    // Get form field values (Bricks prefixes field names with 'form-field-')
    $user_name = sanitize_text_field($fields['form-field-fullname'] ?? '');
    $user_email = sanitize_email($fields['form-field-email'] ?? '');
    $user_password = $fields['form-field-password'] ?? '';
    
    // Debug: Log extracted values
    error_log("JaamPack: Extracted - Name: '$user_name', Email: '$user_email', Password length: " . strlen($user_password));
    
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
    
    // If there are errors, return them
    if (!empty($errors)) {
        jampack_set_form_result($form, 'UserRegistrationAction', 'error', implode(' ', $errors));
        return;
    }
    
    // Generate unique username from email
    $base_username = explode('@', $user_email)[0];
    $username = sanitize_user($base_username);
    $counter = 1;
    
    while (username_exists($username)) {
        $username = sanitize_user($base_username . $counter);
        $counter++;
    }
    
    // Create user data
    $user_data = [
        'user_login'    => $username,
        'user_email'    => $user_email,
        'user_pass'     => $user_password,
        'display_name'  => $user_name,
        'first_name'    => $user_name,
        'role'          => get_option('default_role', 'subscriber')
    ];
    
    // Create the user
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        jampack_set_form_result($form, 'UserRegistrationAction', 'error', $user_id->get_error_message());
        return;
    }
    
    // Send notification emails
    wp_new_user_notification($user_id, null, 'both');
    
    // Log successful registration
    error_log("JaamPack: New user registered - ID: {$user_id}, Email: {$user_email}");
    
    // Success
    jampack_set_form_result($form, 'UserRegistrationAction', 'success', 'Registration successful!');
}

/**
 * Helper function to set form results
 * 
 * @param object $form The form instance
 * @param string $action Action name
 * @param string $type Result type (success/error)
 * @param string $message Message to display
 */
function jampack_set_form_result($form, $action, $type, $message) {
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
function jampack_add_register_button_to_mepr_login($atts = []) {
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
add_action('mepr-login-form-after-submit', 'jampack_add_register_button_to_mepr_login');

/**
 * Add register button to Bricks login forms using proper hooks
 * This handles Bricks forms with login action
 */
function jampack_add_register_button_to_bricks_login($form) {
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

// Hook into Bricks login forms - this is the RECOMMENDED approach
add_action('bricks/form/custom_action', 'jampack_add_register_button_to_bricks_login');

/**
 * Add register button to WordPress default login forms using proper hooks
 * This handles wp-login.php and other WordPress login forms
 */
function jampack_add_register_button_to_wp_login() {
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
add_action('login_form', 'jampack_add_register_button_to_wp_login');

/**
 * Enable user registration by default
 */
function jampack_enable_registration() {
    if (!get_option('users_can_register')) {
        update_option('users_can_register', 1);
    }
}
add_action('init', 'jampack_enable_registration');

/**
 * Validate registration data (can be used for additional validation)
 * 
 * @param string $name User's full name
 * @param string $email User's email
 * @param string $password User's password
 * @return array Array of validation errors (empty if valid)
 */
function jampack_validate_registration_data($name, $email, $password) {
    $errors = [];
    
    //TODO: Add Extra Validation here with Email, Password, or name whatever is necessary, Default validation by Memberpress seem sufficent for now
    
    return $errors;
}

/**
 * Log registration attempts for security monitoring
 * 
 * @param string $email Email attempted
 * @param bool $success Whether registration was successful
 * @param string $error_message Error message if failed
 */
function jampack_log_registration_attempt($email, $success = true, $error_message = '') {
    $log_message = $success 
        ? "Registration successful for: {$email}" 
        : "Registration failed for: {$email} - Error: {$error_message}";
    
    error_log("JaamPack Registration: {$log_message}");
}