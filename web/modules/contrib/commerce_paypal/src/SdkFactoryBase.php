<?php

namespace Drupal\commerce_paypal;

use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a base factory for our custom PayPal SDKs.
 */
abstract class SdkFactoryBase implements SdkFactoryInterface {

  /**
   * Array of all instantiated PayPal SDKs.
   *
   * @var \Drupal\commerce_paypal\SdkInterface[]
   */
  protected array $instances = [];

  /**
   * Constructs a new SdkFactory object.
   *
   * @param \Drupal\Core\Http\ClientFactory $clientFactory
   *   The client factory.
   * @param \Drupal\commerce_order\AdjustmentTransformerInterface $adjustmentTransformer
   *   The adjustment transformer.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirableFactory
   *   The key value expirable factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected ClientFactory $clientFactory,
    protected AdjustmentTransformerInterface $adjustmentTransformer,
    protected EventDispatcherInterface $eventDispatcher,
    protected ModuleHandlerInterface $moduleHandler,
    protected TimeInterface $time,
    protected RounderInterface $rounder,
    protected KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getAccessToken(array $config, bool $get_new = FALSE): ?string {
    // Generates a key for storing the OAuth2 token retrieved from PayPal.
    // This is useful in case multiple PayPal checkout gateway instances are
    // configured.
    $token_key = 'commerce_paypal.oauth2_token.' . md5($config['client_id'] . $config['secret']);
    $collection = $this->keyValueExpirableFactory->get('commerce_paypal');
    $access_token = $get_new ? NULL : $collection->get($token_key);
    if (!$access_token) {
      try {
        $options = [
          'base_uri' => $this->getBaseUri($config),
        ];
        $oauth_client = $this->clientFactory->fromOptions($options);
        $token_response = $oauth_client->post('/v1/oauth2/token', [
          'auth' => [$config['client_id'], $config['secret']],
          'form_params' => [
            'grant_type' => 'client_credentials',
          ],
        ]);
        $token_data = Json::decode($token_response->getBody()->getContents());
        $collection->setWithExpire($token_key, $token_data['access_token'], $token_data['expires_in']);
        return $token_data['access_token'];
      }
      catch (\Exception $e) {
        $this->logger->error('Failed getting an Oauth2 access token for PayPal with the following error: %error', ['%error' => $e->getMessage()]);
        throw $e;
      }
    }

    return $access_token;
  }

  /**
   * Gets a preconfigured HTTP client instance for use by the SDK.
   *
   * @param array $config
   *   The config for the client.
   *
   * @return \GuzzleHttp\Client
   *   The API client.
   */
  protected function getClient(array $config) {
    $attribution_id = (isset($config['payment_solution']) && $config['payment_solution'] === 'custom_card_fields') ? 'Centarro_Commerce_PCP' : 'CommerceGuys_Cart_SPB';
    $options = [
      'base_uri' => $this->getBaseUri($config),
      'headers' => [
        'PayPal-Partner-Attribution-Id' => $attribution_id,
        'Authorization' => 'Bearer ' . $this->getAccessToken($config) ?? '',
      ],
    ];
    return $this->clientFactory->fromOptions($options);
  }

}
