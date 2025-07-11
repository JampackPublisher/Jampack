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
  $form_fields['game_build'] = $game_build[0]['file'];
  $description = $fields['description'] ?? '';
  $form_fields['description'] = $description;
  $preview_video = $uploaded_files['previewvideo'] ?? '';
  $form_fields['preview_video'] = $preview_video[0]['file'];
  $credits = $fields['credits'] ?? '';
  $form_fields['credits'] = $credits;
  $controls = $fields['controls'] ?? '';
  $form_fields['controls'] = $controls;
  $game_logo = $uploaded_files['gamelogo'] ?? '';
  $form_fields['game_logo'] = $game_logo[0]['file'];
  $game_icon = $uploaded_files['gameicon'] ?? '';
  $form_fields['game_icon'] = $game_icon[0]['file'];

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

  $form->set_result([
    'action' => 'GameSubmissionFormAction',
    'type'    => 'success',
    'message' => esc_html__('Data submitted successfully', 'bricks'),
  ]);
}
