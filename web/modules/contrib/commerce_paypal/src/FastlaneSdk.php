<?php

namespace Drupal\commerce_paypal;

use Drupal\address\AddressInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_paypal\Event\FastlaneOrderRequestEvent;
use Drupal\commerce_paypal\Event\PayPalEvents;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a replacement of the PayPal SDK.
 */
class FastlaneSdk extends SdkBase implements FastlaneSdkInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getOauthToken(): ResponseInterface {
    return $this->client->post('/v1/oauth2/token', [
      'auth' => [$this->config['client_id'], $this->config['secret']],
      'form_params' => [
        'grant_type' => 'client_credentials',
        'response_type' => 'client_token',
        'intent' => 'sdk_init',
        'domains[]' => 'ddev.site',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createOrder(OrderInterface $order, ?AddressInterface $billing_address = NULL): ResponseInterface {
    $params = $this->prepareOrderRequest($order, $billing_address);
    $event = new FastlaneOrderRequestEvent($order, $params);
    $this->eventDispatcher->dispatch($event, PayPalEvents::FASTLANE_CREATE_ORDER_REQUEST);
    return $this->client->post('/v2/checkout/orders', [
      'json' => $event->getRequestBody(),
      'headers' => [
        'PayPal-Request-Id' => floor(microtime(TRUE) * 1000),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function updateOrder($remote_id, OrderInterface $order): ResponseInterface {
    $params = $this->prepareOrderRequest($order);
    $update_params = [
      [
        'op' => 'replace',
        'path' => "/purchase_units/@reference_id=='default'",
        'value' => $params['purchase_units'][0],
      ],
    ];
    $event = new FastlaneOrderRequestEvent($order, $update_params);
    $this->eventDispatcher->dispatch($event, PayPalEvents::FASTLANE_UPDATE_ORDER_REQUEST);
    return $this->client->patch(sprintf('/v2/checkout/orders/%s', $remote_id), ['json' => $event->getRequestBody()]);
  }

  /**
   * Prepares the order request.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\address\AddressInterface|null $billing_address
   *   The billing address.
   *
   * @return array
   *   The request params.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function prepareOrderRequest(OrderInterface $order, ?AddressInterface $billing_address = NULL): array {
    $params = parent::prepareOrderRequest($order, $billing_address);

    // @todo we should support vaulting/reusable.

    /** @var \Drupal\commerce_payment\Entity\PaymentMethod $payment_method */
    $payment_method = $order->get('payment_method')->entity;
    $remote_id = $payment_method->getRemoteId();
    $params['payment_source'] =
      [
        'card' => [
          'single_use_token' => $remote_id,
        ],
      ];

    return $params;
  }

}
