<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "paypal_fastlane_contact_information",
 *   label = @Translation("Fastlane by PayPal information"),
 *   default_step = "_disabled",
 *   wrapper_element = "fieldset",
 *  )
 */
class FastlaneContactInformation extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary(): array {
    $email = $this->order?->getData('paypal_fastlane_email') ?? $this->order?->getEmail() ?? '';
    return [
      '#type' => 'container',
      'email' => [
        '#type' => 'item',
        '#title' => $this->t('Email'),
        '#plain_text' => $email,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form): array {
    $pane_form['#title'] = $this->t('Contact Information');
    $email = $this->order?->getData('paypal_fastlane_email') ?? $this->order?->getEmail() ?? '';
    $pane_form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $email,
      '#required' => TRUE,
    ];
    $pane_form['watermark_container'] = [
      '#type' => 'container',
      '#id' => 'watermark-container',
    ];
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form): void {
    $values = $form_state->getValue($pane_form['#parents']);
    // If the customer is anonymous, set the email on the order.
    if ($this->order?->getCustomer()->isAnonymous()) {
      $this->order->setEmail($values['email']);
    }
    // If the customer is logged in,
    // they may be using a different email with PayPal Fastlane.
    // We'll store it on the order so that it is available for auditing.
    $this->order->setData('paypal_fastlane_email', $values['email']);
  }

}
