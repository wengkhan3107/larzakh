<?php

namespace Drupal\commerce_paypal\PluginForm\Checkout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\commerce_paypal\CustomCardFieldsBuilderInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\CheckoutInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding a payment method.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * The custom card fields builder.
   *
   * @var \Drupal\commerce_paypal\CustomCardFieldsBuilderInterface
   */
  protected CustomCardFieldsBuilderInterface $cardFieldsBuilder;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->cardFieldsBuilder = $container->get('commerce_paypal.custom_card_fields_builder');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    // We need to inject the custom card fields, only when this is the solution
    // configured.
    if (!$this->shouldInjectForm($payment_method->getPaymentGateway()->getPlugin())) {
      // If the payment gateway isn't configured to collect the billing
      // information, make sure the payment info pane is hidden in case PayPal
      // is the only gateway enabled.
      if (!isset($form['billing_information'])) {
        $form['#access'] = FALSE;
      }
      return $form;
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->routeMatch->getParameter('commerce_order');
    if ($order instanceof OrderInterface) {
      $form['payment_details'] += $this->cardFieldsBuilder->build($order, $payment_method->getPaymentGateway());
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;
    $payment_method->setReusable(FALSE);
    // When the gateway is configured to display "Smart payment buttons", the
    // buttons are not injected in the payment information pane but in the
    // "review" step, which means the payment method creation should be skipped.
    if ($this->shouldInjectForm($payment_method->getPaymentGateway()->getPlugin())) {
      parent::submitConfigurationForm($form, $form_state);
    }
    else {
      // Since we're not calling the parent submitConfigurationForm() method
      // we need to duplicate the logic for setting the billing profile.
      /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface $payment_gateway_plugin */
      $payment_gateway_plugin = $this->plugin;
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $payment_method = $this->entity;

      if ($payment_gateway_plugin->collectsBillingInformation()) {
        /** @var \Drupal\commerce\Plugin\Commerce\InlineForm\EntityInlineFormInterface $inline_form */
        $inline_form = $form['billing_information']['#inline_form'];
        /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
        $billing_profile = $inline_form->getEntity();
        $payment_method->setBillingProfile($billing_profile);
      }
    }
  }

  /**
   * Determines whether the card fields form should be injected.
   */
  protected function shouldInjectForm(PaymentGatewayInterface $plugin) {
    return $plugin instanceof CheckoutInterface && $plugin->getPaymentSolution() === 'custom_card_fields';
  }

}
