<?php

namespace Drupal\miniorange_saml_idp;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Random\RandomException;

/**
* @file
* class CertificateKeyGenerator is responsible for generating and storing the SAML keys (public and private) in the database if they are not already present.
*/

class CertificateKeyGenerator {

  /**
   * config factory interface to access and modify configuration settings.
   *
   * @var
   */
 private $configFactory;
  /**
   * extension path resolver to determine the path of the module for accessing the OpenSSL configuration file.
   *
   * @var
   */
 private $pathResolver;
 /**
   * The application root directory, used to construct the path to the OpenSSL configuration file.
   *
   * @var
   */
 private $appRoot;

  public function __construct(ConfigFactoryInterface $config_factory,ExtensionPathResolver $path_resolver,$app_root) {
    $this->configFactory = $config_factory;
    $this->pathResolver = $path_resolver;
    $this->appRoot = $app_root;
  }

  /**
   * Check if SAML certificate exists in configuration.
   *
   * @return bool
   *   TRUE if certificate exists, FALSE otherwise.
   */
  protected function samlCertificateExists(): bool {
    $config = $this->configFactory->getEditable('miniorange_saml_idp.settings');
    return !empty($config->get('miniorange_saml_idp_x509_certificate'));
  }

  /**
   * Generate and store SAML keys in configuration.
   */
  protected function generateAndStoreSamlKeys(): void {
    $new_keys = $this->generateSamlKeys();
    $config = $this->configFactory->getEditable('miniorange_saml_idp.settings');
    $config->set('miniorange_saml_idp_x509_certificate', $new_keys['public_key'])
      ->set('miniorange_saml_idp_private_key', $new_keys['private_key'])
      ->save();
  }

  /**
   * Ensure SAML certificate exists, generate if missing.
   */
  public function ensureSamlCertificateExists($force = FALSE): void {
    if ($force || !$this->samlCertificateExists()) {
      $this->generateAndStoreSamlKeys();
    }
  }

  /**
   * Generates a new pair of SAML keys (public and private) using OpenSSL and
   * returns them as an array.
   *
   * @return false|array
   * @throws RandomException
   */
  private function generateSamlKeys()
  {
    $dn = [
      'countryName'            => 'IN',
      'stateOrProvinceName'    => 'Maharashtra',
      'localityName'           => 'Pune',
      'organizationName'       => 'miniOrange Security Software Pvt Ltd',
      'organizationalUnitName' => 'Drupal',
      'commonName'             => 'miniOrange',
      'emailAddress'           => MiniorangeSamlIdpConstants::SUPPORT_EMAIL,
    ];

    $module_path = $this->pathResolver->getPath('module', 'miniorange_saml_idp');
    $openssl_config_path = $this->appRoot . '/' . $module_path . '/src/libraries/openssl.cnf';

    $config = [
      'config' => $openssl_config_path,
      'digest_alg'       => 'sha512',
      'private_key_bits' => 2048,
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    $serial = random_int(1000000000, PHP_INT_MAX);
    $private_key = openssl_pkey_new($config);
    $csr = openssl_csr_new($dn, $private_key, $config);
    if($csr===FALSE){
      \Drupal::logger('miniorange_saml_idp')->error('Error while generating SAML keys.');
      return FALSE;
    }
    $csr_signed = openssl_csr_sign($csr, null, $private_key, 365 * 3, $config, $serial);

    openssl_x509_export($csr_signed, $public_key_out);
    openssl_pkey_export($private_key, $private_key_out, null, $config);
    while ( ( $e = openssl_error_string() ) !== false ) {
      error_log($e);
    }
  return [
    'public_key'  => $public_key_out,
    'private_key' => $private_key_out,
     ];
  }
}
