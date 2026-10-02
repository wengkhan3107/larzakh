<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\miniorange_saml_idp\Utilities;

/**
 * Handles the signon settings for the miniOrange SAML Identity Provider.
 */
class MiniorangeSignonSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_login_setting';
  }

  /**
   * Builds the form for configuring IDP initiated login settings.
   *
   * @param array $form
   *   The form array containing the form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The rendered form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();

    $form['markup_idp_login_header'] = [
      '#attached' => [
        'library' => [
          "miniorange_saml_idp/miniorange_saml_idp_copy.icon",
          "miniorange_saml_idp/miniorange_saml_idp.admin",
        ],
      ],
      '#markup' => t('<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout mo_saml_container">
                               <div class="mo_saml_idp_font_for_heading">IDP Initiated Login <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/Licensing">[PREMIUM]</a></div>
                               <p style="clear: both"></p><hr><br>'),
    ];

    $disabled = TRUE;

    $form['sign_on_settings'] = [
      '#type' => 'fieldset',
      '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],
    ];

    $form['sign_on_settings']['Checkbox'] = [
      '#prefix' => '</div>',
      '#type' => 'checkbox',
      '#title' => t('Enable Identity Provider Initiated SSO &nbsp;&nbsp;&nbsp;&nbsp;') . '<a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/Licensing">[PREMIUM]</a>',
      '#description' => '<b>Note: </b>' . t('Check this checkbox to enable IDP initiate SSO') . '<br> ',
      '#disabled' => $disabled,
    ];

    $form['sign_on_settings']['mo_saml_idp_debugging_log'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable logging &nbsp;&nbsp;&nbsp;&nbsp;') . '<a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/Licensing">[PREMIUM]</a>',
      '#description' => t('Enabling this checkbox will add loggers under the <a target="_blank" href="@url"> Reports </a> section', ['@url' => $base_url . '/admin/reports/dblog']),
      '#suffix' => '<br><br>',
      '#disabled' => $disabled,
    ];

    $form['sign_on_settings']['mo_saml_idp_logging'] = [
      '#type' => 'submit',
      '#value' => t('Save Configuration'),
      '#disabled' => $disabled,
      '#attributes' => ['class' => ['button button--primary']],
    ];

    Utilities::advertise2fa($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
