<?php

namespace Drupal\miniorange_saml_idp;

/**
 * Represents a customer in the MiniOrange SAML IDP system.
 */
class MiniorangeSamlCustomer {
  /**
   * The email address of the customer.
   *
   * @var string
   */
  public $email;
  /**
   * The phone number of the customer.
   *
   * @var string
   */
  public $phone;
  /**
   * The customer key for the MiniOrange service.
   *
   * @var string
   */
  public $customerKey;
  /**
   * The transaction ID associated with the customer.
   *
   * @var string
   */
  public $transactionId;
  /**
   * The password for the customer account.
   *
   * @var string
   */
  public $password;
  /**
   * The OTP token for customer authentication.
   *
   * @var string
   */
  public $otpToken;
  /**
   * The default customer ID.
   *
   * @var string
   */
  private $defaultCustomerId;
  /**
   * The default customer API key.
   *
   * @var string
   */
  private $defaultCustomerApiKey;

  /**
   * Constructs a new MiniorangeSamlCustomer object.
   *
   * @param string $email
   *   The customer's email address.
   * @param string $phone
   *   The customer's phone number.
   * @param string $password
   *   The customer's password.
   * @param string $otp_token
   *   The OTP token for customer authentication.
   */
  public function __construct($email, $phone, $password, $otp_token) {
    $this->email = $email;
    $this->phone = $phone;
    $this->password = $password;
    $this->otpToken = $otp_token;
    $this->defaultCustomerId = "16622";
    $this->defaultCustomerApiKey = "XzjkmAaAOzmtJRmXddkXyhgDXnMCrdZz";
  }

  /**
   * Checks if the customer already exists in the system.
   *
   * @return string
   *   The response from the service.
   */
  public function checkCustomer() {
    $url = MiniorangeSamlIdpConstants::BASE_URL . '/moas/rest/customer/check-if-exists';
    $email = $this->email;
    $fields = ['email' => $email];
    return $this->callService($url, $fields);
  }

  /**
   * Creates a new customer in the system.
   *
   * @return string
   *   The response from the service.
   */
  public function createCustomer() {
    $url = MiniorangeSamlIdpConstants::BASE_URL . '/moas/rest/customer/add';
    $request = \Drupal::request();
    $serverName = \Drupal::request()->server->get('SERVER_NAME')?: $request->getHost() ?: 'localhost';
    $fields = [
      'companyName' => $serverName,
      'areaOfInterest' => 'DRUPAL 8 IDP Module',
      'email' => $this->email,
      'phone' => $this->phone,
      'password' => $this->password,
    ];
    return $this->callService($url, $fields);
  }

  /**
   * Retrieves the customer's keys from the service.
   *
   * @return string
   *   The response from the service.
   */
  public function getCustomerKeys() {
    $url = MiniorangeSamlIdpConstants::BASE_URL . '/moas/rest/customer/key';
    $email = $this->email;
    $password = $this->password;
    $fields = ['email' => $email, 'password' => $password];
    return $this->callService($url, $fields);
  }

  /**
   * Sends an OTP to the customer for verification.
   *
   * @return string
   *   The response from the service.
   */
  public function sendOtp() {
    $url = MiniorangeSamlIdpConstants::BASE_URL . '/moas/api/auth/challenge';
    $customer_key = $this->defaultCustomerId;
    $username = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_customer_admin_email');
    $fields = ['customerKey' => $customer_key, 'email' => $username, 'authType' => 'EMAIL'];
    return $this->callService($url, $fields, TRUE);
  }

  /**
   * Makes a request to the specified service URL with the given fields.
   *
   * @param string $url
   *   The service URL to send the request to.
   * @param array $fields
   *   The fields to send in the request.
   * @param bool $addExtendedHeader
   *   Whether to include an extended header in the request.
   * @param bool $logError
   *   Whether to log errors if they occur.
   *
   * @return string
   *   The response from the service.
   */
  public function callService($url, $fields, $addExtendedHeader = FALSE, $logError = TRUE) {
    $fieldString = is_string($fields) ? $fields : json_encode($fields);
    $header = $this->getHeader($addExtendedHeader);
    try {
      $response = \Drupal::httpClient()->post($url, [
        'body' => $fieldString,
        'allow_redirects' => TRUE,
        'http_errors' => FALSE,
        'decode_content' => TRUE,
        'headers' => $header,
      ]);
      return $response->getBody()->getContents();
    }
    catch (\Exception $exception) {
      if ($logError) {
        $error = ['%apiName' => explode("moas", $url)[1], '%error' => $exception->getMessage()];
        \Drupal::logger('miniorange_saml_idp')->notice('Error at %apiName of  %error', $error);
      }
    }
  }

  /**
   * Retrieves the headers to be used in the service request.
   *
   * @param bool $addExtendedHeader
   *   Whether to include an extended header in the request.
   *
   * @return array
   *   The headers for the service request.
   */
  public function getHeader($addExtendedHeader = FALSE) {
    $header = [
      'Content-Type' => 'application/json',
      'charset' => 'UTF - 8',
      'Authorization' => 'Basic',
    ];
    if ($addExtendedHeader) {
      /* Current time in milliseconds since midnight, January 1, 1970 UTC. */
      $current_time_in_millis = $this->getTimeStamp();
      /* Creating the Hash using SHA-512 algorithm */
      $string_to_hash = $this->defaultCustomerId . $current_time_in_millis . $this->defaultCustomerApiKey;
      $hashValue = hash("sha512", $string_to_hash);
      $timestamp_header = number_format($current_time_in_millis, 0, '', '');
      $header = array_merge($header, [
        "Customer-Key" => $this->defaultCustomerId,
        "Timestamp" => $timestamp_header,
        "Authorization" => $hashValue,
      ]);

    }
    return $header;

  }

  /**
   * Retrieves the current timestamp in milliseconds.
   *
   * @return string
   *   The current timestamp in milliseconds.
   */
  public function getTimeStamp() {
    $url = MiniorangeSamlIdpConstants::BASE_URL . '/moas/rest/mobile/get-timestamp';
    $fields = [];
    $currentTimeInMillis = $this->callService($url, $fields);
    if (empty($currentTimeInMillis)) {
      $currentTimeInMillis = round(microtime(TRUE) * 1000);
      $currentTimeInMillis = number_format($currentTimeInMillis, 0, '', '');
    }
    return $currentTimeInMillis;
  }

}
