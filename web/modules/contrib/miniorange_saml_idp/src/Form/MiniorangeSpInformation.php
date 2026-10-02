<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpConstants;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the sp information for the miniOrange SAML Identity Provider.
 */
class MiniorangeSpInformation extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_sp_setup';
  }
  /**
   * The configuration object for the SAML IDP settings.
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('miniorange_saml_idp.settings');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Builds the form for configuring the Service Provider (SP) information.
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
    $login_url = $base_url . '/initiatelogon';
    $issuer = $base_url . '/?q=admin/config/people/miniorange_saml_idp/';
    $issuer_id = !empty($issuer_id) ? $issuer_id : $issuer;

    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          'miniorange_saml_idp/miniorange_saml_idp.admin',
          'miniorange_saml_idp/miniorange_saml_idp_copy.icon',
          'core/drupal.dialog.ajax',
        ],
      ],
    ];

    [$statusCode, $effectiveUrl] = Utilities::GetURL($login_url);
    if ($effectiveUrl !== $login_url) {
      $form['markup_reg_msg'] = [
        '#markup' => '<div class="mo_saml_register_message">You need to make the <a href="' . $login_url . '">' . $login_url . '</a> anonymously accessible.</div>',
      ];
    }

    /* Create container to hold @IdentityProviderMetadata form elements. */
    $form['mo_saml_identity_provider_metadata'] = [
      '#type' => 'container',
          // '#title' => t('Identity Provider Metadata'),
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_saml_idp_div_st'] = [
      '#markup' => t('<div class="mo_saml_idp_font_for_heading">Identity Provider Metadata</div>&nbsp;&nbsp;<a class="button button--small" target="_blank" href="https://faq.miniorange.com/kb/drupal/saml-drupal/" >FAQs</a><a class="button button--small" target="_blank" href="https://plugins.miniorange.com/drupal/setup-guides?category=saml-idp">Setup Guides</a>
                                <p style="clear: both"></p><hr><br>'),
    ];

    $form['mo_saml_identity_provider_metadata']['mo_saml_metadata_option'] = [
      '#markup' => t('<div class="mo_saml_font_idp_setup_for_heading">Provide this metadata to your Service Provider. You can choose any one of the options below.</div>
                             <br>'),
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_oauth_client_name_attr_title'] = [
      '#markup' => '<div class="container-inline"><b>a) Metadata URL: &nbsp;</b><span id="saml_idp_metadeta_url"><code><a id="metadata_link" href="' . $base_url . '/moidp_metadata"><strong>' . $base_url . '/moidp_metadata</strong></a></code></span>',
    ];

    $form['mo_saml_identity_provider_metadata']['miniorange_oauth_client_callback_url'] = [
      '#prefix' => '<div class= "mo_idp_mo_saml_highlight_background_url_not">',
      '#suffix' => '</div>&nbsp;&nbsp;',
    ];

    $form['mo_saml_identity_provider_metadata']['test'] = [
      '#value' => t('&#128461; Copy'),
      '#type' => 'submit',
      '#id' => 'copy_button',
      '#attributes' => ['onclick' => 'CopyToClipboard(saml_idp_metadeta_url)', 'class' => ['use-ajax button--small']],
      '#ajax' => [
        'event' => 'click',
        'progress' => [
          'message' => NULL,
        ],
      ],
      '#suffix' => '<span><a href="' . $base_url . '/moidp_metadata_download" class="button button--small button--primary">Download Metadata</a></span></div><br>',
    ];

    $form['mo_saml_identity_provider_metadata']['mo_saml_download_btn_title'] = [
      '#markup' => t('<div><b>b) Provide the following information to your Service Provider</b><br></div>'),
    ];

    $idp_Entity      = $issuer_id;
    $saml_login_url  = $login_url;
    $saml_logout_url = 'Available in <a href="' . $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL . '">Premium</a> version.';
    $certificate     = '<a href="' . $base_url . '/moidp_certificate_download">Click Here</a> to download X509 certificate.';

    $mo_table_content = [
      'IDP Entity ID or Issuer' => $idp_Entity,
      'SAML Login URL' => $saml_login_url,
      'SAML Logout URL' => $saml_logout_url,
      'X.509 Certificate' => $certificate,
      'Custom X.509 Certificate' => 'Click <a class="use-ajax"  data-dialog-type = "modal"  data-ajax-progress="fullscreen" data-dialog-options="{&quot;width&quot;:&quot;80%&quot;}" href="' . $base_url . '/admin/config/people/miniorange_saml_idp/generate_certificate">here</a> to generate custom certificate',
    ];

    $form['mo_saml_identity_provider_metadata']['mo_dfg_saml_attrs_list_idp'] = [
      '#type' => 'table',
      '#header' => ['ATTRIBUTE', 'VALUE' , ''],
      '#empty' => t('Something is not right. Please run the update script or contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>'),
      '#responsive' => TRUE,
      '#sticky' => TRUE,
      '#size' => 2,
    ];

    foreach ($mo_table_content as $key => $value) {
      $row = self::miniorangeSamlIdpMetadataTable($key, $value);
      $form['mo_saml_identity_provider_metadata']['mo_dfg_saml_attrs_list_idp'][$key] = $row;
    }

    return $form;
  }

  /**
   * Helper function to generate table rows for the metadata table.
   *
   * @param string $attr_name
   *   The attribute name for the row.
   * @param string $attr_value
   *   The value for the attribute.
   *
   * @return array
   *   The row of the table with attribute name, value, and an optional button.
   */
  public function miniorangeSamlIdpMetadataTable($attr_name, $attr_value) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $certData = openssl_x509_parse($this->config->get('miniorange_saml_idp_x509_certificate'));
    $expiryTimestamp = $certData['validTo_time_t'];
    $interval = $expiryTimestamp > time() ? \Drupal::service('date.formatter')->formatTimeDiffUntil($expiryTimestamp) .  ' remaining': 'Expired';

    $row[$attr_name] = [
      '#markup' => '<div class="container-inline"><strong>' . $attr_name . '</strong>',
    ];

    if ($attr_name == 'IDP Entity ID or Issuer' || $attr_name == 'SAML Login URL') {
      $row[$attr_value] = [
        '#markup' => '<span id="copy_' . str_replace(' ', '_', $attr_name) . '">' . $attr_value . '</span>',
      ];

      $row['copy_' . $attr_name] = [
        '#value' => t('&#128461; Copy'),
        '#type' => 'button',
        '#id' => 'copy_button_' . str_replace(' ', '_', $attr_name),
        '#attributes' => [
          'onclick' => 'CopyMetaToClipboard(copy_' . str_replace(' ', '_', $attr_name) . ')',
          'class' => [
            'use-ajax button--small',
          ],
        ],
        '#ajax' => [
          'event' => 'click',
          'progress' => [
            'message' => NULL,
          ],
        ],
        '#suffix' => '</div>',
      ];
    }
    elseif ($attr_name == 'X.509 Certificate') {
      $row[$attr_value] = [
        '#type'   => 'textarea',
        '#value'  => $this->config->get('miniorange_saml_idp_x509_certificate'),
        '#id'     => 'certificate-textarea',
        '#description' => $this->t('Expires on %date (%interval)', ['%interval' => $interval, '%date' => date('Y-m-d', $expiryTimestamp) ] ),
        '#suffix' => $this->t('You can also <a data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal"  class="use-ajax" href=":url">generate a new certificate</a>.', [':url' => Url::fromRoute('miniorange_saml_idp.confirm_generate_certificate')->toString()]),
      ];

      $row['copy_' . $attr_name] = [
        '#markup' => '',
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '<span class="button mo_copy_certificate button--small">&#128461; Copy</span><a class="button button--small" href="' . $base_url . '/moidp_certificate_download">Download</a></div>',
      ];
    }
    else {
      $row[$attr_value] = [
        '#markup' => $attr_value,
      ];
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
