<?php

namespace Drupal\commerce_paypal;

use Drupal\address\AddressInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paypal\Event\CheckoutOrderRequestEvent;
use Drupal\commerce_paypal\Event\PayPalEvents;

/**
 * Provides a replacement of the PayPal SDK.
 */
class CheckoutSdk extends SdkBase implements CheckoutSdkInterface {

  /**
   * {@inheritdoc}
   */
  public function createOrder(OrderInterface $order, ?AddressInterface $billing_address = NULL) {
    $params = $this->prepareOrderRequest($order, $billing_address);
    $event = new CheckoutOrderRequestEvent($order, $params);
    $this->eventDispatcher->dispatch($event, PayPalEvents::CHECKOUT_CREATE_ORDER_REQUEST);
    return $this->client->post('/v2/checkout/orders', [
      'json' => $event->getRequestBody(),
      'headers' => [
        'PayPal-Request-Id' => floor(microtime(TRUE) * 1000),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrder($remote_id, OrderInterface $order) {
    $params = $this->prepareOrderRequest($order);
    $update_params = [
      [
        'op' => 'replace',
        'path' => "/purchase_units/@reference_id=='default'",
        'value' => $params['purchase_units'][0],
      ],
    ];
    $event = new CheckoutOrderRequestEvent($order, $update_params);
    $this->eventDispatcher->dispatch($event, PayPalEvents::CHECKOUT_UPDATE_ORDER_REQUEST);
    return $this->client->patch(sprintf('/v2/checkout/orders/%s', $remote_id), ['json' => $event->getRequestBody()]);
  }

}
