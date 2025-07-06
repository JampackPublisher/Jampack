<?php
function handle_forms($form) {

  $settings = $form->get_settings();
  $cssId = $settings['_cssId'] ?? '';

  switch ($cssId) {
    case 'game-submission-form-id-1':
      handle_game_submission_form($form);
      break;

    default:
      error_log("Unhandled Bricks form action (form ID: $cssId)");
      $form->set_result([
        'action' => 'handle_bricks_forms_action',
        'type'    => 'error', // or 'error' or 'info'
        'message' => esc_html__('Error in form', 'bricks'),
      ]);
      break;
  }
}

function handle_game_submission_form($form){
  $fields = $form->get_fields();
  $form->set_result([
    'action' => 'GameSubmissionFormAction',
    'type'    => 'success', // or 'error' or 'info'
    'message' => esc_html__('handle_game_submission_form works', 'bricks'),
  ]);
}

add_action('bricks/form/custom_action', 'handle_forms', 10, 1);
