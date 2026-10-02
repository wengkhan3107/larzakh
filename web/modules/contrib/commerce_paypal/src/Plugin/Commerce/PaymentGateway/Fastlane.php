<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway;

use Drupal\commerce\Utility\Error;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_paypal\FastlaneSdkFactoryInterface;
use Drupal\commerce_paypal\FastlaneSdkInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Url;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the PayPal Fastlane payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_fastlane",
 *   label = @Translation("Fastlane by PayPal (Preferred)"),
 *   display_label = @Translation("Fastlane by PayPal"),
 *   modes = {
 *     "test" = @Translation("Sandbox"),
 *     "live" = @Translation("Live"),
 *   },
 *   forms = {
 *     "add-payment-method" =
 *   "Drupal\commerce_paypal\PluginForm\Fastlane\PaymentMethodAddForm",
 *     "offsite-payment" =
 *   "Drupal\commerce_paypal\PluginForm\Fastlane\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"paypal_fastlane"},
 *   requires_billing_information = FALSE,
 * )
 *
 * @see https://developer.paypal.com/studio/checkout/fastlane
 */
class Fastlane extends OffsitePaymentGatewayBase implements FastlaneInterface {

  /**
   * The PayPal Fastlane SDK factory.
   *
   * @var \Drupal\commerce_paypal\FastlaneSdkFactoryInterface
   */
  protected FastlaneSdkFactoryInterface $fastlaneSdkFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The Private User Temporary Storage.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $privateTempStore;

  /**
   * @var \Drupal\commerce_paypal\FastlaneSdkInterface|null
   */
  protected ?FastlaneSdkInterface $sdk = NULL;

