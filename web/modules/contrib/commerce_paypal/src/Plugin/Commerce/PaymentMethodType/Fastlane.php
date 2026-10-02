<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the Fastlane by PayPal payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "paypal_fastlane",
 *   label = @Translation("Fastlane by PayPal"),
 *   create_label = @Translation("Fastlane by PayPal"),
 * )
 */
class Fastlane extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method): string|TranslatableMarkup {
    $label = $payment_method->getPaymentGateway()?->getPlugin()?->getDisplayLabel() ?? $this->t('Fastlane by PayPal')->render();
    if (!$payment_method->get('paypal_fastlane_data')->isEmpty()) {
      $data = json_decode($payment_method->get('paypal_fastlane_data')->getString());
      if ($data->card ?? NULL) {
        $brand = $data->card->brand ?? NULL;
        $last4 = $data->card->lastDigits ?? NULL;
        if ($brand && $last4) {
          $label = $this->t('@card_type ending in @card_number', [
            '@card_type' => $brand,
            '@card_number' => $last4,
          ])->render();
        }
      }
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['paypal_fastlane_data'] = BundleFieldDefinition::create('string_long')
      ->setLabel($this->t('Fastlane by PayPal Data'))
      ->setDescription($this->t('The payment data returned by Fastlane by PayPal'))
      ->setRequired(FALSE);

    return $fields;
  }

}
