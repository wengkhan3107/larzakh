<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpConstants;
use Drupal\miniorange_saml_idp\Utilities;

/**
 * Provides the customer setup form for miniOrange SAML Identity Provider.
 */
class MiniorangeSamlCustomerSetup extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_customer_setup';
  }

  /**
   * Builds the form for customer setup.
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
    $form['#attached']['library'][] = 'miniorange_saml_idp/miniorange_saml_idp.admin';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $current_status = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_status');

    if (empty($current_status) || $current_status == 'CUSTOMER_SETUP') {
      $form['markup_top'] = [
        '#markup' => '<div class="mo_saml_sp_main_container"><div id="Register_Section" class="mo_saml_container">',
      ];

      $form['mo_saml_login'] = [
        '#type' => 'fieldset',
        '#attributes' => ['style' => 'padding:2% 2% 20%; margin-bottom:2%'],
      ];

      $form['mo_saml_login']['markup_15'] = [
        '#markup' => t('<div class="mo_saml_idp_font_for_heading">Login with mini<span class="mo_orange"><b>O</b></span>range</div><p style="clear: both"></p><hr>'),
      ];

      $form['mo_saml_login']['username'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#required' => TRUE,
      ];

      $form['mo_saml_login']['password'] = [
        '#type' => 'password',
        '#title' => $this->t('Password'),
        '#required' => TRUE,
      ];

      $form['mo_saml_login']['miniorange_saml_customer_login_button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Login'),
        '#button_type' => 'primary',
        '#prefix' => '<div class="container-inline">',
      ];

      $form['mo_saml_login']['register_link'] = [
        '#type' => 'link',
        '#title' => $this->t('Create an account'),
        '#url' => Url::fromUri(MiniorangeSamlIdpConstants::CREATE_ACCOUNT_URL),
        '#attributes' => ['class' => 'button', 'target' => '_blank'],
        '#suffix' => '</div>',
      ];

    }
    elseif ($current_status == 'PLUGIN_CONFIGURATION') {
      $form['markup_top_message'] = [
        '#markup' => t('<div class="mo_saml_sp_main_container"><div class="mo_saml_container">'),
      ];

      /* Create container to hold @UserProfile form elements. */

      $form['mo_saml_user_profile'] = [
        '#type' => 'fieldset',
        '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
      ];

      $form['mo_saml_user_profile']['markup_top_'] = [
        '#markup' => t('<div class="mo_saml_welcome_message">Thank you for registering with miniOrange</div><br><h4>Your Profile: </h4>'),
      ];

      $header = [t('Attribute'), t('Value')];
      $options = [
        [
          'Customer Email',
          \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_customer_admin_email'),
        ],
        ['Customer ID', \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_id')],
        ['Drupal Version', Utilities::miniorangeGetDrupalCoreVersion()],
        ['PHP Version', phpversion()],
      ];

      $form['mo_saml_user_profile']['fieldset']['customerinfo'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $options,
        '#suffix' => '<br>',
        '#attributes' => ['style' => 'margin:1% 0% 7%;'],
      ];

      $form['mo_saml_user_profile']['miniorange_saml_customer_Remove_Account_info'] = [
        '#markup' => t('<h4>Remove Account:</h4><p>This section will help you to remove your current
                       logged in account without losing your current configurations.</p>'),
      ];

      $form['mo_saml_user_profile']['miniorange_saml_customer_Remove_Account'] = [
        '#type' => 'link',
        '#title' => $this->t('Remove Account'),
        '#url' => Url::fromRoute('miniorange_saml_idp.modal_form'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
          ],
        ],
      ];
    }

    $form['div_for_end'] = [
      '#markup' => '</div>',
    ];

    Utilities::advertise2fa($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $username = trim($form_state->getValue('username'));
    $password = $form_state->getValue('password');
    Utilities::customerSetupSubmit($username, '', $password, TRUE);
  }

}
