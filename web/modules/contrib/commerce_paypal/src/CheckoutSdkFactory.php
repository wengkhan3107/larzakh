<?php

namespace Drupal\commerce_paypal;

/**
 * Defines a factory for our custom PayPal checkout SDK.
 */
class CheckoutSdkFactory extends SdkFactoryBase implements CheckoutSdkFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get(array $configuration) {
    $client_id = $configuration['client_id'];
    if (!isset($this->instances[$client_id])) {
      $client = $this->getClient($configuration);
      $this->instances[$client_id] = new CheckoutSdk($client, $this->adjustmentTransformer, $this->eventDispatcher, $this->moduleHandler, $this->time, $configuration, $this->rounder);
    }

    return $this->instances[$client_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUri(array $config): string {
    return match ($config['mode']) {
      'live' => 'https://api.paypal.com',
      default => 'https://api.sandbox.paypal.com',
    };
  }

}
