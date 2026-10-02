<?php

namespace Drupal\miniorange_saml_idp;

/**
 * @file
 * Contains miniOrange Support class.
 */
/**
 * Provides support functionality for the miniorange_saml_idp module.
 *
 * This class handles the sending of support queries.
 */
class MiniorangeSamlIdpSupport {
  /**
   * Email address for support contact.
   *
   * @var string
   */
  public $email;

  /**
   * Phone number for support contact.
   *
   * @var string
   */
  public $phone;

  /**
   * The query or request message to be sent.
   *
   * @var string
   */
  public $query;
  /**
   * The type of query.
   *
   * @var string
   */
  public $queryType;

  public function __construct($email, $phone, $query, $queryType) {
    $this->email = $email;
    $this->phone = $phone;
    $this->query = $query;
    $this->queryType = $queryType;
  }

  /**
   * Send a support query to miniOrange.
   *
   * This method constructs a support query message and
   * sends it to miniOrange via their contact service API.
   */
  public function sendSupportQuery() {

    $modulesInfo = \Drupal::service('extension.list.module')->getExtensionInfo('miniorange_saml_idp');
    $modulesVersion = $modulesInfo['version'];
    $php_version = phpversion();

    $request = \Drupal::request();
    $serverName = \Drupal::request()->server->get('SERVER_NAME')?: $request->getHost() ?: 'localhost';

    if ($this->queryType === 'Demo Request') {
      $this->query = 'Demo request for ' . $this->phone . ' .<br> ' . $this->query;
    }

    $this->query = '[Drupal ' . Utilities::miniorangeGetDrupalCoreVersion() . ' SAML IDP ' . $this->queryType . ' | ' . $modulesVersion . ' | PHP: ' . $php_version . '] ' . $this->query;

    $fields = [
      'company' => $serverName,
      'email'   => $this->email,
      'phone'   => $this->queryType != 'Demo Request' ? $this->phone : '',
      'ccEmail' => 'drupalsupport@xecurify.com',
      'query'   => $this->query,
    ];

    $url = MiniorangeSamlIdpConstants::BASE_URL . '/moas/rest/customer/contact-us';
    $customer = new MiniorangeSamlCustomer(NULL, NULL, NULL, NULL);
    $response = $customer->callService($url, $fields);
    return $response;

  }

}
