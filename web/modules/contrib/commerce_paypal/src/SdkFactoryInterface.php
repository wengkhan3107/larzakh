<?php

namespace Drupal\commerce_paypal;

/**
 * PayPal SDK factory interface.
 */
interface SdkFactoryInterface {

  /**
   * Retrieves the PayPal SDK for the given config.
   *
   * @param array $configuration
   *   An associative array, containing at least these three keys:
   *   - mode: The API mode (e.g "test" or "live").
   *   - client_id: The client ID.
   *   - secret: The client secret.
   *
   * @return \Drupal\commerce_paypal\CheckoutSdkInterface|\Drupal\commerce_paypal\FastlaneSdkInterface
   *   The PayPal SDK.
   */
  public function get(array $configuration);

  /**
   * Get the base uri.
   *
   * @param array $config
   *   The config.
   *
   * @return string
   *   The base uri.
   */
  public function getBaseUri(array $config): string;

  /**
   * Returns the access token.
   *
   * @param array $config
   *   An associative array, containing at least these three keys:
   *    - mode: The API mode (e.g "test" or "live").
   *    - client_id: The client ID.
   *    - secret: The client secret.
   * @param bool $get_new
   *   Whether a new access token should be generated.
   */
  public function getAccessToken(array $config, bool $get_new = FALSE): ?string;

}
