<?php

namespace Drupal\birthday_wish_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Birthday wish mail advanced settings.
 */
class BirthdayWishMailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'birthday_wish_mail_settings_advanced';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'birthday_wish_mail.settings_advanced',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $settings = $this->config('birthday_wish_mail.settings_advanced');
    $form['#tree'] = TRUE;
    $form['bwm_dob'] = [
      '#type'          => 'textfield',
      '#title' => $this->t('Date of birth'),
      '#description' => $this->t('Add the date of birth field machine name from user manage fields.'),
      '#default_value' => $settings->get('bwm_dob'),
      '#required' => TRUE,
    ];
    $form['site'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Mail configuration'),
    ];
    $form['site']['bcc'] = [
      '#type'          => 'email',
      '#title'         => $this->t('BCC email'),
      '#default_value' => $settings->get('site_bcc') ? $settings->get('site_bcc') : '',
    ];
    $form['site']['title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Subject'),
      '#default_value' => $settings->get('site_title'),
      '#required' => TRUE,
    ];
    $form['site']['content'] = [
      '#type'          => 'text_format',
      '#title'         => $this->t('Message'),
      '#default_value' => $settings->get('site_value') ? $settings->get('site_value') : '',
      '#format' => $settings->get('site_format') ? $settings->get('site_format') : 'full_html',
      '#element_validate' => ['token_element_validate'],
      '#token_types' => ['user'],
      '#required' => TRUE,
    ];
    // Add the token tree UI .
    $form['site']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
      '#weight' => 90,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $dob_machine_name = $form_state->getValue('bwm_dob');
    // Check machine name exit or not.
    if (Database::getConnection()->schema()->fieldExists('user__' . $dob_machine_name, $dob_machine_name . '_value') === FALSE) {
      $form_state->setErrorByName('bwm_dob', $this->t('The machine name is not found from the user manage fields.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory->getEditable('birthday_wish_mail.settings_advanced');
    $settings->set('bwm_dob', $form_state->getValue('bwm_dob'))->save();
    $settings->set('site_title', $form_state->getValue('site')['title'])->save();
    $settings->set('site_value', $form_state->getValue('site')['content']['value'])->save();
    $settings->set('site_format', $form_state->getValue('site')['content']['format'])->save();
    $settings->set('site_bcc', $form_state->getValue('site')['bcc'])->save();
    return parent::submitForm($form, $form_state);
  }

}
