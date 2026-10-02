<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to generate custom certificates for miniOrange SAML IDP module.
 */
class MiniorangeGenerateCertificate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_generate_certificate';
  }

  /**
   * Builds the custom certificate generation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'] = ['miniorange_saml_idp/miniorange_saml_idp.admin'];

    $form['miniorange_saml_idp_add_certificate'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Add Custom Certificate'),
    ];

    $form['miniorange_saml_idp_add_certificate']['miniorange_saml_idp_private_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('X.509 Private Certificate'),
      '#disabled' => TRUE,
      '#attributes' => ['placeholder' => '-----BEGIN CERTIFICATE-----' . PHP_EOL . 'XXXXXXXXXXXXXXXXXXXXXXXXXXX' . PHP_EOL . '-----END CERTIFICATE-----'],
      '#description' => t('Copy and paste your private key of X.509 Certificate here.'),
    ];

    $form['miniorange_saml_idp_add_certificate']['miniorange_saml_idp_publ_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('X.509 Public Certificate'),
      '#disabled' => TRUE,
      '#attributes' => ['placeholder' => '-----BEGIN CERTIFICATE-----' . PHP_EOL . 'XXXXXXXXXXXXXXXXXXXXXXXXXXX' . PHP_EOL . '-----END CERTIFICATE-----'],
      '#description' => t('Copy and paste your public key of X.509 Certificate here.'),
    ];

    $form['miniorange_saml_idp_add_certificate']['save_config_elements'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Upload'),
      '#submit' => ['::submitForm'],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_add_certificate']['save_config_elements1'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Generate Custom Certificate'),
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_country_code_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country Code'),
      '#description' => $this->t('<b>Note: </b>Check your country code <a>here</a>.'),
      '#attributes' => [
        'placeholder' => $this->t('Enter Country code'),
      ],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_certificate_state_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#attributes' => [
        'placeholder' => $this->t('State Name'),
      ],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_certificate_company_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Company'),
      '#attributes' => [
        'placeholder' => $this->t('Company Name'),
      ],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate']['miniorange_saml_idp_unit_name'] = [
      '#type' => 'textfield',
      '#title' => 'Unit',
      '#attributes' => [
        'placeholder' => $this->t('Unit name'),
      ],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_certificate_common_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Common'),
      '#attributes' => [
        'placeholder' => $this->t('Common Name'),
      ],
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_select_digest_algo'] = [
      '#type' => 'select',
      '#title' => $this->t('Digest Algorithm'),
      '#options' => [
        'sha512' => $this->t('SHA512'),
        'sha384' => $this->t('SHA384'),
        'sha256' => $this->t('SHA256'),
        'sha1' => $this->t('SHA1'),
      ],
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_select_private_key_bit'] = [
      '#type' => 'select',
      '#title' => $this->t('Bits to generate the private key'),
      '#options' => [
        '2048' => $this->t('2048 bits'),
        '1024' => $this->t('1024 bits'),
      ],
    ];

    $form['miniorange_saml_idp_generate_certificate']['mo_saml_select_valid_days'] = [
      '#type' => 'select',
      '#title' => $this->t('Valid Days'),
      '#options' => [
        '365' => $this->t('365 days'),
        '180' => $this->t('180 days'),
        '90' => $this->t('90 days'),
        '45' => $this->t('45 days'),
        '30' => $this->t('30 days'),
        '15' => $this->t('15 days'),
        '7' => $this->t('7 days'),
      ],
    ];

    $form['miniorange_saml_idp_generate_certificate']['generate_config_elements'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Generate Self-Signed Certs'),
      '#disabled' => TRUE,
    ];

    $form['miniorange_saml_idp_generate_certificate']['clear_genrate_certificate_data'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Data'),
      '#disabled' => TRUE,
    ];

    return $form;
  }

  /**
   * Submit handler for the certificate generation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement submitForm() method.
  }

}
