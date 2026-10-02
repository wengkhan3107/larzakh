<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\miniorange_saml_idp\Utilities;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpConstants;

/**
 * Provides the Identity Provider setup form for the MiniOrange SAML module.
 */
class MiniorangeIdpSetup extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_idp_setup';
  }

  /**
   * Builds the form for the Identity Provider (IdP) setup.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $login_url = $base_url . '/initiatelogon';

    $form['miniorange_saml_SP_tab'] = [
      '#attached' => [
        'library' => [
          'miniorange_saml_idp/miniorange_saml_idp.admin',
          'miniorange_saml_idp/miniorange_saml_idp.test',
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    $id_req = \Drupal::request()->query->all();

    if (isset($id_req['delete']) && $id_req['delete'] == 'true') {
      $variables_and_values = [
        'miniorange_saml_idp_name',
        'miniorange_saml_idp_entity_id',
        'miniorange_saml_idp_acs_url',
        'miniorange_saml_idp_relay_state',
        'miniorange_saml_idp_nameid_format',
        'miniorange_saml_idp_assertion_signed',
      ];
      Utilities::miniorangeSetGetConfigurations($variables_and_values, 'CLEAR');
      $response = new RedirectResponse($base_url . '/admin/config/people/miniorange_saml_idp/idp_setup');
      \Drupal::messenger()->addMessage($this->t('Identity Provider configuration successfully deleted.'));
      $response->send();
    }

    $sp_name = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_name');
    $sp_entity = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id');
    $acs_url = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url');
    if (!(empty($sp_name) || empty($sp_entity) || empty($acs_url)) && empty($id_req)) {
      $form['metadata_1'] = [
        '#markup' => t('<div><div><div class="mo_saml_idp_font_for_heading">Service Provider Setup</div>&nbsp;&nbsp;<a target="_blank" class="button button--small" href="https://plugins.miniorange.com/drupal/setup-guides?category=saml-idp">Setup Guides</a>
                                  <p style="clear: both"></p>'),
      ];

      $form['mo_saml_sp_list'] = [
        '#type' => 'fieldset',
      ];

      $form['mo_saml_sp_list']['mo_saml_add_sp'] = [
        '#markup' => '<a data-dialog-type="modal"  href="add_new_sp" class="use-ajax button button--primary add_new_provider">+ Add New SP</a>',
      ];

      $header = [
        'sp_name' => [
          'data' => t('SP Name'),
        ],
        'issuer' => [
          'data' => t('SP Entity-ID/Issuer'),
        ],
        'test' => [
          'data' => t('Test'),
        ],
        'action' => [
          'data' => t('Action'),
        ],
      ];

      $sp_name   = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_name');
      $sp_issuer = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id');

      $form['mo_saml_sp_list']['mo_saml_sp_list_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#suffix' => '</div>',
      ];

      $form['mo_saml_sp_list']['mo_saml_sp_list_table'][0]['sp_name'] = [
        '#markup' => $sp_name,
      ];

      $form['mo_saml_sp_list']['mo_saml_sp_list_table'][0]['sp_issuer_name'] = [
        '#markup' => $sp_issuer,
      ];

      $form['mo_saml_sp_list']['mo_saml_sp_list_table'][0]['test_configuration'] = [
        '#markup' => Markup::create('<a style="cursor: pointer;" id="testConfigButton">Test</a>'),
      ];

      $form['mo_saml_sp_list']['mo_saml_sp_list_table'][0]['dropbutton'] = [
        '#type' => 'dropbutton',
        '#dropbutton_type' => 'extrasmall',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('miniorange_saml_idp.idp_setup',[],['query' => ['edit' => 'true']]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('miniorange_saml_idp.confirm_delete_sp'),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => json_encode([
                'width' => 700,
              ]),
            ],
          ],
        ],
      ];

      return $form;
    }

    $form['metadata_1'] = [
      '#markup' => t('<div class="mo_saml_sp_main_container"><div class="mo_saml_container mo_saml_container"><div class="mo_saml_idp_font_for_heading">Service Provider Setup</div>
                                <p style="clear: both"></p><hr><br><div class="mo_saml_font_idp_setup_for_heading">Enter the information gathered from your Service Provider</div>'),
    ];
    [$statusCode, $effectiveUrl] = Utilities::GetURL($login_url);
    if ($effectiveUrl !== $login_url) {
      $form['markup_reg_msg'] = [
        '#markup' => '<div class="mo_saml_register_message">You need to make the <a href="' . $login_url . '">' . $login_url . '</a> anonymously accessible.</div>',
      ];
    }

    /* Create container to hold @moSAMLSPSetup form elements. */
    $form['mo_saml_SP_setup'] = [
      '#type' => 'details',
      '#title' => t('Upload SP Metadata'),
          // '#open' => TRUE,
    ];

    $form['mo_saml_SP_setup']['metadata_file'] = [
      '#type' => 'file',
      '#title' => 'Upload a metadata file',
      '#description' => $this->t('For example: %filename from your local computer.', ['%filename' => 'metadata.xml']),
      '#attributes' => ['accept' => 'application/xml'],
    ];

    $form['mo_saml_SP_setup']['separator'] = [
      '#prefix' => '<strong>',
      '#markup' => $this->t('Or'),
      '#suffix' => '</strong>',
    ];

    $form['mo_saml_SP_setup']['metadata_URL'] = [
      '#type' => 'url',
      '#title' => $this->t('Add from a URL'),
      '#placeholder' => $this->t('Enter metadata URL of your SP.'),
      '#maxlength' => NULL,
      '#description' => $this->t('For example: %url', ['%url' => 'https://example.com/metadata']),
    ];

    $form['mo_saml_SP_setup']['metadata_fetch'] = [
      '#type' => 'submit',
      '#value' => t('Fetch Metadata'),
      '#button_type' => 'primary',
      '#name' => 'metadata_fetch',
      '#limit_validation_errors' => [['metadata_file'], ['metadata_URL']],
      '#submit' => ['::miniorangeSamlFetchMetadata'],
      '#validate' => ['::validateMetadataInput'],
    ];

    /* Create container to hold @IdentityProviderSetup form elements. */
    $form['mo_saml_identity_provider_metadata'] = [
      '#type' => 'fieldset',
          // '#title' => t('Service Provider Metadata'),
      '#attributes' => ['style' => 'padding:2% 2% 5%; margin-bottom:2%'],

    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_name'] = [
      '#type' => 'textfield',
      '#title' => t('Service Provider Name'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_name'),
      '#attributes' => [
        'style' => 'width:90%',
        'placeholder' => t('Enter Service Provider Name'),
      ],
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_entity_id'] = [
      '#type' => 'textfield',
      '#title' => t('SP Entity ID or Issuer'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id'),
      '#attributes' => ['style' => 'width:90%', 'placeholder' => t('Enter SP Entity ID or Issuer')],
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_acs_url'] = [
      '#type' => 'url',
      '#title' => t('ACS URL'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url'),
      '#attributes' => ['style' => 'width:90%', 'placeholder' => t('Enter ACS URL')],

    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_nameid_format'] = [
      '#type' => 'select',
      '#title' => t('NameID Format:'),
      '#options' => [
        '1.1:nameid-format:emailAddress' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
        '1.1:nameid-format:unspecified' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'),
        '2.0:nameid-format:transient' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:transient'),
        '2.0:nameid-format:persistent' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:persistent'),
      ],
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_nameid_format'),
      '#attributes' => ['style' => 'width:90%;'],
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_relay_state'] = [
      '#type' => 'textfield',
      '#title' => t('Relay State'),
      '#description' => t('It specifes the page at the service provider where users are redirected once SSO completes.'),
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_relay_state'),
      '#attributes' => ['style' => 'width:90%', 'placeholder' => t('Enter Relay State (optional)')],
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_single_logout_url'] = [
      '#type' => 'textfield',
      '#title' => t('Single Logout URL (optional):<a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '"> [Premium]</a>'),
      '#attributes' => ['style' => 'width:90%', 'placeholder' => t('Enter Single Logout URL')],
      '#disabled' => TRUE,
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_assertion_signed'] = [
      '#type' => 'checkbox',
      '#title' => t('<b>Sign SAML Assertion</b>'),
      '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_assertion_signed'),
      '#prefix' => '<div id="assertion_signed">',
      '#suffix' => '</div>',
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_x509_certificate_request'] = [
      '#type' => 'textarea',
      '#title' => t('X.509 certificate for signed request <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">[Premium]</a>'),
      '#cols' => '10',
      '#rows' => '5',
      '#attributes' => [
        'style' => 'width:90%',
        'placeholder' => '-----BEGIN CERTIFICATE-----' . PHP_EOL . 'XXXXXXXXXXXXXXXXXXXXXXXXXXX' . PHP_EOL . '-----END CERTIFICATE-----',
      ],
      '#disabled' => TRUE,
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_x509_certificate_assertion'] = [
      '#type' => 'textarea',
      '#title' => t('X.509 certificate for encrypted assertion <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '"> [Premium]</a>'),
      '#cols' => '10',
      '#rows' => '5',
      '#attributes' => [
        'style' => 'width:90%',
        'placeholder' => '-----BEGIN CERTIFICATE-----' . PHP_EOL . 'XXXXXXXXXXXXXXXXXXXXXXXXXXX' . PHP_EOL . '-----END CERTIFICATE-----',
      ],
      '#disabled' => TRUE,
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_response_signed'] = [
      '#type' => 'checkbox',
      '#title' => t('Sign SAML Response:'),
      '#disabled' => TRUE,
      '#description' => t('This feature is available in the <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">Premium</a> version of the module.'),
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_encrypt_signed'] = [
      '#type' => 'checkbox',
      '#title' => t('Encrypt SAML Assertion:'),
      '#disabled' => TRUE,
      '#description' => t('This feature is available in the <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">Premium</a> version of the module.'),
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_config_submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Save Configuration'),
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_test_config_button'] = [
      '#type' => 'button',
      '#value' => t('Test Configuration'),
      '#attributes' => ['id' => 'testConfigButton'],
    ];

    $entity_id = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id');
    $acs_url = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url');
    $disableButton = FALSE;
    if ($entity_id == NULL || $acs_url == NULL) {
      $disableButton = TRUE;
    }

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_config_delete'] = [
      '#type' => 'submit',
      '#value' => t('Delete Configuration'),
      '#submit' => ['::miniorangeSamlIdpDeleteIdpConfig'],
      '#button_type' => 'danger',
      '#disabled' => $disableButton,
      '#suffix' => '<br><br></div>',
    ];

    Utilities::spConfigGuide($form, $form_state);

    return $form;
  }

  /**
   * Get the test URL for IDP configuration.
   *
   * @return string
   *   The URL to test the configuration.
   */
  public function getTestUrl() {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $testUrl = $base_url . '/?q=idpTestConfig';
    return $testUrl;
  }

  /**
   * Validate the metadata input (either file or URL).
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateMetadataInput(array &$form, FormStateInterface $form_state) {
    $all_files    = $this->getRequest()->files->get('files', []);
    $metadataUrl  = $form_state->getValue('metadata_URL');
    $metadataFile = $all_files['metadata_file'];

    if (\Drupal::moduleHandler()->moduleExists('file')) {
      if (!($metadataFile xor $metadataUrl)) {
        $form_state->setErrorByName('metadata_file', $this->t('You must either provide a URL or upload a metadata file.'));
      }
    }
    else {
      if (!($form_state->getValue('metadata_URL'))) {
        $form_state->setErrorByName('metadata_URL', $this->t('You must provide a metadata URL.'));
      }
    }
  }

  /**
   * This function handles the form submission.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $base_url    = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $utilities   = new Utilities();
    $form_values = $form_state->getValues();
    if (empty($form_values['miniorange_saml_idp_name']) || empty($form_values['miniorange_saml_idp_entity_id']) || empty($form_values['miniorange_saml_idp_acs_url'])) {
      \Drupal::messenger()->addMessage(t('The <b>Service Provider Name, SP Entity ID or Issuer, ACS URL</b> field is required.'), 'error');
      return;
    }
    $variables_and_values = [
      'miniorange_saml_idp_name'             => $form_values['miniorange_saml_idp_name'],
      'miniorange_saml_idp_entity_id'        => str_replace(' ', '', $form_values['miniorange_saml_idp_entity_id']),
      'miniorange_saml_idp_acs_url'          => str_replace(' ', '', $form_values['miniorange_saml_idp_acs_url']),
      'miniorange_saml_idp_relay_state'      => $form_values['miniorange_saml_idp_relay_state'],
      'miniorange_saml_idp_nameid_format'    => $form_values['miniorange_saml_idp_nameid_format'],
      'miniorange_saml_idp_assertion_signed' => $form_values['miniorange_saml_idp_assertion_signed'] == 1 ? TRUE : FALSE,
    ];

    $utilities->miniorangeSetGetConfigurations($variables_and_values, 'SET');
    \Drupal::messenger()->addMessage(t('Your Service Provider configuration are successfully saved. You can click on Test Configuration button/link below to test these configurations.'));
    $response = new RedirectResponse($base_url . '/admin/config/people/miniorange_saml_idp/idp_setup');
    $response->send();
  }

  /**
   * Delete the IDP configuration.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function miniorangeSamlIdpDeleteIdpConfig(array &$form, FormStateInterface $form_state) {
    $variables_and_values = [
      'miniorange_saml_idp_name',
      'miniorange_saml_idp_entity_id',
      'miniorange_saml_idp_acs_url',
      'miniorange_saml_idp_relay_state',
      'miniorange_saml_idp_nameid_format',
      'miniorange_saml_idp_assertion_signed',
    ];
    Utilities::miniorangeSetGetConfigurations($variables_and_values, 'CLEAR');
    \Drupal::messenger()->addMessage(t('Service Provider configuration successfully deleted.'));
  }

  /**
   * Fetch and upload SP metadata from a file or URL.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function miniorangeSamlFetchMetadata(array &$form, FormStateInterface $form_state) {
    $all_files    = $this->getRequest()->files->get('files', []);
    $metadataUrl  = $form_state->getValue('metadata_URL');
    $metadataFile = $all_files['metadata_file'];
    $filePath     = isset($metadataFile) ? $metadataFile->getRealPath() : $metadataUrl;
    $file         = file_get_contents($filePath);

    if (empty($file)) {
      $form_state->setRebuild();
      \Drupal::messenger()->addError('Enable to fetch the metadata. Please provide a valid metadata file/URL.');
      return $form;
    }

    Utilities::uploadMetadata($file);
  }

}
