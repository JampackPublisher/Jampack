<?php
if (!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}

/**
 * Handle Bricks forms submissions.
 *
 * @param Bricks_Form $form The form instance. 
 * This function calls a proper method based on the form's CSS ID.
 */
function handle_forms($form)
{
  $settings = $form->get_settings();
  $cssId = $settings['_cssId'] ?? '';

  switch ($cssId) {
    case 'game-submission-form-id-1':
      handle_game_submission_form($form);
      break;
    
    case 'user-registration-form':
      handle_user_registration_form($form);
      break;

    default:
      $error_message = 'Unhandled Bricks form action';
      error_log("$error_message (form CSS ID: $cssId)");
      set_result($form, 'handle_bricks_forms_action', 'error', $error_message);
      break;
  }
}

add_action('bricks/form/custom_action', 'handle_forms', 10, 1);

/**
 * Handle the game submission form.
 *
 * @param Bricks_Form $form The form instance.
 * (If this file increases in size, and there are features that are not related. 
 * These features could be moved to other files.)
 */
function handle_game_submission_form($form)
{
  $fields = $form->get_fields();
  $uploaded_files = $form->get_uploaded_files();

  $form_fields = [];
  $game_build = $uploaded_files['gamebuild'][0] ?? '';
  $form_fields['game_build'] = $game_build['file'] ?? '';
  $game_description = $fields['description'] ?? '';
  $form_fields['game_description'] = $game_description;
  $preview_video = $uploaded_files['previewvideo'][0] ?? '';
  $form_fields['preview_video'] = $preview_video['file'] ?? '';
  $credits = $fields['credits'] ?? '';
  $form_fields['credits'] = $credits;
  $controls = $fields['controls'] ?? '';
  $form_fields['controls'] = $controls;
  $game_logo = $uploaded_files['gamelogo'][0] ?? '';
  $form_fields['game_logo'] = $game_logo['file'] ?? '';
  $game_icon = $uploaded_files['gameicon'][0] ?? '';
  $form_fields['game_icon'] = $game_icon['file'] ?? '';
  $form_fields['user_id'] = get_current_user_id();

  if($form_fields['user_id'] <= 0) {
    $error_message = 'You must be logged in to submit a game.';
    set_result($form, 'GameSubmissionFormAction', 'error', $error_message);
    error_log($error_message);
    return;
  }
 
  $empty_fields = [];
  foreach ($form_fields as $field => $value) {
    if (empty($value)) {
      $empty_fields[] = $field;
    }
  }

  if (!empty($empty_fields)) {
    $error_message = sprintf(
      'The following fields are empty but they are required: %s',
      implode(', ', $empty_fields)
    );
    set_result($form, 'GameSubmissionFormAction', 'error', $error_message);
    error_log($error_message);
    return;
  }

  $game_build['tmp_name'] = $game_build['file'];
  $game_build_upload = wp_handle_sideload( $game_build, ['test_form' => false]);
  $preview_video['tmp_name'] = $preview_video['file'];
  $preview_video_upload = wp_handle_sideload( $preview_video, ['test_form' => false]);
  $game_logo['tmp_name'] = $game_logo['file'];
  $game_logo_upload = wp_handle_sideload( $game_logo, ['test_form' => false]);
  $game_icon['tmp_name'] = $game_icon['file'];
  $game_icon_upload = wp_handle_sideload( $game_icon, ['test_form' => false]);

  if(!empty($game_build_upload['error']) || !empty($preview_video_upload['error']) || 
     !empty($game_logo_upload['error']) || !empty($game_icon_upload['error'])) {
    $error_message = "File upload error // " . 
    (!empty($game_build_upload['error']) ? (" - Game Build: " . $game_build_upload['error']) : (''))  . 
    (!empty($preview_video_upload['error']) ? (" - Preview Video: " . $preview_video_upload['error']) : (''))  . 
    (!empty($game_logo_upload['error']) ? (" - Game Logo: " . $game_logo_upload['error']) : ('')) . 
    (!empty($game_icon_upload['error']) ? (" - Game icon: " . $game_icon_upload['error']) : (''));
    set_result($form, 'GameSubmissionFormAction', 'error', $error_message);
    error_log($error_message);
    return;
  }

  // Use the actual file paths from the uploads
  $form_fields['game_build'] = $game_build_upload['file'];
  $form_fields['preview_video'] = $preview_video_upload['file'];
  $form_fields['game_logo'] = $game_logo_upload['file'];
  $form_fields['game_icon'] = $game_icon_upload['file'];

  global $jampack_db;
  if (!isset($jampack_db)) {
    $error_message = 'Database connection is not initialized.';
    set_result($form, 'GameSubmissionFormAction', 'error', $error_message);
    return;
  }

  // TODO: Implement Jampack database connection and logic through an own class. (You can declare the tables there)
  $table_name = 'games';
  $result = $jampack_db->insert(
              $table_name,
              [
                'user_id'           => $form_fields['user_id'],
                'game_build'        => $form_fields['game_build'],
                'game_description'  => $form_fields['game_description'],
                'preview_video'     => $form_fields['preview_video'],
                'credits'           => $form_fields['credits'],
                'controls'          => $form_fields['controls'],
                'game_logo'         => $form_fields['game_logo'],
                'game_icon'         => $form_fields['game_icon']
              ]
            );

  if ($result === false) {
    $error_message = 'Failed to insert data into the database.';
    set_result($form, 'GameSubmissionFormAction', 'error', $error_message);
    error_log('Database insert error: ' . $jampack_db->last_error);
    return;
  }

  set_result($form, 'GameSubmissionFormAction', 'success', 'Data submitted successfully');
}

function set_result($form, $action, $type, $message) {
  $form->set_result([
    'action' => $action,
    'type'   => $type,
    'message' => esc_html__($message, 'bricks'),
  ]);
}
