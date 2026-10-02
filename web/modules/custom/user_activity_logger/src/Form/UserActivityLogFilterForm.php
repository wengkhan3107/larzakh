<?php

namespace Drupal\user_activity_logger\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UserActivityLogFilterForm extends FormBase {

  public function getFormId() {
    return 'user_activity_logger_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter by username'),
      '#size' => 20,
      '#default_value' => $form_state->getValue('username', ''),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Just rebuild the page with the filter value as query param.
    $username = $form_state->getValue('username');
    $form_state->setRedirect('user_activity_logger.logs', [], [
      'query' => ['username' => $username],
    ]);
  }
}
