<?php

namespace Drupal\flood_settings\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Flood Settings module.
 */
class FloodSettings extends ConfigFormBase {

  const SETTINGS_KEY = 'user.flood';
  const DEFAULT_IP_LIMIT = 50;
  const DEFAULT_IP_WINDOW = 3600;
  const DEFAULT_USER_LIMIT = 5;
  const DEFAULT_USER_WINDOW = 21600;

  /**
   * FloodSettings constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   DateFormatterInterface Object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(protected DateFormatterInterface $dateFormatter, ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('date.formatter'),
      $container->get('config.factory'),
      $container->get('config.typed'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): array|string {
    return str_replace('.', '_', self::SETTINGS_KEY);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      self::SETTINGS_KEY,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::SETTINGS_KEY);

    $occurrenceLimits = [
      1,
      2,
      3,
      4,
      5,
      6,
      7,
      8,
      9,
      10,
      20,
      30,
      40,
      50,
      75,
      100,
      125,
      150,
      200,
      250,
      500,
    ];
    $durationLimits = [
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];

    $durationLimits_options = $this->buildOptions($durationLimits);
    $durationLimits_options[0] = $this->t('None (disabled)');
    ksort($durationLimits_options);

    $form['login'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login'),
    ];

    $form['login']['uid_only'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Username only'),
      '#default_value'  => $config->get('uid_only') ?? FALSE,
      '#description'    => $this->t('Register flood events based on the uid only, so they apply for any
      IP address. This is the most secure option.'),
    ];

    $form['login']['ip_limit'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Failed login (IP) limit'),
      '#default_value'  => $config->get('ip_limit') ?? self::DEFAULT_IP_LIMIT,
      '#options'        => array_combine($occurrenceLimits, $occurrenceLimits),
    ];

    $form['login']['ip_window'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Failed login (IP) window'),
      '#default_value'  => $config->get('ip_window') ?? self::DEFAULT_IP_WINDOW,
      '#options'        => $durationLimits_options,
    ];

    $form['login']['user_limit'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Failed login (username) limit'),
      '#default_value'  => $config->get('user_limit') ?? self::DEFAULT_USER_LIMIT,
      '#options'        => array_combine($occurrenceLimits, $occurrenceLimits),
    ];

    $form['login']['user_window'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Failed login (username) window'),
      '#default_value'  => $config->get('user_window') ?? self::DEFAULT_USER_WINDOW,
      '#options'        => $durationLimits_options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    foreach (['uid_only', 'ip_limit', 'ip_window', 'user_limit', 'user_window'] as $configKey) {
      $this->configFactory->getEditable(self::SETTINGS_KEY)
        ->set($configKey, $form_state->getValue($configKey))
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Provide DateFormatter interval.
   *
   * @param array $time_intervals
   *   Intervals time array.
   * @param int $granularity
   *   Granularity value.
   * @param string|null $langcode
   *   Langcode value.
   *
   * @return array
   *   Return an array.
   */
  protected function buildOptions(array $time_intervals, int $granularity = 2, ?string $langcode = NULL): array {
    $callback = function ($value) use ($granularity, $langcode) {
      return $this->dateFormatter->formatInterval($value, $granularity, $langcode);
    };
    return array_combine($time_intervals, array_map($callback, $time_intervals));
  }

}
