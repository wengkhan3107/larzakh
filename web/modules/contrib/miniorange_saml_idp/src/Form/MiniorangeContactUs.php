<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpConstants;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpSupport;
use Drupal\miniorange_saml_idp\Utilities;

/**
 * Class MiniorangeContactUs.
 *
 * This class defines a form to handle the 'Contact Us' functionality.
 */
class MiniorangeContactUs extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_contact_us';
  }

  /**
   * Builds the contact form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param null $options
   *   Additional options, if any.
   *
   * @return array
   *   The rendered form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['#prefix'] = '<div id="modal_support_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $db_config = \Drupal::config('miniorange_saml_idp.settings');
    $user_email = $db_config->get('miniorange_saml_idp_customer_admin_email');
    $phone = $db_config->get('miniorange_saml_customer_admin_phone');

    $form['markup_1'] = [
      '#markup' => t('<p>Need any help? We can help you with configuring miniOrange SAML IDP module on your site. Just reach out to us on <a href="mailto:@support_email">here</a> and we will get back to you soon.</p>', ['@support_email' => MiniorangeSamlIdpConstants::SUPPORT_EMAIL]),
    ];

    $form['miniorange_saml_idp_support_email_address'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#default_value' => $user_email,
      '#required' => TRUE,
      '#attributes' => ['placeholder' => t('Enter your email'), 'style' => 'width:99%;margin-bottom:1%;'],
    ];
    $form['miniorange_saml_idp_support_phone_number'] = [
      '#type' => 'textfield',
      '#title' => t('Phone'),
      '#default_value' => $phone,
      '#attributes' => [
        'placeholder' => t('Enter number with country code Eg. +00xxxxxxxxxx'),
        'style' => 'width:99%;margin-bottom:1%;',
      ],
    ];
    $form['miniorange_saml_idp_support_query'] = [
      '#type' => 'textarea',
      '#title' => t('Query'),
      '#required' => TRUE,
      '#attributes' => ['placeholder' => t('Describe your query here!'), 'style' => 'width:99%'],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button--primary',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * Handles the AJAX submission of the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object to be returned to the AJAX callback.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // If there are any form errors, AJAX replace the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_support_form', $form));
    }
    else {
      $email = $form['miniorange_saml_idp_support_email_address']['#value'];
      $phone = $form['miniorange_saml_idp_support_phone_number']['#value'];
      $query = $form['miniorange_saml_idp_support_query']['#value'] . '<br><br>' . 'Timezone: ' . Utilities::getFormatedTimezone();
      $query_type = 'Support';
      $support = new MiniorangeSamlIdpSupport($email, $phone, $query, $query_type);
      $support_response = $support->sendSupportQuery();
      $ajax_form = Utilities::getModalFormAfterQuery($support_response, $email, FALSE);
      $response->addCommand($ajax_form);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
