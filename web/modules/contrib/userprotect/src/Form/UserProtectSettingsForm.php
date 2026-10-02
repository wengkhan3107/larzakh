<?php

namespace Drupal\userprotect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the settings form for User Protect.
 */
class UserProtectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'userprotect_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['userprotect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('userprotect.settings');

    $form['display_applied_protections_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display applied protections message to administrators'),
      '#description' => $this->t('When editing a user, display a message listing the protections that were applied to the form.'),
      '#default_value' => $config->get('display_applied_protections_message') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('userprotect.settings')
      ->set('display_applied_protections_message', (bool) $form_state->getValue('display_applied_protections_message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
