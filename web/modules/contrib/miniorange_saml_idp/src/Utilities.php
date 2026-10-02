<?php

namespace Drupal\miniorange_saml_idp;

use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use InvalidArgumentException;

/**
 * This file is part of miniOrange Drupal SAML IDP module.
 *
 * @package miniOrange
 * @subpackage Plugins
 * @license GNU/GPLv3
 * @copyright Copyright 2015 miniOrange. All Rights Reserved.
 *
 * miniOrange Drupal SAML IDP module is free software:
 * you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * miniOrange Drupal IDP module is distributed in the hope
 * that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General
 * Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with miniOrange SAML module. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class representing Utilities.
 */
class Utilities {

  /**
   * Retrieves the HTTP status code and effective URL for the given URL.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return array
   *   The HTTP status code and effective URL.
   */
  public static function getUrl($url) {
    if (self::isCurlInstalled()) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_exec($ch);
      $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      return [$code, $effectiveUrl];
    }
    return [200, $url];

  }

  /**
   * Handles all database variable calls such as SET, GET, and CLEAR.
   *
   * @param array $variable_array
   *   The array containing variables and values.
   *   - For SET: array(variable_name1 => value, variable_name2 => value).
   *   - For GET and CLEAR: array(variable_name1, variable_name2).
   *    Note: Keys do not matter here.
   * @param string $mo_method
   *   The operation to perform. Accepted values are:
   *   - 'SET': Set the variables and their values.
   *   - 'GET': Get the values of the variables.
   *   - 'CLEAR': Clear the variables.
   *
   * @return array|void
   *   - Returns an array of variable names and their values for 'GET'.
   *   - Returns void for 'SET' and 'CLEAR'.
   */
  public static function miniorangeSetGetConfigurations($variable_array, $mo_method) {
    if ($mo_method === 'GET') {
      $variables_and_values = [];
      $miniOrange_config = \Drupal::config('miniorange_saml_idp.settings');
      foreach ($variable_array as $variable => $value) {
        $variables_and_values[$value] = $miniOrange_config->get($value);
      }
      return $variables_and_values;
    }
    $configFactory = \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings');
    if ($mo_method === 'SET') {
      foreach ($variable_array as $variable => $value) {
        $configFactory->set($variable, $value)->save();
      }
      return;
    }
    foreach ($variable_array as $variable => $value) {
      $configFactory->clear($value)->save();
    }
  }

  /**
   * Generates a list of Service Provider setup guides.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function spConfigGuide(array &$form, FormStateInterface $form_state) {

    $form['miniorange_idp_guide_link1'] = [
      '#markup' => '<div class="mo_saml_container_2">',
    ];

    // Some links are commented out. Uncomment them if necessary in the future.
    $spList = [
      'Learnworlds' => 'https://www.drupal.org/docs/contributed-modules/saml-idp-20-single-sign-on-sso-saml-identity-provider/learnworlds-sso-login',
      'Learnupon' => 'https://www.drupal.org/docs/contributed-modules/saml-idp-20-single-sign-on-sso-saml-identity-provider/learnupon-sso-login',
      'Wordpress' => 'https://plugins.miniorange.com/wordpress-sso-login-with-drupal-idp',
      'Shopify' => 'https://plugins.miniorange.com/shopify-saml-single-sign-on-sso-with-drupal',
      'Drupal' => 'https://plugins.miniorange.com/configure-saml-sso-between-two-drupal-sites',
      'Moodle' => 'https://plugins.miniorange.com/moodle-sso-integration-with-drupal-saml-idp-module',
      'Tableau Online' => 'https://plugins.miniorange.com/tableau-online-saml-single-sign-on-with-drupal',
      'Tableau Server' => 'https://plugins.miniorange.com/tableau-server-saml-single-sign-on-with-drupal',
      'Jira' => 'https://plugins.miniorange.com/jira-saml-single-sign-on-with-drupal',
      'AWS' => 'https://plugins.miniorange.com/aws-sso-login-using-drupal-idp',
      'AWS Cognito' => 'https://plugins.miniorange.com/amazon-cognito-sso-login-using-drupal-idp',
      'Salesforce' => 'https://plugins.miniorange.com/salesforce-single-sign-on-sso-for-drupal',
      'Rocketchat' => 'https://plugins.miniorange.com/rocketchat-sso-for-drupal-rocketchat-sso-login-using-drupal-idp',
      'Other SP' => 'https://plugins.miniorange.com/drupal/setup-guides?category=saml-idp',
    // 'Freshdesk' => 'https://plugins.miniorange.com/freshdesk-saml-single-sign-on-sso-integration-with-drupal-as-idp',
    // 'Wordpress' => 'https://plugins.miniorange.com/wordpress-sso-login-with-drupal-idp',
    // 'Zoom'=> 'https://plugins.miniorange.com/zoom-sso-login-using-drupal-idp',
    // 'Jira' => 'https://plugins.miniorange.com/jira-saml-single-sign-on-with-drupal',
    // 'Drupal (as SP)' => 'https://plugins.miniorange.com/configure-saml-sso-between-two-drupal-sites',
    // 'Joomla' => 'https://plugins.miniorange.com/joomla-saml-single-sign-on-sso-with-drupal',
    // 'Powerschool' => 'https://plugins.miniorange.com/powerschool-saml-single-sign-on-with-drupal',
    // 'Talent LMS' => 'https://plugins.miniorange.com/talentlms-single-sign-on-sso-using-drupal-as-idp',
    // 'Rocketchat' => 'https://plugins.miniorange.com/rocketchat-sso-for-drupal-rocketchat-sso-login-using-drupal-idp',
    // 'Klipfolio' => 'https://plugins.miniorange.com/klipfolio-sso-login-using-drupal-idp',
    // 'Panopto' => 'https://plugins.miniorange.com/panopto-sso-for-drupal-panopto-sso-login-using-drupal-idp',
    // 'Adobe Captive Prime' => 'https://plugins.miniorange.com/adobe-captivate-saml-single-sign-on-with-drupal',
    // 'ClicData' => 'https://plugins.miniorange.com/clicdata-saml-single-sign-on-with-drupal',
    // 'Minecast' => 'https://plugins.miniorange.com/mimecast-saml-single-sign-on-with-drupal',
    // 'Tableau Online' => 'https://plugins.miniorange.com/tableau-online-saml-single-sign-on-with-drupal',
    // 'Shopify' => 'https://plugins.miniorange.com/shopify-saml-single-sign-on-sso-with-drupal'
    ];

    $iterationCount = 0;
    $tableRows = [];

    foreach ($spList as $spName => $guideLink) {
      $tempArray[] = Markup::create('<strong><a class="mo_guide_text-color" href="' . $guideLink . '" target="_blank">' . $spName . '</a></strong>');
      $iterationCount++;
      if ($iterationCount % 2 == 0) {
        $tableRows[] = $tempArray;
        $tempArray = [];
      }
    }

    $header = [
      [
        'data' => t('Service Provider Setup Guides'),
        'colspan' => 2,
        'style' => 'text-align: center;',
      ],
    ];

    $form['modules'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $tableRows,
      '#responsive' => TRUE,
    ];

    self::faq($form, $form_state);

    $form['miniorange_idp_guide_link_end'] = [
      '#markup' => '</div>',
    ];
  }

  /**
   * Adds FAQ and forum links to the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function faq(&$form, &$form_state) {

    $form['miniorange_faq'] = [
      '#markup' => '<br><div class="mo_saml_text_center"><b></b>
                          <a class="button button--small" href="https://faq.miniorange.com/kb/drupal/saml-drupal/" target="_blank">FAQs</a>
                                    <b></b><a class="button button--small" href="https://forum.miniorange.com/" target="_blank">Ask questions on forum</a></div><br>',
    ];
  }

  /**
   * Displays advertisement for enabling 2FA/MFA on Drupal login.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form array.
   */
  public static function advertise2fa(&$form, &$form_state) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $form['miniorange_idp_guide_link3'] = [
      '#markup' => '<div class="mo_saml_container_2">',
    ];

    $form['mo_idp_net_adv'] = [
      '#markup' => t('<form name="f1">
            <table id="idp_support" class="idp-table" style="border: none;">
            <h5>Secure your Drupal login using 2FA/MFA</h5>
                <tr class="mo_ns_row">
                    <th class="mo_ns_image1"><img
                                src="' . $base_url . '/' . self::moGetModulePath() . '/images/second_factor_autentication.jpg"
                                alt="miniOrange icon" height=12% width=55%>
                    <br>
                            <h4>Drupal Second-Factor Authentication</h4>
                    </th>
                </tr>

                <tr class="mo_ns_row">
                    <td class="mo_ns_align">
                        Two Factor Authentication (2FA) module adds a second layer of authentication at the time of login to secure your Drupal accounts.
                        It is a highly secure and easy to setup module which protects your site from hacks and unauthorized login attempts.
                        Also Two Factor Authentication (2FA) module provides 17+ 2fa methods including OTP Over SMS, Email, Phone call, WhatsApp, Google Authenticator, etc.
                    </td>
                </tr>
                <tr class="mo_ns_row">
                    <td class="mo_ns_td"><br> <a class="button button--small" href="https://www.drupal.org/project/miniorange_2fa" target="_blank">Download Module</a>
                              <b></b><a class="button button--small" href="https://plugins.miniorange.com/drupal-two-factor-authentication-2fa" target="_blank">Know More</a>
                    </td>
                </tr>
            </table>
        </form>'),
    ];
    return $form;
  }

  /**
   * Replacement of deprecated function drupal_get_path()
   *
   * @return Modules
   *   path
   */
  public static function moGetModulePath() {
    return \Drupal::service('extension.list.module')->getPath('miniorange_saml_idp');
  }

  /**
   * Checks if the customer is registered by verifying required settings.
   *
   * @return bool
   *   TRUE if customer is registered, FALSE otherwise.
   */
  public static function isCustomerRegistered() {
    if (\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_customer_admin_email') == NULL
          || \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_id') == NULL
          || \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_admin_token') == NULL
          || \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_customer_api_key') == NULL) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Verifies and saves customer details, handling redirection or errors.
   *
   * @param string $username
   *   The customer's username.
   * @param string $phone
   *   The customer's phone number.
   * @param string $password
   *   The customer's password.
   * @param bool $login
   *   Whether login is required. Default is FALSE.
   * @param bool $called_from_popup
   *   Whether called from a popup. Default is FALSE.
   * @param string|null $payment_plan
   *   Optional payment plan. Default is NULL.
   */
  public static function customerSetupSubmit($username, $phone, $password, $login = FALSE, $called_from_popup = FALSE, $payment_plan = NULL) {
    $customer_config = new MiniorangeSamlCustomer($username, $phone, $password, NULL);
    $check_customer_response = json_decode($customer_config->checkCustomer());
    $db_config = \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings');

    if (isset($check_customer_response->status) && $check_customer_response->status == 'CUSTOMER_NOT_FOUND') {
      \Drupal::messenger()->addMessage(t('The account with username :username does not exist.', [':username' => $username]), 'error');
    }
    elseif (isset($check_customer_response->status) && $check_customer_response->status == 'SUCCESS') {
      $customer_info = $customer_config->getCustomerKeys();
      $customer_keys_response = json_decode($customer_info);

      if (isset($customer_info) && $customer_info == MiniorangeSamlIdpConstants::API_INVALID_CRED_RESPONSE) {
        \Drupal::messenger()->addMessage(t('@message', ['@message' => MiniorangeSamlIdpConstants::API_INVALID_CRED_RESPONSE]), 'error');
        return;
      }

      if (isset($customer_keys_response->status) && ($customer_keys_response->status == 'SUCCESS') && json_last_error() == JSON_ERROR_NONE) {
        $db_config->set('miniorange_saml_customer_id', $customer_keys_response->id)
          ->set('miniorange_saml_customer_admin_token', $customer_keys_response->token)
          ->set('miniorange_saml_idp_customer_admin_email', $username)
          ->set('miniorange_saml_customer_admin_phone', $phone)
          ->set('miniorange_saml_customer_api_key', $customer_keys_response->apiKey)
          ->set('miniorange_saml_status', 'PLUGIN_CONFIGURATION')->save();

        \Drupal::messenger()->addMessage(t('Successfully retrieved your account.'));
        $redirect_url = Url::fromRoute('miniorange_saml_idp.customer_setup')->toString();
        $response = new RedirectResponse($redirect_url);
        $response->send();
      }
    }
    \Drupal::messenger()->addMessage(t('@message', ['@message' => MiniorangeSamlIdpConstants::API_NO_RESPONSE]), 'error');
  }

  /**
   * Returns class names for import/export mappings.
   *
   * @return array
   *   Associative array of class names for different mapping types.
   */
  public static function getClassNameForImportExport() {
    $tab_class_name = [
      "Identity_Provider" => 'mo_options_enum_identity_provider',
      "Service_Provider"  => 'mo_options_enum_service_provider',
      "Attribute_Mapping"  => 'mo_options_enum_attribute_mapping',
    ];
    return $tab_class_name;
  }

  /**
   * Returns a variable-to-config key mapping based on the class name.
   *
   * @param string $class_name
   *   The name of the class to get the configuration mapping for.
   *
   * @return array
   *   The mapping of variable names to configuration keys.
   */
  public static function getVariableArrayForImportExport($class_name) {
    if ($class_name == "mo_options_enum_identity_provider") {
      $class_object = [
        'IdP_Entity_ID' => 'miniorange_saml_idp_entity_id_issuer',
        'IdP_Login_Url' => 'miniorange_saml_idp_login_url',
      ];
    }
    elseif ($class_name == "mo_options_enum_service_provider") {
      $class_object = [
        'Sevice_Provider_name' => 'miniorange_saml_idp_name',
        'SP_Entity_ID_or_Issuer' => 'miniorange_saml_idp_entity_id',
        'Name_ID_format' => 'miniorange_saml_idp_nameid_format',
        'ACS_URL' => 'miniorange_saml_idp_acs_url',
        'Relay_State' => 'miniorange_saml_idp_relay_state',
        'Assertion_Signed' => 'miniorange_saml_idp_assertion_signed',
      ];
    }
    elseif ($class_name == "mo_options_enum_attribute_mapping") {
      $class_object = [
        'NameID_Attribute' => 'miniorange_saml_idp_nameid_attr_map',
      ];
    }
    return $class_object;
  }

  /**
   * Handles uploading and processing metadata.
   *
   * @param string $file
   *   The XML metadata file content.
   */
  public static function uploadMetadata($file) {

    if (empty(\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_name'))) {
      \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_name', 'Service Provider')->save();
    }
    $document = new \DOMDocument();

    // Enable libxml error handling.
    libxml_use_internal_errors(TRUE);

    if (!$document->loadXML($file)) {
      libxml_clear_errors();
      \Drupal::messenger()->addError('The metadata is not in the correct XML format. Please provide a valid metadata file/URL.');
      return;
    }

    // Reset libxml error handling.
    libxml_use_internal_errors(FALSE);

    $first_child = $document->firstChild;
    if (!empty($first_child)) {
      $metadata = new MetadataReader($document);
      $service_providers = $metadata->getServiceProviders();
      if (empty($service_providers)) {
        \Drupal::messenger()->addMessage(t('Please provide a valid metadata'), 'error');
        return;
      }
      foreach ($service_providers as $key => $sp) {
        $entityID_issuer = $sp->getEntityId();
        $acs_url = $sp->getAcsUrl();
        $is_assertion_signed = $sp->getAssertionsSigned() == 'true';

        \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_entity_id', $entityID_issuer)->save();
        \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_acs_url', $acs_url)->save();
        \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_assertion_signed', $is_assertion_signed)->save();
      }
      \Drupal::messenger()->addMessage(t('Service Provider configuration successfully saved.'));
    }
    else {
      \Drupal::messenger()->addMessage(t('Please provide a valid metadata'), 'error');
    }
  }

  /**
   * Checks if cURL is installed on the server.
   *
   * @return int
   *   1 if cURL is installed, 0 otherwise.
   */
  public static function isCurlInstalled() {
    if (in_array('curl', get_loaded_extensions())) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * Generates a unique ID by converting random bytes to a hexadecimal string.
   *
   * @return string
   *   The generated unique ID.
   */
  public static function generateId() {
    return '_' . self::stringToHex(self::generateRandomBytes(21));
  }

  /**
   * Converts a string of bytes to a hexadecimal string.
   *
   * @param string $bytes
   *   The string of bytes to be converted.
   *
   * @return string
   *   The hexadecimal representation of the input bytes.
   */
  public static function stringToHex($bytes) {
    $ret = '';
    for ($i = 0; $i < strlen($bytes); $i++) {
      $ret .= sprintf('%02x', ord($bytes[$i]));
    }
    return $ret;
  }

  /**
   * Generates a specified length of random bytes.
   *
   * @param int $length
   *   The length of random bytes to generate.
   * @param bool $fallback
   *   Fallback method if OpenSSL is unavailable (default: TRUE).
   *
   * @return string
   *   The generated random bytes.
   */
  public static function generateRandomBytes($length, $fallback = TRUE) {
    if (!is_int($length) || $length <= 0) {
        throw new InvalidArgumentException('The length must be a positive integer.');
    }
    return openssl_random_pseudo_bytes($length);
  }

  /**
   * Generates a UTC timestamp for the given or current time.
   *
   * @param int|null $instant
   *   The Unix timestamp (defaults to current time if null).
   *
   * @return string
   *   The generated UTC timestamp in ISO 8601 format.
   */
  public static function generateTimestamp($instant = NULL) {
    if ($instant === NULL) {
      $instant = time();
    }
    return gmdate('Y-m-d\TH:i:s\Z', $instant);
  }

  /**
   * Executes an XPath query on a given DOMNode.
   *
   * @param \DOMNode $node
   *   The DOMNode to query.
   * @param string $query
   *   The XPath query to execute.
   *
   * @return \DOMNode[]
   *   An array of matching DOMNodes.
   */
  public static function xpQuery(\DOMNode $node, $query) {
    static $xpCache = NULL;

    if ($node instanceof \DOMDocument) {
      $doc = $node;
    }
    else {
      $doc = $node->ownerDocument;
    }

    if ($xpCache === NULL || !$xpCache->document->isSameNode($doc)) {
      $xpCache = new \DOMXPath($doc);
      $xpCache->registerNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
      $xpCache->registerNamespace('saml_protocol', 'urn:oasis:names:tc:SAML:2.0:protocol');
      $xpCache->registerNamespace('saml_assertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
      $xpCache->registerNamespace('saml_metadata', 'urn:oasis:names:tc:SAML:2.0:metadata');
      $xpCache->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
      $xpCache->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
    }

    $results = $xpCache->query($query, $node);
    $ret = [];
    for ($i = 0; $i < $results->length; $i++) {
      $ret[$i] = $results->item($i);
    }
    return $ret;
  }

  /**
   * Parses a NameID element.
   *
   * @param \DOMElement $xml
   *   The XML element representing the NameID.
   *
   * @return array
   *   An associative array containing the NameID value and its attributes.
   */
  public static function parseNameId(\DOMElement $xml) {
    $ret = ['Value' => trim($xml->textContent)];

    foreach (['NameQualifier', 'SPNameQualifier', 'Format'] as $attr) {
      if ($xml->hasAttribute($attr)) {
        $ret[$attr] = $xml->getAttribute($attr);
      }
    }
    return $ret;
  }

  /**
   * Converts an xs:dateTime string to a Unix timestamp.
   *
   * @param string $time
   *   The xs:dateTime string to convert.
   *
   * @return int
   *   The corresponding Unix timestamp.
   *
   * @throws \Exception
   *   If the input string is not a valid xs:dateTime format.
   */
  public static function xsDateTimeToTimestamp($time) {
    $matches = [];

    // We use a very strict regex to parse the timestamp.
    $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
    if (preg_match($regex, $time, $matches) == 0) {
       self::sendErrorResponse("Invalid SAML2 timestamp passed: " . $time);
    }
    // Extract the different components of the time from the  matches in the regex.
    // intval will ignore leading zeroes in the string.
    $year   = intval($matches[1]);
    $month  = intval($matches[2]);
    $day    = intval($matches[3]);
    $hour   = intval($matches[4]);
    $minute = intval($matches[5]);
    $second = intval($matches[6]);

    // We use gmmktime because the timestamp will always be given
    // in UTC.
    $ts = gmmktime($hour, $minute, $second, $month, $day, $year);
    return $ts;
  }

  /**
   * Extracts the strings.
   *
   * @param \DOMElement $parent
   *   The parent DOM element to search within.
   * @param string $namespaceURI
   *   The namespace URI to match.
   * @param string $localName
   *   The local name of the child elements to match.
   *
   * @return string[]
   *   An array of trimmed text content from the matching child elements.
   */
  public static function extractStrings(\DOMElement $parent, $namespaceURI, $localName) {
    if (!is_string($namespaceURI) || !is_string($localName)) {
        throw new InvalidArgumentException('Namespace URI and Local Name must be strings.');
    }

    $ret = [];
    for ($node = $parent->firstChild; $node !== NULL; $node = $node->nextSibling) {
      if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
        continue;
      }
      $ret[] = trim($node->textContent);
    }
    return $ret;
  }

  /**
   * Validates the XML signature of a given DOM element.
   *
   * @param \DOMElement $root
   *   The root DOM element to validate.
   *
   * @return array|bool
   *   Returns an array with signature details or FALSE on failure.
   */
  public static function validateElement(\DOMElement $root) {
    /* Create an XML security object. */
    $objXMLSecDSig = new XMLSecurityDSig();

    /* Both SAML messages and SAML assertions use the 'ID' attribute. */
    $objXMLSecDSig->idKeys[] = 'ID';

    /* Locate the XMLDSig Signature element to be used. */
    $signatureElement = self::xpQuery($root, './ds:Signature');

    if (count($signatureElement) === 0) {
      /* We don't have a signature element to validate. */
      return FALSE;
    }
    elseif (count($signatureElement) > 1) {
     self::sendErrorResponse('XMLSec: more than one signature element in root.');
    }

    $signatureElement = $signatureElement[0];
    $objXMLSecDSig->sigNode = $signatureElement;

    /* Canonicalize the XMLDSig SignedInfo element in the message. */
    $objXMLSecDSig->canonicalizeSignedInfo();

    /* Validate referenced xml nodes. */
    if (!$objXMLSecDSig->validateReference()) {
      echo sprintf("XMLsec: digest validation failed");
      exit;
    }

    /* Check that $root is one of the signed nodes. */
    $rootSigned = FALSE;
    /** @var \DOMNode $signedNode */
    foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
      if ($signedNode->isSameNode($root)) {
        $rootSigned = TRUE;
        break;
      }
      elseif ($root->parentNode instanceof \DOMDocument && $signedNode->isSameNode($root->ownerDocument)) {
        /* $root is the root element of a signed document. */
        $rootSigned = TRUE;
        break;
      }
    }

    if (!$rootSigned) {
       self::sendErrorResponse('XMLSec: The root element is not signed.');
    }

    /* Now we extract all available X509 certificates in the signature element. */
    $certificates = [];
    foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
      $certData = trim($certNode->textContent);
      $certData = str_replace(["\r", "\n", "\t", ' '], '', $certData);
      $certificates[] = $certData;
    }

    $ret = [
      'Signature' => $objXMLSecDSig,
      'Certificates' => $certificates,
    ];

    return $ret;
  }

  /**
   * Converts an XMLSecurityKey to a specified algorithm and key type.
   *
   * @param XMLSecurityKey $key
   *   The original XMLSecurityKey object.
   * @param string $algorithm
   *   The desired algorithm type for the key.
   * @param string $type
   *   The key type, either 'public' or 'private'. Defaults to 'public'.
   *
   * @return XMLSecurityKey
   *   A new XMLSecurityKey object with the specified algorithm and key type.
   *
   * @throws \Exception
   *   If key details cannot be retrieved or are invalid.
   */
  public static function castKey(XMLSecurityKey $key, $algorithm, $type = 'public') {
    if (!is_string($algorithm)) {
        throw new InvalidArgumentException('Algorithm must be a string.');
    }
    if (!in_array($type, ['public', 'private'], TRUE)) {
        throw new InvalidArgumentException('Key type must be either "public" or "private".');
    }

    // Do nothing if algorithm is already the type of the key.
    if ($key->type === $algorithm) {
      return $key;
    }

    $keyInfo = openssl_pkey_get_details($key->key);
    if ($keyInfo === FALSE) {
       self::sendErrorResponse('Unable to get key details from XMLSecurityKey.');
    }
    if (!isset($keyInfo['key'])) {
       self::sendErrorResponse('Missing key in public key details.');
    }

    $newKey = new XMLSecurityKey($algorithm, ['type' => $type]);
    $newKey->loadKey($keyInfo['key']);

    return $newKey;
  }

  /**
   * Decrypt an encrypted element.
   *
   * This is an internal helper function.
   *
   * @param \DOMElement $encryptedData
   *   The encrypted data.
   * @param XMLSecurityKey $inputKey
   *   The decryption key.
   * @param array &$blacklist
   *   Blacklisted decryption algorithms.
   *
   * @return \DOMElement
   *   The decrypted element.
   *
   * @throws Exception.
   */
  private static function doDecryptElement(\DOMElement $encryptedData, XMLSecurityKey $inputKey, array &$blacklist) {
    $enc = new XMLSecEnc();
    $enc->setNode($encryptedData);

    $enc->type = $encryptedData->getAttribute("Type");
    $symmetricKey = $enc->locateKey($encryptedData);
    if (!$symmetricKey) {
       self::sendErrorResponse('Could not locate key algorithm in encrypted data.');
    }

    $symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
    if (!$symmetricKeyInfo) {
       self::sendErrorResponse('Could not locate <dsig:KeyInfo> for the encrypted key.');
    }
    $inputKeyAlgo = $inputKey->getAlgorithm();
    if ($symmetricKeyInfo->isEncrypted) {
      $symKeyInfoAlgo = $symmetricKeyInfo->getAlgorithm();
      if (in_array($symKeyInfoAlgo, $blacklist, TRUE)) {
         self::sendErrorResponse('Algorithm disabled: ' . var_export($symKeyInfoAlgo, TRUE));
      }
      if ($symKeyInfoAlgo === XMLSecurityKey::RSA_OAEP_MGF1P && $inputKeyAlgo === XMLSecurityKey::RSA_1_5) {
        /*
         * The RSA key formats are equal, so loading an RSA_1_5 key
         * into an RSA_OAEP_MGF1P key can be done without problems.
         * We therefore pretend that the input key is an
         * RSA_OAEP_MGF1P key.
         */
        $inputKeyAlgo = XMLSecurityKey::RSA_OAEP_MGF1P;
      }
      /* Make sure that the input key format is the same as the one used to encrypt the key. */
      if ($inputKeyAlgo !== $symKeyInfoAlgo) {
        echo sprintf('Algorithm mismatch between input key and key used to encrypt ' .
              ' the symmetric key for the message. Key was: ' .
              var_export($inputKeyAlgo, TRUE) . '; message was: ' .
              var_export($symKeyInfoAlgo, TRUE));
        exit;
      }
      /** @var XMLSecEnc $encKey */
      $encKey = $symmetricKeyInfo->encryptedCtx;
      $symmetricKeyInfo->key = $inputKey->key;
      $keySize = $symmetricKey->getSymmetricKeySize();
      if ($keySize === NULL) {
        /* To protect against "key oracle" attacks, we need to be able to create a
         * symmetric key, and for that we need to know the key size.
         */
        echo sprintf('Unknown key size for encryption algorithm: ' . var_export($symmetricKey->type, TRUE));
        exit;
      }
      try {
        $key = $encKey->decryptKey($symmetricKeyInfo);
        if (strlen($key) != $keySize) {
          echo sprintf('Unexpected key size (' . strlen($key) * 8 . 'bits) for encryption algorithm: ' .
                var_export($symmetricKey->type, TRUE));
          exit;
        }
      }
      catch (Exception $e) {
        /* We failed to decrypt this key. Log it, and substitute a "random" key. */

        /* Create a replacement key, so that it looks like we fail in the same way as if the key was correctly padded. */
        /* We base the symmetric key on the encrypted key and private key, so that we always behave the
         * same way for a given input key.
         */
        $encryptedKey = $encKey->getCipherValue();
        $pkey = openssl_pkey_get_details($symmetricKeyInfo->key);
        $pkey = sha1(serialize($pkey), TRUE);
        $key = sha1($encryptedKey . $pkey, TRUE);
        /* Make sure that the key has the correct length. */
        if (strlen($key) > $keySize) {
          $key = substr($key, 0, $keySize);
        }
        elseif (strlen($key) < $keySize) {
          $key = str_pad($key, $keySize);
        }
      }
      $symmetricKey->loadkey($key);
    }
    else {
      $symKeyAlgo = $symmetricKey->getAlgorithm();
      /* Make sure that the input key has the correct format. */
      if ($inputKeyAlgo !== $symKeyAlgo) {
        echo sprintf('Algorithm mismatch between input key and key in message. ' .
              'Key was: ' . var_export($inputKeyAlgo, TRUE) . '; message was: ' .
              var_export($symKeyAlgo, TRUE));
        exit;
      }
      $symmetricKey = $inputKey;
    }
    $algorithm = $symmetricKey->getAlgorithm();
    if (in_array($algorithm, $blacklist, TRUE)) {
      echo sprintf('Algorithm disabled: ' . var_export($algorithm, TRUE));
      exit;
    }
    /** @var string $decrypted */
    $decrypted = $enc->decryptNode($symmetricKey, FALSE);
    /*
     * This is a workaround for the case where only a subset of the XML
     * tree was serialized for encryption. In that case, we may miss the
     * namespaces needed to parse the XML.
     */
    $xml = '<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ' .
                     'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
            $decrypted .
            '</root>';
    $newDoc = new \DOMDocument();
    if (!@$newDoc->loadXML($xml)) {
      echo sprintf('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
      throw new Exception('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
    }
    $decryptedElement = $newDoc->firstChild->firstChild;
    if ($decryptedElement === NULL) {
      echo sprintf('Missing encrypted element.');
      throw new Exception('Missing encrypted element.');
    }

    if (!($decryptedElement instanceof \DOMElement)) {
      echo sprintf('Decrypted element was not actually a DOMElement.');
    }

    return $decryptedElement;
  }

  /**
   * Decrypt an encrypted element.
   *
   * @param \DOMElement $encryptedData
   *   The encrypted data.
   * @param XMLSecurityKey $inputKey
   *   The decryption key.
   * @param array $blacklist
   *   Blacklisted decryption algorithms.
   *
   * @return \DOMElement
   *   The decrypted element.
   *
   * @throws Exception.
   */
  public static function decryptElement(\DOMElement $encryptedData, XMLSecurityKey $inputKey, array $blacklist = [], ?XMLSecurityKey $alternateKey = NULL) {
    try {
      echo "trying primary";
      return self::doDecryptElement($encryptedData, $inputKey, $blacklist);
    }
    catch (Exception $e) {
      // Try with alternate key.
      try {
        echo "trying secondary";
        return self::doDecryptElement($encryptedData, $alternateKey, $blacklist);
      }
      catch (Exception $t) {

      }
      /*
       * Something went wrong during decryption, but for security
       * reasons we cannot tell the user what failed.
       */
      echo sprintf('Failed to decrypt XML element.');
      exit;
    }
  }

  /**
   * Parse a boolean attribute.
   *
   * @param \DOMElement $node
   *   The element we should fetch the attribute from.
   * @param string $attributeName
   *   The name of the attribute.
   * @param mixed $default
   *   The value that should be returned if the attribute doesn't exist.
   *
   * @return bool|mixed
   *   The value of the attribute, or $default if the attribute doesn't exist.
   *
   * @throws \Exception.
   */
  public static function parseBoolean(\DOMElement $node, $attributeName, $default = NULL) {

    if (!$node->hasAttribute($attributeName)) {
      return $default;
    }
    $value = $node->getAttribute($attributeName);
    switch (strtolower($value)) {
      case '0':
      case 'false':
        return FALSE;

      case '1':
      case 'true':
        return TRUE;

      default:
        throw new Exception('Invalid value of boolean attribute ' . var_export($attributeName, TRUE) . ': ' . var_export($value, TRUE));
    }
  }

  /**
   * Retrieves the encryption algorithm.
   *
   * @param string $method
   *   The URI of the encryption method.
   *
   * @return string
   *   The corresponding XMLSecurityKey constant for the encryption algorithm.
   *
   * @throws \Exception
   *   If the method URI is invalid.
   */
  public static function getEncryptionAlgorithm($method) {
    switch ($method) {
      case 'http://www.w3.org/2001/04/xmlenc#tripledes-cbc':
        return XMLSecurityKey::TRIPLEDES_CBC;

      break;

      case 'http://www.w3.org/2001/04/xmlenc#aes128-cbc':
        return XMLSecurityKey::AES128_CBC;

      case 'http://www.w3.org/2001/04/xmlenc#aes192-cbc':
        return XMLSecurityKey::AES192_CBC;

      break;

      case 'http://www.w3.org/2001/04/xmlenc#aes256-cbc':
        return XMLSecurityKey::AES256_CBC;

      break;

      case 'http://www.w3.org/2001/04/xmlenc#rsa-1_5':
        return XMLSecurityKey::RSA_1_5;

      break;

      case 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p':
        return XMLSecurityKey::RSA_OAEP_MGF1P;

      break;

      case 'http://www.w3.org/2000/09/xmldsig#dsa-sha1':
        return XMLSecurityKey::DSA_SHA1;

      break;

      case 'http://www.w3.org/2000/09/xmldsig#rsa-sha1':
        return XMLSecurityKey::RSA_SHA1;

      break;

      case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256':
        return XMLSecurityKey::RSA_SHA256;

      break;
      case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384':
        return XMLSecurityKey::RSA_SHA384;

      break;

      case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512':
        return XMLSecurityKey::RSA_SHA512;

      break;

      default:
        echo sprintf('Invalid Encryption Method: ' . $method);
        exit;
      break;
    }
  }

  /**
   * Adds a premium tag for premium features with a link.
   *
   * @param string $mo_tag
   *   The tag to be displayed.
   *
   * @return string
   *   The formatted premium tag with a link.
   */
  public static function miniorangeAddPremiumTag($mo_tag) {
    $base_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $url = $base_url . '/admin/config/people/miniorange_saml_idp/Licensing';
    $mo_premium_tag = '<a href= "' . $url . '" style="color: red; font-weight: lighter;">[' . $mo_tag . ']</a>';
    return $mo_premium_tag;
  }

  /**
   * Retrieves the current Drupal core version.
   *
   * @return string
   *   The Drupal core version.
   */
  public static function miniorangeGetDrupalCoreVersion() {
    return \DRUPAL::VERSION;
  }

  /**
   * Determines if the script is being run in a CLI environment.
   *
   * @return bool
   *   TRUE if running in CLI, FALSE otherwise.
   */
  public static function drupalIsCli() {
    $server = \Drupal::request()->server;
    $server_software = $server->get('SERVER_SOFTWARE');
    $server_argc = $server->get('argc');
    return !isset($server_software) && (php_sapi_name() == 'cli' || (is_numeric($server_argc) && $server_argc > 0));

  }

  /**
   * Retrieves custom user fields for mapping.
   *
   * @return array
   *   An array of custom user fields.
   */
  public static function customUserFields() {
    $custom_fields = ['select' => '- Select Attribute Value -'];
    $usr = User::load(\Drupal::currentUser()->id());
    $usrVal = $usr->toArray();
    foreach ($usrVal as $key => $value) {
      if (!array_key_exists($key, MiniorangeSamlIdpConstants::NOT_INCLUDE_IN_MAPPING)) {
        $custom_fields[$key] = $key;
      }
    }
    return $custom_fields;
  }

  /**
   * Generates a modal form based on the API response.
   *
   * @param string $supportResponse
   *   The response code from the support API.
   * @param string $email
   *   The email address provided by the user.
   * @param bool $isTrial
   *   Indicates if the request is for a trial.
   *
   * @return \Drupal\Core\Ajax\OpenModalDialogCommand
   *   The command to open a modal dialog.
   */
  public static function getModalFormAfterQuery($supportResponse, $email, $isTrial) {
    $trialMessage = t('Thank you for reaching out to us! We are processing your request and you will soon receive details on %email.', ['%email' => $email]);
    $queryMessage = t('Thanks for getting in touch! We will get back to you shortly.');
    $successMessage = $isTrial ? $trialMessage : $queryMessage;

    if ($supportResponse == MiniorangeSamlIdpConstants::API_QUERY_SUCCESS_RESPONSE) {
      $message = [
        '#type' => 'item',
        '#markup' => $successMessage,
      ];
      $ajax_form = new OpenModalDialogCommand('Thank you!', $message, ['width' => '50%']);
    }
    elseif ($supportResponse == MiniorangeSamlIdpConstants::API_INVALID_EMAIL_RESPONSE) {
      $message = [
        '#type' => 'item',
        '#markup' => t('The email address entered :email, possibly invalid.
                        We discourage the use of disposable emails, please try again with a valid email.
                        You can also reach out to us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>.',
           [':email' => $email]),
      ];
      $ajax_form = new OpenModalDialogCommand('⚠️ Invalid Email', $message, ['width' => '50%']);
    }
    else {
      $error = [
        '#type' => 'item',
        '#markup' => t('Error submitting the support query. Please send us your query at
                             <a href="mailto:drupalsupport@xecurify.com">
                             drupalsupport@xecurify.com</a>.'),
      ];
      $ajax_form = new OpenModalDialogCommand('⚠️ Error', $error, ['width' => '50%']);
    }

    return $ajax_form;
  }

  /**
   * Retrieves the formatted timezone of the server.
   *
   * @return string
   *   The formatted timezone.
   */
  public static function getFormatedTimezone() {
    $defaultTimezone = date_default_timezone_get();
    $differenceToUtc = date('P', time());
    return "$defaultTimezone (UTC $differenceToUtc)";
  }
public static function sendErrorResponse($error) {
  echo '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; padding: 40px 20px; background-color: #f5f5f5; min-height: 100vh;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="background-color: #d8274e; color: white; padding: 20px; text-align: center; font-size: 24px; font-weight: 600;">ERROR</div>
        <div style="padding: 30px;">
            <div style="background-color: #fcf4f4; border-left: 4px solid #d8274e; padding: 15px 20px; margin-bottom: 20px;">
                <p style="margin: 0; color: #5a5a5a; font-size: 16px;"><strong style="color: #d8274e;">Error:</strong> ' . htmlspecialchars($error) . '</p>
            </div>';

echo '    <div style="text-align: center; margin-top: 30px;">
                <a href="javascript:history.back()" style="display: inline-block; background-color: #0074bd; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 15px;">Go Back</a>
            </div>
        </div>
    </div>
</div>';
exit();
  }
}
