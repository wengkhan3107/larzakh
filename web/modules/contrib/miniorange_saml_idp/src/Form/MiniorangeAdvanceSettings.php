<?php

namespace Drupal\miniorange_saml_idp\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml_idp\MiniorangeSamlIdpConstants;
use Drupal\miniorange_saml_idp\Utilities;

/**
 * Form for advanced SAML settings for the Miniorange SAML IdP module.
 */
class MiniorangeAdvanceSettings extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_saml_advance_settings';
  }

  /**
   * Builds the form for advanced SAML settings.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array with added elements for advanced settings.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();

    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          'miniorange_saml_idp/miniorange_saml_idp.admin',
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
    $idp_entity_id = $base_url . '/?q=admin/config/people/miniorange_saml_idp/';
    $idp_login_url = $base_url . '/initiatelogon';
    \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_entity_id_issuer', $idp_entity_id)->save();
    \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_login_url', $idp_login_url)->save();

    $form['markup_start'] = [
      '#markup' => t('<div class="mo_saml_sp_main_container"><div class="mo_saml_container">
                            <div class="mo_saml_idp_font_for_heading">Advance Settings</div><p style="clear: both"></p><hr/>'),
    ];

    /* Create container to hold @moSAMLAdvanseSettings form elements. */
    $form['mo_saml_idp_initiate_sso'] = [
      '#type' => 'details',
      '#title' => t('IdP Initiated Login'),
      '#open' => TRUE,
    ];

    $form['mo_saml_idp_initiate_sso']['Checkbox'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Identity Provider Initiated SSO &nbsp;&nbsp;&nbsp;&nbsp;') . '<a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/Licensing">[PREMIUM]</a>',
      '#disabled' => TRUE,
    ];

    $form['mo_saml_idp_initiate_sso']['mo_saml_idp_debugging_log'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable logging &nbsp;&nbsp;&nbsp;&nbsp;') . '<a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/Licensing">[PREMIUM]</a>',
      '#description' => t('Enabling this checkbox will add loggers under the <a target="_blank" href="@url">Reports</a> section', ['@url' => $base_url . '/admin/reports/dblog']),
      '#suffix' => '<br>',
      '#disabled' => TRUE,
    ];

    $form['mo_saml_idp_initiate_sso']['mo_saml_idp_logging'] = [
      '#type' => 'submit',
      '#value' => t('Save Configuration'),
      '#disabled' => TRUE,
      '#attributes' => ['class' => ['button button--primary']],
    ];

    /* Create container to hold @moSAMLAdvanseSettings form elements. */
    $form['mo_saml_import_export_configurations'] = [
      '#type' => 'details',
      '#title' => t('Import/Export configurations'),
      '#open' => TRUE,
    ];

    $form['mo_saml_import_export_configurations']['markup_top_2'] = [
      '#markup' => t('<br><br><div class="mo_saml_font_for_sub_heading">Export Configuration</div><hr/>
                    <div id="Exort_Configuration"><p>Click on the button below to download module configuration.</p>'),
    ];

    $entity_id = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id');
    $acs_url = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url');
    $disableButton = FALSE;
    if ($entity_id == NULL || $acs_url == NULL) {
      $disableButton = TRUE;
      $form['mo_saml_import_export_configurations']['markup_note'] = [
        '#markup' => t('<div class="mo_saml_configure_message"> Please <a href="@url">Configure module </a> first to export the configurations.</div><br>', ['@url' => $base_url . '/admin/config/people/miniorange_saml_idp/idp_setup']),
      ];
    }

    $form['mo_saml_import_export_configurations']['miniorange_saml_imo_option_exists_export'] = [
      '#type' => 'submit',
      '#value' => t('Download Configuration'),
      '#button_type' => 'primary',
      '#submit' => ['::miniorangeImportExport'],
      '#disabled' => $disableButton,
      '#suffix' => '<br/><br/></div><div id="Import_Configuration"><br/>',
    ];

    $form['mo_saml_import_export_configurations']['markup_prem_plan'] = [
      '#markup' => t('<br><div id="import_configurations">Import Configuration</div><hr><br><div class="mo_saml_highlight_background_note"><b>Note: </b>Available in
            <a href="@url">Premium</a> version of the module</div>', ['@url' => $base_url . MiniorangeSamlIdpConstants::LICENSE_PAGE_URL]),
    ];

    $form['mo_saml_import_export_configurations']['import_Config_file'] = [
      '#type' => 'file',
      '#disabled' => TRUE,
      '#prefix' => '<div class="container-inline">',
    ];

    $form['mo_saml_import_export_configurations']['miniorange_saml_idp_import'] = [
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#disabled' => TRUE,
      '#suffix' => '</div></div></div>',
    ];

    Utilities::advertise2fa($form, $form_state);

    return $form;
  }

  /**
   * Exports the configuration as a JSON file.
   */
  public function miniorangeImportExport() {
    $tab_class_name = Utilities::getClassNameForImportExport();
    $configuration_array = [];
    foreach ($tab_class_name as $key => $value) {
      $configuration_array[$key] = self::miniorangeGetConfigurationArray($value);
    }
    $configuration_array["Version_dependencies"] = self::miniorangeGetVersionInformations();
    header("Content-Disposition: attachment; filename = miniorange_saml_idp_config.json");
    echo(json_encode($configuration_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    exit;
  }

  /**
   * Retrieves configuration settings from the specified class.
   *
   * @param string $class_name
   *   The class name for which to retrieve the configuration.
   *
   * @return array
   *   The configuration array for the given class.
   */
  public function miniorangeGetConfigurationArray($class_name) {
    $class_object = Utilities::getVariableArrayForImportExport($class_name);
    $mo_array = [];
    foreach ($class_object as $key => $value) {
      $mo_option_exists = \Drupal::config('miniorange_saml_idp.settings')->get($value);
      if ($mo_option_exists) {
        $mo_array[$key] = $mo_option_exists;
      }
    }
    return $mo_array;
  }

  /**
   * Retrieves version information for the current environment.
   *
   * @return array
   *   An array containing version information for PHP, Drupal, and extensions.
   */
  public function miniorangeGetVersionInformations() {
    $array_version = [];
    $array_version["PHP_version"] = phpversion();
    $array_version["Drupal_version"] = Utilities::miniorangeGetDrupalCoreVersion();
    $array_version["OPEN_SSL"] = self::miniorangeSamlIsOpensslInstalled();
    $array_version["CURL"] = self::miniorangeSamlIsCurlInstalled();
    $array_version["ICONV"] = self::miniorangeSamlIsIconvInstalled();
    $array_version["DOM"] = self::miniorangeSamlIsDomInstalled();
    return $array_version;
  }

  /**
   * Checks if OpenSSL is installed on the server.
   *
   * @return int
   *   1 if OpenSSL is installed, otherwise 0.
   */
  public function miniorangeSamlIsOpensslInstalled() {
    if (in_array('openssl', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Checks if cURL is installed on the server.
   *
   * @return int
   *   1 if cURL is installed, otherwise 0.
   */
  public function miniorangeSamlIsCurlInstalled() {
    if (in_array('curl', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Checks if iconv is installed on the server.
   *
   * @return int
   *   1 if iconv is installed, otherwise 0.
   */
  public function miniorangeSamlIsIconvInstalled() {
    if (in_array('iconv', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Checks if the DOM extension is installed on the server.
   *
   * @return int
   *   1 if DOM is installed, otherwise 0.
   */
  public function miniorangeSamlIsDomInstalled() {
    if (in_array('dom', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
