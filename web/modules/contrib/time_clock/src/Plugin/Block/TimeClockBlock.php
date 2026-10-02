<?php

namespace Drupal\time_clock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Time Clock' block.
 *
 * @Block(
 *   id = "time_clock",
 *   admin_label = @Translation("Time Clock"),
 *   category = @Translation("General"),
 * )
 */
class TimeClockBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new WorkspaceSwitcherBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatter $dateFormatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    $timezone = $config['timezone'];
    $dateformat = $config['dateformat'];
    if ($dateformat == 1) {
      $date_format = 'm/d/Y';
    }
    elseif ($dateformat == 2) {
      $date_format = 'd/m/Y';
    }
    elseif ($dateformat == 3) {
      $date_format = 'Y/m/d';
    }
    else {
      $date_format = 'jS M, Y';
    }
    $timeformat = $config['timeformat'];
    if ($timeformat) {
      $time_format = 'H:i:s';
    }
    else {
      $time_format = 'g:i:s A';
    }
    $items = [];
    if (!empty($timezone)) {
      // Set the default timezone value.
      date_default_timezone_set($timezone);
      $dateview = $config['dateview'];
      $date = '';
      if ($dateview) {
        $date = $this->dateFormatter->format(time(), 'custom', $date_format);
      }
      $time = $this->dateFormatter->format(time(), 'custom', $time_format);

      $items = [
        'timezone' => $timezone,
        'date' => $date,
        'time' => $time,
        'current_time' => [
          '#lazy_builder' => [
            self::class . '::generateTimestamp', [$time_format, $timezone],
          ],
          '#create_placeholder' => TRUE,
        ],
      ];
    }
    $data['#theme'] = 'time_clock';
    $data['#attached']['library'] = ['time_clock/clock-style'];
    $data['#attached']['drupalSettings']['timezone'] = $timezone;
    $data['#attached']['drupalSettings']['timeformat'] = $timeformat;
    $data['#data'] = $items;
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['generateTimestamp'];
  }

  /**
   * Callback to return a current time.
   *
   * @param string $format
   *   Specific time format.
   * @param string $zone
   *   Specific timezone.
   *
   * @return array
   *   Return a current time.
   */
  public static function generateTimestamp($format, $zone) {
    date_default_timezone_set($zone);
    $dt['lazy_builder_time'] = [
      '#markup' => date($format, time()),
      '#cache' => [
        'tags' => [
          'config:block.block.time_clock',
        ],
        'contexts' => [
          'url',
        ],
        'max-age' => 0,
      ],
    ];
    return $dt;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    // Retrieve all configuration terms for this block.
    $config = $this->getConfiguration();
    // Form fields to the block configuration form.
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#options' => \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.1.0', fn() => \Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsListByRegion(), fn() => system_time_zones(NULL, TRUE)),
      '#default_value' => $config['timezone'] ?? \Drupal::config('system.date')->get('timezone.default'),
      '#required' => TRUE,
    ];
    $form['dateformat'] = [
      '#type' => 'select',
      '#title' => $this->t('Date Format'),
      '#default_value' => $config['dateformat'] ?? 0,
      '#options' => [
        $this->t('Default'),
        '12/31/1980',
        '31/12/1980',
        '1980/12/31',
      ],
    ];
    $form['dateview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show date'),
      '#default_value' => $config['dateview'] ?? 1,
    ];
    $form['timeformat'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show time in 24 hours'),
      '#default_value' => $config['timeformat'] ?? 0,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save the settings when the form is submitted.
    $this->setConfigurationValue('timezone', $form_state->getValue('timezone'));
    $this->setConfigurationValue('dateformat', $form_state->getValue('dateformat'));
    $this->setConfigurationValue('timeformat', $form_state->getValue('timeformat'));
    $this->setConfigurationValue('dateview', $form_state->getValue('dateview'));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $timezone = $form_state->getValue('timezone');
    if (empty($timezone)) {
      $form_state->setErrorByName('timezone', $this->t('Select a timezone.'));
    }
  }

}
