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

    default:
      $error_message = 'Unhandled Bricks form action';
      error_log("$error_message (form CSS ID: $cssId)");
      $form->set_result([
        'action' => 'handle_bricks_forms_action',
        'type'    => 'error', // 'success' or 'error' or 'info'
        'message' => esc_html__($error_message, 'bricks'),
      ]);
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
  $game_build = $uploaded_files['gamebuild'] ?? '';
  $form_fields['game_build'] = $game_build[0]['file'] ?? '';
  $game_description = $fields['description'] ?? '';
  $form_fields['game_description'] = $game_description;
  $preview_video = $uploaded_files['previewvideo'] ?? '';
  $form_fields['preview_video'] = $preview_video[0]['file'] ?? '';
  $credits = $fields['credits'] ?? '';
  $form_fields['credits'] = $credits;
  $controls = $fields['controls'] ?? '';
  $form_fields['controls'] = $controls;
  $game_logo = $uploaded_files['gamelogo'] ?? '';
  $form_fields['game_logo'] = $game_logo[0]['file'] ?? '';
  $game_icon = $uploaded_files['gameicon'] ?? '';
  $form_fields['game_icon'] = $game_icon[0]['file'] ?? '';
  $form_fields['user_id'] = get_current_user_id();

  if($form_fields['user_id'] <= 0) {
    $error_message = esc_html__('You must be logged in to submit a game.', 'bricks');
    $form->set_result([
      'action' => 'GameSubmissionFormAction',
      'type'    => 'error',
      'message' => $error_message,
    ]);
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
      esc_html__('The following fields are empty but they are required: %s', 'bricks'),
      implode(', ', $empty_fields)
    );
    $form->set_result([
      'action' => 'GameSubmissionFormAction',
      'type'    => 'error',
      'message' => esc_html__($error_message, 'bricks'),
    ]);
    return;
  }

  global $jampack_db;
  if (!isset($jampack_db)) {
    $error_message = esc_html__('Database connection is not initialized.', 'bricks');
    $form->set_result([
      'action' => 'GameSubmissionFormAction',
      'type'    => 'error',
      'message' => $error_message,
    ]);
    return;
  }

  $game_build_upload = wp_handle_sideload( $game_build, ['test_form' => false]);
  $preview_video_upload = wp_handle_sideload( $preview_video, ['test_form' => false]);
  $game_logo_upload = wp_handle_sideload( $game_logo, ['test_form' => false]);
  $game_icon_upload = wp_handle_sideload( $game_icon, ['test_form' => false]);

  if(!empty($game_build_upload['error']) || !empty($preview_video_upload['error']) || 
     !empty($game_logo_upload['error']) || !empty($game_icon_upload['error'])) {
    $error_message = esc_html__("File upload error // Game Build: " . $game_build_upload['error'] . 
    " - Preview Video: " . $preview_video_upload['error'] . 
    " - Game Logo: " . $game_logo_upload['error'] . 
    " - Game icon: " . $game_icon_upload['error'], 'bricks');
    $form->set_result([
      'action' => 'GameSubmissionFormAction',
      'type'    => 'error',
      'message' => $error_message,
    ]);
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
    $error_message = esc_html__('Failed to insert data into the database.', 'bricks');
    $form->set_result([
      'action' => 'GameSubmissionFormAction',
      'type'    => 'error',
      'message' => $error_message,
    ]);
    error_log('Database insert error: ' . $jampack_db->last_error);
    return;
  }

  $form->set_result([
    'action' => 'GameSubmissionFormAction',
    'type'    => 'success',
    'message' => esc_html__('Data submitted successfully', 'bricks'),
  ]);
}
