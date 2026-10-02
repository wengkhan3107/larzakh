<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpSupport;
use Drupal\miniorange_saml_idp\Utilities;

/**
 * Handles the demo request form for the miniOrange SAML Identity Provider.
 */
class MiniorangeSamlIdpRequestDemo extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_idp_request_demo';
  }

  /**
   * Builds the form for requesting a miniOrange SAML trial.
   *
   * @param array $form
   *   The form array to be built.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The built form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $form['#prefix'] = '<div id="miniorange_saml_idp_form">';
    $form['#suffix'] = '</div>';
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    $disabled = FALSE;

    $user_email = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_customer_admin_email');
    if (is_null($user_email)) {
      \Drupal::messenger()->addWarning(t('Please login with your miniOrange account <a target="_blank" href=:url>@here</a> to request a 7-day trial.', [
        ':url' => $base_url . '/admin/config/people/miniorange_saml_idp/customer_setup',
        '@here' => 'here',
      ]));
      $disabled = TRUE;
    }

    $form['miniorange_saml_idp_trial_email_address'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#required' => TRUE,
      '#default_value' => $user_email ?? '',
      '#disabled' => $disabled,
      '#attributes' => ['placeholder' => t('Enter your email')],
    ];

    $form['miniorange_saml_idp_service_provider_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service Provider Name'),
      '#disabled' => $disabled,
      '#attributes' => [
    // 'style' => 'width:60%',
        'placeholder' => t('Enter Service Provider Name'),
      ],
      '#description' => $this->t('The application name where you want to login using SSO service.'),
    ];

    $form['miniorange_saml_idp_trial_description'] = [
      '#type' => 'textarea',
      '#rows' => 4,
      '#title' => t('Description'),
      '#disabled' => $disabled,
      '#attributes' => ['placeholder' => t('Describe your use case here!'), 'style' => 'width:99%;'],
      '#suffix' => '<br>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#disabled' => $disabled,
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
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response containing the modal form.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $form_values = $form_state->getValues();
    $spName = $form_values['miniorange_saml_idp_service_provider_name'];
    // If there are any form errors, AJAX replace the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#miniorange_saml_idp_form', $form));
    }
    else {
      $email = $form['miniorange_saml_idp_trial_email_address']['#value'];
      $query_type = 'Trial Request';
      $trial_details = [
        'User Email' => $email,
        'Service Provider Name' => $spName,
        'Use-case/Requirements' => $form_values['miniorange_saml_idp_trial_description'],
        'Timezone' => Utilities::getFormatedTimezone(),
      ];

      if (empty($spName)) {
        unset($trial_details['Service Provider Name']);
      }

      $query = $this->getQueryContent($trial_details);
      $support = new MiniorangeSamlIdpSupport($email, '', $query, $query_type);
      $support_response = $support->sendSupportQuery();
      $ajax_form = Utilities::getModalFormAfterQuery($support_response, $email, TRUE);
      $response->addCommand($ajax_form);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

  /**
   * Generates the content for the trial request query.
   *
   * @param array $trial_details
   *   The details of the trial request.
   *
   * @return string
   *   The formatted HTML content for the trial request query.
   */
  protected function getQueryContent($trial_details) {
    $html = '<br><br>Account Details and Usecase:';
    $html .= '<pre><code><table style="border-collapse: collapse; border: 1px solid black; width: 100%;">';
    foreach ($trial_details as $key => $value) {
      $html .= '<tr>';
      $html .= '<td style="padding: 10px; width: 15%;"><b>' . $key . ':</b></td>';
      $html .= '<td style="padding: 10px; width: 85%;">' . $value . '</td>';
      $html .= '</tr>';
    }
    $html .= '</table></code></pre>';

    return $html;
  }

}
