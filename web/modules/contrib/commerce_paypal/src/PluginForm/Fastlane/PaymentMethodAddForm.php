<?php

namespace Drupal\commerce_paypal\PluginForm\Fastlane;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface|null
   */
  protected $shippingOrderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->moduleHandler = $container->get('module_handler');
    if ($container->has('commerce_shipping.order_manager')) {
      $instance->shippingOrderManager = $container->get('commerce_shipping.order_manager');
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_gateway = $payment_method->getPaymentGateway();
    if ($payment_gateway === NULL) {
      return $form;
    }

    /** @var \Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\FastlaneInterface $plugin */
    $plugin = $payment_gateway->getPlugin();

    $order_id = $this->routeMatch->getRawParameter('commerce_order');
    /** @var \Drupal\commerce_order\OrderStorageInterface $order_storage */
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $order = $order_storage->loadForUpdate($order_id);

    $form['payment_details']['fastlane']['paypal_smart_payment_buttons'] = $plugin->build($order);
    $form['payment_details']['fastlane']['payment-container'] = [
      '#type' => 'container',
    ];
    // Populated by the JS library.
    $form['payment_details']['fastlane']['fastlane_payment_token'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'data-fastlane-payment-token' => 'data-fastlane-payment-token',
      ],
    ];
    if ($this->isShippable($order)) {
      $form['payment_details']['fastlane']['fastlane_shipping_address'] = [
        '#type' => 'hidden',
        '#attributes' => [
          'data-fastlane-shipping-address' => 'data-fastlane-shipping-address',
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $payment_details = $values['payment_details'];
    $fastlane_email = $payment_details['fastlane']['email'] ?? NULL;
    $fastlane_payment_token = Json::decode($payment_details['fastlane']['fastlane_payment_token'] ?? NULL);
    $fastlane_billing_address = $fastlane_payment_token['paymentSource']['card']['billingAddress'] ?? NULL;

    $order_id = $this->routeMatch->getRawParameter('commerce_order');
    /** @var \Drupal\commerce_order\OrderStorageInterface $order_storage */
    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $order = $order_storage->loadForUpdate($order_id);

    $order->setData('paypal_fastlane_email', $fastlane_email);
    if (empty($order->getCustomerId()) && empty($order->getEmail())) {
      $order->setEmail($fastlane_email);
    }
    if (!empty($fastlane_billing_address)) {
      $billing_profile = $order->getBillingProfile();
      if (!$billing_profile) {
        $billing_profile = $this->entityTypeManager->getStorage('profile')->create([
          'type' => 'customer',
          'uid' => 0,
        ]);
      }
      $this->populateProfile($billing_profile, $fastlane_billing_address);
      $billing_profile->save();
      $order->setBillingProfile($billing_profile);
    }
    if ($this->isShippable($order)) {
      $fastlane_shipping_address = Json::decode($payment_details['fastlane']['fastlane_shipping_address'] ?? NULL);
      $profiles = $order->collectProfiles();
      $shipping_profile = $profiles['shipping'] ?? NULL;
      if ($shipping_profile !== NULL) {
        if ($fastlane_shipping_address !== NULL) {
          $this->populateProfile($shipping_profile, $fastlane_shipping_address['address']);
          $this->populateProfile($shipping_profile, $fastlane_shipping_address['name']);
        }
        else {
          $this->populateProfile($shipping_profile, $fastlane_billing_address);
        }
        $shipping_profile->save();
      }
    }
    $order->save();
  }

  /**
   * Populate the given profile with the given PayPal address.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile to populate.
   * @param array $address
   *   The PayPal address.
   */
  protected function populateProfile(ProfileInterface $profile, array $address): void {
    // Map Fastlane address keys to keys expected by AddressItem.
    $mapping = [
      // Billing Address fields.
      'countryCodeAlpha2' => 'country_code',
      'postalCode' => 'postal_code',
      'region' => 'administrative_area',
      'locality' => 'locality',
      'streetAddress' => 'address_line1',
      'extendedAddress' => 'address_line2',

      // Shipping Address fields.
      'countryCode' => 'country_code',
      'adminArea1' => 'administrative_area',
      'adminArea2' => 'locality',
      'addressLine1' => 'address_line1',
      'addressLine2' => 'address_line2',

      'firstName' => 'given_name',
      'lastName' => 'family_name',

      'company' => 'organization',
    ];
    // @todo map phone.
    foreach ($address as $key => $value) {
      if (!isset($mapping[$key])) {
        continue;
      }
      // PayPal address fields have a higher maximum length than ours.
      $value = ($mapping[$key] === 'country_code') ? $value : mb_substr($value, 0, 255);
      $profile->get('address')->{$mapping[$key]} = $value;
    }
  }

  /**
   * Determine whether the order is shippable.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   Whether the order is shippable.
   */
  public function isShippable(OrderInterface $order): bool {
    if (!$this->shippingOrderManager) {
      return FALSE;
    }
    return $this->shippingOrderManager->isShippable($order);
  }

}
