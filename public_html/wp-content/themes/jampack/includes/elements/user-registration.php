<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * JaamPack User Registration System
 * 
 * Handles user registration through Bricks forms with proper validation,
 * security, and integration with WordPress user system.
 */

/**
 * Handle user registration form submission
 *
 * @param Bricks_Form $form The form instance.
 */
function handle_user_registration_form($form)
{
    $fields = $form->get_fields();
    
    // Get form field values
    $user_name = sanitize_text_field($fields['fullname'] ?? '');
    $user_email = sanitize_email($fields['email'] ?? '');
    $user_password = $fields['password'] ?? '';
    
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
    jampack_set_form_result($form, 'UserRegistrationAction', 'success', 'Registration successful! Please check your email for login details.');
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
 * Add register button to login pages via JavaScript
 */
function jampack_add_register_button_script() {
    // Only add on pages (not posts or other content types)
    if (!is_page() && !is_front_page()) {
        return;
    }
    
    // Get registration page URL
    $registration_page = get_page_by_path('register');
    $registration_url = $registration_page ? get_permalink($registration_page->ID) : site_url('/register');
    
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Look for common login form selectors
        var loginForms = document.querySelectorAll('form[class*="login"], form[id*="login"], .mepr-login-form, .bricks-form');
        
        if (loginForms.length > 0) {
            loginForms.forEach(function(form) {
                // Check if register button already exists
                if (form.querySelector('.jampack-register-btn')) {
                    return;
                }
                
                // Create register button
                var registerDiv = document.createElement('div');
                registerDiv.className = 'jampack-register-section';
                registerDiv.style.cssText = 'text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;';
                
                var registerText = document.createElement('p');
                registerText.style.cssText = 'margin: 0 0 10px 0; color: #666; font-size: 14px;';
                registerText.textContent = "Don't have an account?";
                
                var registerBtn = document.createElement('a');
                registerBtn.href = '<?php echo esc_js($registration_url); ?>';
                registerBtn.className = 'jampack-register-btn';
                registerBtn.textContent = 'Create Account';
                registerBtn.style.cssText = 'display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 500; transition: all 0.3s ease;';
                
                // Add hover effect
                registerBtn.addEventListener('mouseenter', function() {
                    this.style.background = '#005a87';
                    this.style.transform = 'translateY(-1px)';
                    this.style.boxShadow = '0 2px 8px rgba(0,115,170,0.3)';
                });
                registerBtn.addEventListener('mouseleave', function() {
                    this.style.background = '#0073aa';
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
                
                registerDiv.appendChild(registerText);
                registerDiv.appendChild(registerBtn);
                
                // Insert after the form
                form.parentNode.insertBefore(registerDiv, form.nextSibling);
            });
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'jampack_add_register_button_script');

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
    
    // Additional validation rules can be added here
    // For example: password complexity, name format, etc.
    
    return $errors;
}

/**
 * Custom registration redirect (if needed)
 */
function jampack_registration_redirect() {
    // Custom redirect logic can be added here
    // For example: redirect to specific page after registration
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