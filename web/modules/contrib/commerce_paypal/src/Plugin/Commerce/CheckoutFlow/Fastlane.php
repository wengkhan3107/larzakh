<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom checkout flow for use by Fastlane by PayPal.
 *
 * @CommerceCheckoutFlow(
 *   id = "paypal_fastlane",
 *   label = "Fastlane by PayPal Checkout Flow",
 * )
 */
class Fastlane extends CheckoutFlowWithPanesBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pane_id, $pane_definition): CheckoutFlowWithPanesInterface {
    $instance = parent::create($container, $configuration, $pane_id, $pane_definition);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    if (isset($form['display_checkout_progress'])) {
      $form['display_checkout_progress']['#access'] = FALSE;
    }
    if (isset($form['display_checkout_progress_breadcrumb_links'])) {
      $form['display_checkout_progress_breadcrumb_links']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSteps(): array {
    // Note that previous_label and next_label are not the labels
    // shown on the step itself. Instead, they are the labels shown
    // when going back to the step or proceeding to the step.
    $steps = [
      'order_information' => [
        'label' => $this->t('Order information'),
        'has_sidebar' => TRUE,
        'previous_label' => $this->t('Go back'),
      ],
    ] + parent::getSteps();
    $steps['complete']['next_label'] = $this->t('Pay and complete purchase');
    return $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getPanes(): array {
    $panes = parent::getPanes();
    $remove_list = ['paypal_checkout_payment_process', 'review', 'login', 'contact_information'];
    foreach ($panes as $id => $pane) {
      if (in_array($id, $remove_list, TRUE)) {
        unset($panes[$id]);
        continue;
      }
      // Ensure we don't override the existing configuration for these panes.
      if (!isset($this->configuration['panes'][$id])) {
        $pane->setStepId('_disabled');
      }
    }
    return $panes;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $pane_values = $values['panes'];
    $required_pane_ids = ['paypal_fastlane_contact_information'];
    $has_shipping = $this->moduleHandler->moduleExists('commerce_shipping');
    if ($has_shipping) {
      $required_pane_ids[] = 'shipping_information';
    }
    $required_pane_ids[] = 'payment_information';

    foreach ($required_pane_ids as $pane_id) {
      if (!isset($pane_values[$pane_id]) ||
        $pane_values[$pane_id]['step_id'] !== 'order_information') {
        $pane = $this->getPane($pane_id);
        $form_state->setError($form['panes'], $this->t('The %title pane must be configured in the %step step.', [
          '%title' => $pane->getLabel(),
          '%step' => $this->getStepLabel('order_information'),
        ]));
      }
    }

    $order_information_panes = array_filter($pane_values, static function ($pane_value) {
      return $pane_value['step_id'] === 'order_information';
    });
    $ordered_panes = array_keys($order_information_panes);

    $contact_pane_key = array_search('paypal_fastlane_contact_information', $ordered_panes, TRUE);
    if ($contact_pane_key === FALSE) {
      $form_state->setError($form['panes'], $this->t('The %title pane must be configured in the %step step.', [
        '%title' => $this->getPane('paypal_fastlane_contact_information')?->getLabel(),
        '%step' => $this->getStepLabel('order_information'),
      ]));
      return;
    }
    $payment_pane_key = array_search('payment_information', $ordered_panes, TRUE);
    if ($payment_pane_key === FALSE) {
      $form_state->setError($form['panes'], $this->t('The %title pane must be configured in the %step step.', [
        '%title' => $this->getPane('payment_information')?->getLabel(),
        '%step' => $this->getStepLabel('order_information'),
      ]));
      return;
    }

    if ($has_shipping) {
      $shipping_pane_key = array_search('shipping_information', $ordered_panes, TRUE);

      if ($shipping_pane_key === FALSE) {
        $form_state->setError($form['panes'], $this->t('The %title pane must be configured in the %step step.', [
          '%title' => $this->getPane('shipping_information')?->getLabel(),
          '%step' => $this->getStepLabel('order_information'),
        ]));
        return;
      }

      if ($shipping_pane_key < $contact_pane_key) {
        $form_state->setError($form['panes'], $this->t('The %title pane must be before the %title_2 pane.', [
          '%title' => $this->getPane('paypal_fastlane_contact_information')?->getLabel(),
          '%title_2' => $this->getPane('shipping_information')?->getLabel(),
        ]));
      }
      if ($payment_pane_key < $shipping_pane_key) {
        $form_state->setError($form['panes'], $this->t('The %title pane must be before the %title_2 pane.', [
          '%title' => $this->getPane('shipping_information')?->getLabel(),
          '%title_2' => $this->getPane('payment_information')?->getLabel(),
        ]));
      }
    }
    else {
      if ($payment_pane_key < $contact_pane_key) {
        $form_state->setError($form['panes'], $this->t('The %title pane must be before the %title_2 pane.', [
          '%title' => $this->getPane('paypal_fastlane_contact_information')?->getLabel(),
          '%title_2' => $this->getPane('payment_information')?->getLabel(),
        ]));
      }
    }

    if ($payment_pane_key < (count($ordered_panes) - 1)) {
      $form_state->setError($form['panes'], $this->t('The %title pane must be the last pane.', [
        '%title' => $this->getPane('payment_information')?->getLabel(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $step_id = NULL): array {
    $form['#attached']['library'][] = 'commerce_paypal/paypal_fastlane';

    // @todo We are relying on the array parents of the shipping profile.
    // We need to do this so that the shipping profile always renders
    // as a form, rather than a rendered profile.
    // See: /commerce/modules/order/src/Plugin/Commerce/InlineForm/CustomerProfile.php::shouldRender
    $form_state->set([
      'shipping_information',
      'shipping_profile',
      'render',
    ], FALSE);
    $form = parent::buildForm($form, $form_state, $step_id);
    if ($step_id !== 'order_information') {
      return $form;
    }

    // Add pane summaries.
    $weight = 1;
    foreach ($this->getVisiblePanes($step_id) as $pane_id => $pane) {
      // Tweak the visible panes.
      $form[$pane_id]['#weight'] = $weight++;
      $form[$pane_id]['#attributes']['data-checkout-pane-id'] = $pane_id;

      // Build the summary for each pane.
      $pane_summary = $pane->buildPaneSummary();
      if (empty($pane_summary)) {
        $pane_summary['summary'] = [
          '#type' => 'container',
        ];
      }

      // Add a summary to each pane.
      $form[$pane_id]['summary'] = [
        '#type' => 'container',
        0 => $pane_summary,
        '#weight' => -10,
        '#attributes' => ['class' => ['summary']],
      ];

      // Add an edit button to each pane.
      $form[$pane_id]['button_edit'] = [
        '#markup' => Markup::create('<button class="button btn btn-primary" type="button" data-checkout-button-type="edit">' . $this->t('Edit') . '</button>'),
        '#weight' => -9,
      ];
      if ($pane_id !== 'payment_information') {
        // Add a "continue" button to each pane,
        // except the payment_information pane, which must be the last pane.
        $form[$pane_id]['button_continue'] = [
          '#markup' => Markup::create('<button class="button btn btn-primary" type="button" data-checkout-button-type="continue">' . $this->t('Continue') . '</button>'),
        ];
      }
      if ($pane_id === 'shipping_information') {
        // Add a change button for use by PayPal Fastlane members.
        $form[$pane_id]['button_change'] = [
          '#markup' => Markup::create('<button class="button btn btn-primary" type="button" data-checkout-button-type="change">' . $this->t('Change') . '</button>'),
          '#weight' => -8,
        ];
        // Remove elements that cause rendering issues.
        unset($form[$pane_id]['#wrapper_id'], $form[$pane_id]['#prefix'], $form[$pane_id]['#suffix']);
        // We do not want auto recalculate to run on this form.
        $form[$pane_id]['#auto_recalculate'] = FALSE;
        $profiles = $this->order->collectProfiles();
        if (array_key_exists('shipping', $profiles)) {
          $shipping_profile = $profiles['shipping'];
          if ($shipping_profile && !$shipping_profile->get('address')->isEmpty()) {
            $shipping_address = $shipping_profile->get('address')?->first()?->getValue();
            $form[$pane_id]['#attached']['drupalSettings']['paypalFastlane'][$this->getOrder()?->id()]['shippingInformation']['address'] = $shipping_address;
          }
          // @todo we should send phone, if one is present.
        }
      }
    }
    self::replaceAjaxRefreshForm($form);
    return $form;
  }

  /**
   * Replace the ajax refresh form callback with our own.
   *
   * @param array $form
   *   The form array.
   */
  protected static function replaceAjaxRefreshForm(array &$form): void {
    foreach ($form as $key => &$value) {
      // Check if the current value is an array (non-leaf node).
      if (is_array($value)) {
        // Recursively call the function for the child array.
        self::replaceAjaxRefreshForm($value);
      }

      // Change callback to our own.
      if ($key === '#ajax') {
        if ($value['callback'][1] === 'ajaxRefreshForm') {
          $value['callback'][0] = self::class;
        }
      }
    }
  }

  /**
   * Ajax refresh form callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public static function ajaxRefreshForm(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NULL;
    if (!empty($triggering_element['#ajax']['element'])) {
      $element = NestedArray::getValue($form, $triggering_element['#ajax']['element']);
    }
    // Element not specified or not found. Show messages on top of the form.
    if (!$element) {
      $element = $form;
    }
    $response = new AjaxResponse();
    foreach (Element::children($form) as $key) {
      self::processRefreshChildren($response, $form[$key], $key);
    }
    $response->addCommand(new PrependCommand('[data-drupal-selector="' . $element['#attributes']['data-drupal-selector'] . '"]', ['#type' => 'status_messages']));

    return $response;
  }

  /**
   * Process the refresh child elements.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The ajax response.
   * @param array $element
   *   The form element.
   * @param string $key
   *   The key.
   */
  protected static function processRefreshChildren(AjaxResponse $response, array $element, string $key): void {
    if (in_array($key, ['paypal_fastlane_contact_information', 'payment_information', 'actions'], TRUE)) {
      return;
    }
    if ($key === 'sidebar') {
      foreach (Element::children($element) as $child_key) {
        self::processRefreshChildren($response, $element[$child_key], $child_key);
      }
      return;
    }
    if (!isset($element['#attributes']['data-drupal-selector'])) {
      return;
    }
    $response->addCommand(new ReplaceCommand('[data-drupal-selector="' . $element['#attributes']['data-drupal-selector'] . '"]', $element));
  }

  /**
   * Gets the step label.
   *
   * @param string $step_id
   *   The step id.
   *
   * @return string
   *   The step label.
   */
  protected function getStepLabel(string $step_id): string {
    $label = $step_id;
    $steps = $this->getSteps();
    if (array_key_exists($step_id, $steps)) {
      $label = $steps[$step_id]['label'];
    }
    return $label;
  }

}
