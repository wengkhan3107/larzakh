<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsCreatingPaymentMethodsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Fastlane payment gateway.
 */
interface FastlaneInterface extends OffsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface, SupportsCreatingPaymentMethodsInterface {

  /**
   * Get the SDK URI.
   *
   * @return string
   *   The SDK URI.
   */
  public function getSdkUri(): string;

  /**
   * Get the client id.
   *
   * @return string
   *   The client id.
   */
  public function getClientId(): string;

  /**
   * Get the intent.
   *
   * @return string
   *   The intent.
   */
  public function getIntent(): string;

  /**
   * Get the styles.
   *
   * @return array
   *   The styles.
   */
  public function getStyles(): array;

  /**
   * Get the allowed brands.
   *
   * @return array
   *   The allowed brands.
   */
  public function getAllowedBrands(): array;

}