  /**
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface|null
   */
  protected $shippingOrderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): FastlaneInterface {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fastlaneSdkFactory = $container->get('commerce_paypal.fastlane_sdk_factory');
    $instance->currentUser = $container->get('current_user');
    $instance->logger = $container->get('logger.channel.commerce_paypal');
    $instance->moduleHandler = $container->get('module_handler');
    /** @var \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store_factory */
    $private_temp_store_factory = $container->get('tempstore.private');
    $instance->privateTempStore = $private_temp_store_factory->get('commerce_paypal_fastlane');
    if ($container->has('commerce_shipping.order_manager')) {
      $instance->shippingOrderManager = $container->get('commerce_shipping.order_manager');
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedBrands(): array {
    return $this->configuration['allowed_brands'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getClientId(): string {
    return $this->configuration['client_id'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIntent(): string {
    return $this->configuration['intent'] ?? 'capture';
  }

  /**
   * {@inheritdoc}
   */
  public function getSdkUri(): string {
    return match ($this->getMode()) {
      'live' => 'https://www.paypal.com/sdk/js',
      default => 'https://www.sandbox.paypal.com/sdk/js',
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles(): array {
    return $this->configuration['styles'] ?? [];
  }

  /**
   * Get the SDK.
   *
   * @return \Drupal\commerce_paypal\FastlaneSdkInterface
   *   The sdk.
   */
  protected function getSdk(): FastlaneSdkInterface {
    if ($this->sdk === NULL) {
      $this->sdk = $this->fastlaneSdkFactory->get($this->getConfiguration());
    }
    return $this->sdk;
  }

  /**
   * Get the oAuth token.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   The oAuth token.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function getToken(OrderInterface $order): array {
    $token = $this->privateTempStore->get('fastlane-token-' . $order->uuid());

    if (empty($token) || (($token['expiration'] ?? 0) < time())) {
      $sdk = $this->getSdk();
      try {
        $response = $sdk->getOauthToken();
        $body = Json::decode($response->getBody()->getContents());
        $expires_in = $body['expires_in'] ?? 9900;
        $token = [
          'expiration' => (time() + $expires_in),
          'data' => $body,
        ];
        $this->privateTempStore->set('fastlane-token-' . $order->uuid(), $token);
      }
      catch (ClientException $exception) {
        Error::logException($this->logger, $exception);
        throw $exception;
      }
    }
    return $token['data'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'client_id' => '',
      'secret' => '',
      'intent' => 'capture',
      'collect_billing_information' => FALSE,
      'webhook_id' => '',
      'allowed_brands' => [],
      'styles' => [
        'root' => [
          'backgroundColor' => '#ffffff',
          'errorColor' => '#d9360b',
          'fontFamily' => 'PayPal-Open',
          'fontSizeBase' => 16,
          'textColorBase' => '#01080d',
        ],
        'input' => [
          'backgroundColor' => '#ffffff',
          'borderRadius' => 4,
          'borderColor' => '#dadddd',
          'focusBorderColor' => '#0057ff',
          'borderWidth' => 1,
          'textColorBase' => '#01080d',
        ],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $documentation_url = Url::fromUri('https://www.drupal.org/node/3092506')->toString();
    $form['mode']['#weight'] = 0;
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
      '#weight' => 0,
    ];
    $form['credentials']['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['form-item'],
      ],
      '#value' => $this->t('Refer to the <a href=":url" target="_blank">module documentation</a> to find your API credentials.', [':url' => $documentation_url]),
    ];
    $form['credentials']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->configuration['client_id'],
      '#size' => 80,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#parents' => array_merge($form['#parents'], ['client_id']),
    ];
    $form['credentials']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#size' => 80,
      '#maxlength' => 128,
      '#default_value' => $this->configuration['secret'],
      '#required' => TRUE,
      '#parents' => array_merge($form['#parents'], ['secret']),
    ];
    $form['collect_billing_information']['#access'] = FALSE;
    $webhook_endpoint_url = $this->t('This will be provided after you have saved this gateway.');
    if ($id = $form_state->getFormObject()->getEntity()->id()) {
      $webhook_endpoint_url = Url::fromRoute('commerce_payment.notify', ['commerce_payment_gateway' => $id])->setAbsolute()->toString();
    }
    $form['webhook_endpoint_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Webhook URL'),
      '#markup' => $webhook_endpoint_url,
      '#description' => $this->t('Specify this when configuring your Webhook for your <a target="_blank" href="https://developer.paypal.com/dashboard/applications/">application</a> in PayPal. Under "Payments and Payouts", select the following events: <ul><li>Payment authorization voided</li><li>Payment capture completed</li><li>Payment capture denied</li><li>Payment capture pending</li><li>Payment capture refunded</li></ul>'),
    ];
    $form['webhook_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook ID'),
      '#size' => 32,
      '#maxlength' => 64,
      '#description' => $this->t('Required value when using Webhooks, used to verify the webhook signature.'),
      '#default_value' => $this->configuration['webhook_id'],
    ];
    $form['intent'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction type'),
      '#options' => [
        'capture' => $this->t("Capture (capture payment immediately after customer's approval)"),
        'authorize' => $this->t('Authorize (requires manual or automated capture after checkout)'),
      ],
      '#description' => $this->t('For more information on capturing a prior authorization, please refer to <a href=":url" target="_blank">Capture an authorization</a>.', [':url' => 'https://docs.drupalcommerce.org/commerce2/user-guide/payments/capture']),
      '#default_value' => $this->configuration['intent'],
    ];
    $form['allowed_brands'] = [
      '#title' => $this->t('Allowed brands'),
      '#description' => $this->t('The allowed brands for the transaction. By default, brand eligibility is smartly decided based on a variety of factors. If no brands are selected, all will be considered.'),
      '#type' => 'checkboxes',
      '#options' => [
        'VISA' => $this->t('Visa'),
        'MASTERCARD' => $this->t('Mastercard'),
        'AMEX' => $this->t('American Express'),
        'DINERS' => $this->t('Diners Club'),
        'DISCOVER' => $this->t('Discover'),
        'JCB' => $this->t('JCB'),
        'CHINA_UNION_PAY' => $this->t('China Union Pay'),
        'MAESTRO' => $this->t('Maestro'),
        'ELO' => $this->t('Elo'),
        'MIR' => $this->t('MIR'),
        'HIPER' => $this->t('Hiper'),
        'HIPERCARD' => $this->t('Hiper Card'),
      ],
      '#default_value' => $this->configuration['allowed_brands'],
    ];

    $styles = $this->configuration['styles'] ?? [];
    $form['styles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Styles'),
    ];
    $form['styles']['root'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Root'),
    ];
    $form['styles']['root']['backgroundColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Background color'),
      '#default_value' => $styles['root']['backgroundColor'] ?? '#ffffff',
      '#description' => $this->t('The root background color. Default is <code>#ffffff</code>.'),
    ];
    $form['styles']['root']['errorColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Error color'),
      '#default_value' => $styles['root']['errorColor'] ?? '#d9360b',
      '#description' => $this->t('The root error color. Default is <code>#d9360b</code>.'),
    ];
    $fonts = [
      'PayPal-Open' => 'PayPal-Open',
      'Arial' => 'Arial',
      'Brush Script MT' => 'Brush Script MT',
      'Courier New' => 'Courier New',
      'Garamond' => 'Garamond',
      'Georgia' => 'Georgia',
      'Tahoma' => 'Tahoma',
      'Times New Roman' => 'Times New Roman',
      'Trebuchet MS' => 'Trebuchet MS',
      'Verdana' => 'Verdana',
    ];
    $form['styles']['root']['fontFamily'] = [
      '#type' => 'select',
      '#title' => $this->t('Font family'),
      '#default_value' => $styles['root']['fontFamily'] ?? 'PayPal-Open',
      '#options' => $fonts,
      '#description' => $this->t('The root font family. Default is <code>PayPal-Open</code>.'),
    ];
    $form['styles']['root']['fontSizeBase'] = [
      '#type' => 'number',
      '#title' => $this->t('Font size base (px)'),
      '#default_value' => $styles['root']['fontSizeBase'] ?? 16,
      '#min' => 13,
      '#max' => 24,
      '#step' => 1,
      '#description' => $this->t('The root font size base. Default is <code>16px</code>.'),
    ];
    $form['styles']['root']['textColorBase'] = [
      '#type' => 'color',
      '#title' => $this->t('Text color base'),
      '#default_value' => $styles['root']['textColorBase'] ?? '#01080D',
      '#description' => $this->t('The root text color base. Default is <code>#01080D</code>.'),
    ];

    $form['styles']['input'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Input'),
    ];
    $form['styles']['input']['backgroundColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Background color'),
      '#default_value' => $styles['input']['backgroundColor'] ?? '#ffffff',
      '#description' => $this->t('The input background color. Default is <code>#ffffff</code>.'),
    ];
    $form['styles']['input']['borderRadius'] = [
      '#type' => 'number',
      '#title' => $this->t('Border radius'),
      '#default_value' => $styles['input']['borderRadius'] ?? 4,
      '#min' => 0,
      '#max' => 32,
      '#step' => 1,
      '#description' => $this->t('The input border radius. Default is <code>4px</code>.'),
    ];
    $form['styles']['input']['borderColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Border color'),
      '#default_value' => $styles['input']['borderColor'] ?? '#dadddd',
      '#description' => $this->t('The input border color. Default is <code>#daddddd</code>.'),
    ];
    $form['styles']['input']['focusBorderColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Focus border color'),
      '#default_value' => $styles['input']['focusBorderColor'] ?? '#0057ff',
      '#description' => $this->t('The input focus border color. Default is <code>#0057ff</code>.'),
    ];
    $form['styles']['input']['borderWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Border width'),
      '#default_value' => $styles['input']['borderWidth'] ?? 1,
      '#min' => 1,
      '#max' => 5,
      '#step' => 1,
      '#description' => $this->t('The input border width. Default is <code>1px</code>.'),
    ];
    $form['styles']['input']['textColorBase'] = [
      '#type' => 'color',
      '#title' => $this->t('Text color base'),
      '#default_value' => $styles['input']['textColorBase'] ?? '#01080d',
      '#description' => $this->t('The input text color base. Default is <code>#01080d</code>.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->getErrors()) {
      return;
    }
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['client_id']) || empty($values['secret'])) {
      return;
    }
    try {
      $this->fastlaneSdkFactory->getAccessToken([
        'client_id' => $values['client_id'],
        'secret' => $values['secret'],
        'mode' => $values['mode'],
      ], TRUE);
      $this->messenger()->addMessage($this->t('Connectivity to PayPal successfully verified.'));
    }
    catch (BadResponseException) {
      $this->messenger()->addError($this->t('Invalid <em>Client ID</em> or <em>Secret</em> specified.'));
      $form_state->setError($form['credentials']['client_id']);
      $form_state->setError($form['credentials']['secret']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    if ($form_state->getErrors()) {
      return;
    }
    $values = $form_state->getValue($form['#parents']);
    $values['allowed_brands'] = array_filter($values['allowed_brands']);
    $keys = [
      'client_id',
      'secret',
      'intent',
      'webhook_id',
      'allowed_brands',
      'styles',
    ];

    foreach ($keys as $key) {
      if (!isset($values[$key])) {
        continue;
      }
      $this->configuration[$key] = $values[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function collectsBillingInformation(): bool {
    // Fastlane will handle collecting billing information.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE): void {
    $order = NULL;
    try {
      $order = $payment->getOrder();
      if ($order === NULL) {
        throw new PaymentGatewayException('Cannot find the order.');
      }
      $payment_method = $payment->getPaymentMethod();
      if (!$payment_method || empty($payment_method->getRemoteId())) {
        throw new PaymentGatewayException('Cannot create the payment without the PayPal order ID.');
      }
      $sdk = $this->fastlaneSdkFactory->get($this->configuration);
      try {
        // Ensure the PayPal order is up to date and in sync with Drupal.
        $response = $sdk->createOrder($order);
        $paypal_order = Json::decode($response->getBody()->getContents());
        $order->setData('paypal_order_id', $paypal_order['id']);
        $order->setRefreshState(OrderInterface::REFRESH_SKIP);
        $order->save();
      }
      catch (BadResponseException $exception) {
        throw new PaymentGatewayException($exception->getMessage());
      }
      // @todo COMPLETED, we are observing, which does not exist on Checkout.
      // We need to confirm this list.
      if (!in_array($paypal_order['status'], [
        'APPROVED',
        'SAVED',
        'COMPLETED',
      ])) {
        throw new PaymentGatewayException(sprintf('Wrong remote order status. Expected: "approved"|"saved"|"completed", Actual: %s.', $paypal_order['status']));
      }
      $intent = $this->configuration['intent'];
      try {
        if ($intent === 'authorize') {
          $remote_payment = $paypal_order['purchase_units'][0]['payments']['authorizations'][0];
        }
        else {
          $remote_payment = $paypal_order['purchase_units'][0]['payments']['captures'][0];
        }
        $payment->setRemoteId($remote_payment['id']);
      }
      catch (BadResponseException $exception) {
        throw new PaymentGatewayException($exception->getMessage());
      }
      $remote_state = strtolower($remote_payment['status']);
      if (in_array($remote_state, ['denied', 'expired', 'declined'])) {
        throw new HardDeclineException(sprintf('Could not %s the payment for order %s. Remote payment state: %s', $intent, $order->id(), $remote_state));
      }
      $state = $this->mapPaymentState($intent, $remote_state);
      // If we couldn't find a state to map to, stop here.
      if (!$state) {
        $this->logger->debug('PayPal remote payment debug: <pre>@remote_payment</pre>', ['@remote_payment' => Json::encode($remote_payment)]);
        throw new PaymentGatewayException(sprintf('The PayPal payment is in a state we cannot handle. Remote state: %s.', $remote_state));
      }
      // Special handling of the "pending" state, if the order is "pending review"
      // we allow the order to go "through" to give a chance to the merchant
      // to accept the payment, in case manual review is needed.
      if ($state === 'pending' && $remote_state === 'pending') {
        $reason = $remote_payment['status_details']['reason'];
        if ($reason === 'PENDING_REVIEW') {
          $state = 'authorization';
        }
        else {
          throw new PaymentGatewayException(sprintf('The PayPal payment is pending. Reason: %s.', $reason));
        }
      }
      $payment_amount = Price::fromArray([
        'number' => $remote_payment['amount']['value'],
        'currency_code' => $remote_payment['amount']['currency_code'],
      ]);
      $payment->setAmount($payment_amount);
      $payment->setState($state);
      $payment->setRemoteId($remote_payment['id']);
      $payment->setRemoteState($remote_state);
      $payment->save();
    }
    catch (\Throwable $e) {
      if ($order !== NULL) {
        $order->set('payment_method', NULL);
        $order->save();
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, ?Price $amount = NULL): void {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    if ($amount === NULL) {
      throw new PaymentGatewayException('An amount is needed to capture a payment.');
    }
    $remote_id = $payment->getRemoteId();
    $params = [
      'amount' => [
        'value' => Calculator::trim($amount->getNumber()),
        'currency_code' => $amount->getCurrencyCode(),
      ],
    ];

    if ($amount->equals($payment->getAmount())) {
      $params['final_capture'] = TRUE;
    }

    try {
      $sdk = $this->fastlaneSdkFactory->get($this->configuration);

      // If the payment was authorized more than 3 days ago, attempt to
      // reauthorize it.
      if (($this->time->getRequestTime() >= ($payment->getAuthorizedTime() + (86400 * 3))) && !$payment->isExpired()) {
        $sdk->reAuthorizePayment($remote_id, ['amount' => $params['amount']]);
      }

      $response = $sdk->capturePayment($remote_id, $params);
      $response = Json::decode($response->getBody()->getContents());
    }
    catch (BadResponseException $exception) {
      Error::logException($this->logger, $exception);
      throw new PaymentGatewayException('An error occurred while capturing the authorized payment.');
    }
    $remote_state = strtolower($response['status']);
    $state = $this->mapPaymentState('capture', $remote_state);

    if (!$state) {
      throw new PaymentGatewayException('Unhandled payment state.');
    }
    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->setRemoteId($response['id']);
    $payment->setRemoteState($remote_state);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment): void {
    $this->assertPaymentState($payment, ['authorization']);
    try {
      $sdk = $this->fastlaneSdkFactory->get($this->configuration);
      $response = $sdk->voidPayment($payment->getRemoteId());
    }
    catch (BadResponseException $exception) {
      Error::logException($this->logger, $exception);
      throw new PaymentGatewayException('An error occurred while voiding the payment.');
    }
    if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
      $payment->setState('authorization_voided');
      $payment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, ?Price $amount = NULL): void {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    if ($amount === NULL) {
      throw new PaymentGatewayException('An amount is needed to refund a payment.');
    }
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount?->add($amount) ?? $amount;
    $params = [
      'amount' => [
        'value' => Calculator::trim($amount->getNumber()),
        'currency_code' => $amount->getCurrencyCode(),
      ],
    ];
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }
    try {
      $sdk = $this->fastlaneSdkFactory->get($this->configuration);
      $response = $sdk->refundPayment($payment->getRemoteId(), $params);
      $response = Json::decode($response->getBody()->getContents());
    }
    catch (BadResponseException $exception) {
      Error::logException($this->logger, $exception);
      throw new PaymentGatewayException('An error occurred while refunding the payment.');
    }

    if (strtolower($response['status']) !== 'completed') {
      throw new PaymentGatewayException(sprintf('Invalid state returned by PayPal. Expected: ("%s"), Actual: ("%s").', 'COMPLETED', $response['status']));
    }
    $payment->setRemoteState($response['status']);
    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request): void {
    // @todo This needs testing with Fastlane transactions.
    // This is basically a clone of the Checkout implementation.
    $request_body = Json::decode($request->getContent());
    $this->logger->debug('Incoming webhook message: <pre>@data</pre>', [
      '@data' => Json::encode($request_body),
    ]);
    $supported_events = [
      'PAYMENT.AUTHORIZATION.VOIDED',
      'PAYMENT.CAPTURE.COMPLETED',
      'PAYMENT.CAPTURE.REFUNDED',
      'PAYMENT.CAPTURE.PENDING',
      'PAYMENT.CAPTURE.DENIED',
    ];

    // Ignore unsupported events.
    if (!isset($request_body['event_type']) ||
      !in_array($request_body['event_type'], $supported_events, TRUE)) {
      return;
    }

    try {
      $sdk = $this->fastlaneSdkFactory->get($this->configuration);
      $parameters = [
        'auth_algo' => $request->headers->get('PAYPAL-AUTH-ALGO'),
        'cert_url' => $request->headers->get('PAYPAL-CERT-URL'),
        'transmission_id' => $request->headers->get('PAYPAL-TRANSMISSION-ID'),
        'transmission_sig' => $request->headers->get('PAYPAL-TRANSMISSION-SIG'),
        'transmission_time' => $request->headers->get('PAYPAL-TRANSMISSION-TIME'),
        'webhook_id' => $this->configuration['webhook_id'],
        'webhook_event' => $request_body,
      ];
      $signature_request = $sdk->verifyWebhookSignature($parameters);
      $response = Json::decode($signature_request->getBody());

      // If the webhook signature could not be successfully verified, stop here.
      if (strtolower($response['verification_status']) !== 'success') {
        $this->logger->error('An error occurred while trying to verify the webhook signature: <pre>@response</pre>', [
          '@response' => Json::encode($response),
        ]);
        return;
      }
      // Unfortunately, we need to use the "custom_id" (i.e. the order_id) for
      // retrieving the payment associated to this webhook event since the
      // resource id might differ from our "remote_id".
      $order_id = $request_body['resource']['custom_id'];
      /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      // Note that we don't use the loadMultipleByOrder() method on the payment
      // storage since we don't actually need to load the order.
      // This assumes the last payment is the right one.
      $payment_ids = $payment_storage->getQuery()
        ->condition('order_id', $order_id)
        ->accessCheck(FALSE)
        ->sort('payment_id', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!$payment_ids) {
        $this->logger->error('Could not find a payment transaction in Drupal for the order ID @order_id.', [
          '@order_id' => $order_id,
        ]);
        return;
      }
      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = $payment_storage->load(reset($payment_ids));
      $amount = Price::fromArray([
        'number' => $request_body['resource']['amount']['value'],
        'currency_code' => $request_body['resource']['amount']['currency_code'],
      ]);
      // Synchronize the remote ID and remote state.
      $payment->setRemoteId($request_body['resource']['id']);
      $payment->setRemoteState($request_body['resource']['status']);

      switch ($request_body['event_type']) {
        case 'PAYMENT.AUTHORIZATION.VOIDED':
          if ($payment->getState()->getId() !== 'authorization_voided') {
            $payment->setState('authorization_voided');
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.DENIED':
          if ($payment->getState()->getId() !== 'authorization_voided') {
            $payment->setState('capture_denied');
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.COMPLETED':
          // Ignore completed payments.
          if ($payment->getState()->getId() !== 'completed' ||
            $amount->lessThan($payment->getAmount())) {
            $payment->setAmount($amount);
            $payment->setState('completed');
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.REFUNDED':
          if ($amount->lessThan($payment->getAmount())) {
            $payment->setState('partially_refunded');
          }
          else {
            $payment->setState('refunded');
          }
          if (!$payment->getRefundedAmount() ||
            !$payment->getRefundedAmount()->equals($amount)) {
            $payment->setRefundedAmount($amount);
            $payment->save();
          }
          break;

        case 'PAYMENT.CAPTURE.PENDING':
          if ($payment->getState()->getId() !== 'pending') {
            $payment->setAmount($amount);
            $payment->setState('pending');
            $payment->save();
          }
          break;
      }
    }
    catch (BadResponseException $exception) {
      Error::logException($this->logger, $exception);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request): void {
    throw new \RuntimeException('Return is not supported.');
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details): void {
    $fastlane_token = Json::decode($payment_details['fastlane']['fastlane_payment_token'] ?? NULL);
    if (empty($fastlane_token['id'])) {
      throw new PaymentGatewayException('The fastlane payment token is not valid.');
    }
    $payment_method->setRemoteId($fastlane_token['id']);
    // @todo we should support vaulting/reusable.
    $payment_method->setReusable(FALSE);
    $payment_method->set('paypal_fastlane_data', json_encode($fastlane_token['paymentSource'] ?? []));
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method): void {
    $payment_method->delete();
  }

  /**
   * Map a PayPal payment state to a local one.
   *
   * @param string $type
   *   The payment type. One of "authorize" or "capture".
   * @param string $remote_state
   *   The PayPal remote payment state.
   *
   * @return string
   *   The corresponding local payment state.
   */
  protected function mapPaymentState(string $type, string $remote_state): string {
    $mapping = [
      'authorize' => [
        'created' => 'authorization',
        'pending' => 'pending',
        'voided' => 'authorization_voided',
        'expired' => 'authorization_expired',
      ],
      'capture' => [
        'completed' => 'completed',
        'pending' => 'pending',
        'partially_refunded' => 'partially_refunded',
      ],
    ];
    return $mapping[$type][$remote_state] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function build(OrderInterface $order): array {
    $element = [];

    $options = [
      'query' => [
        'client-id' => $this->getClientId(),
        'intent' => $this->getIntent(),
        'commit' => 'true',
        'currency' => $order->getTotalPrice()?->getCurrencyCode(),
      ],
    ];
    $options['query']['components'] = 'fastlane';
    $element['#attached']['library'][] = 'commerce_paypal/paypal_fastlane';
    $element_id = Html::getUniqueId('paypal-fastlane-container');

    $token = $this->getToken($order);

    $language = $this->currentUser->getPreferredLangcode();
    $fastlane_locale = NULL;
    // Fastlane only supports 4 languages currently.
    if (in_array($language, ['en', 'es', 'fr', 'zh-hans', 'zh-hant'])) {
      $fastlane_locale = substr($language, 0, 2) . '_us';
    }

    $allowed_billing_countries = $order->getStore()?->getBillingCountries() ?? [];
    $allowed_shipping_countries = $this->getOrderShippingCountries($order);

    $styles = $this->getStyles();
    $styles['root']['fontSizeBase'] .= 'px';
    $styles['input']['borderRadius'] .= 'px';
    $styles['input']['borderWidth'] .= 'px';

    $element['#attached']['drupalSettings']['paypalFastlane'][$order->id()] = [
      'src' => Url::fromUri($this->getSdkUri(), $options)->toString(),
      'clientToken' => $token['access_token'],
      'elementId' => $element_id,
      'allowedBillingCountries' => $allowed_billing_countries,
      'allowedShippingCountries' => $allowed_shipping_countries,
      'allowedBrands' => array_keys($config['allowed_brands'] ?? []),
      'hasShipments' => $this->isShippable($order),
      'locale' => $fastlane_locale,
      'styles' => $styles,
    ];
    $element += [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#weight' => 100,
      '#attributes' => [
        'class' => ['paypal-fastlane-container'],
        'id' => $element_id,
      ],
    ];

    return $element;
  }

  /**
   * Get the shipping countries for the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   The valid shipping countries.
   */
  protected function getOrderShippingCountries(OrderInterface $order): array {
    $countries = [];
    if ($this->isShippable($order)) {
      foreach ($order->getStore()?->get('shipping_countries') as $countryItem) {
        $countries[] = $countryItem->value;
      }
    }
    return $countries;
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
