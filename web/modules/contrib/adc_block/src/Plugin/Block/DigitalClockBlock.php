<?php

namespace Drupal\adc_block\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides a Block to display a Digital Clock.
 *
 * @Block(
 * id = "adc_block_digital_block",
 * admin_label = @Translation("Digital Clock")
 * )
 */
class DigitalClockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a DigitalClockBlock object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
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
      $container->get('entity_type.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'timezone' => 'system_timezone',
      'layout' => 'layout1',
      'show_date' => FALSE,
      'date_format' => 'medium',
      'description_text' => '',
      'container_backgraound_color' => '#ffffff',
      'time_color' => '#000000',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $layout = $config['layout'] ?? 'layout1';
    $show_date = $config['show_date'] ?? FALSE;

    // Resolve Timezone.
    $timezone_setting = $config['timezone'] ?? 'system_timezone';
    $timezone = $timezone_setting;
    if ($timezone_setting === 'system_timezone') {
      $timezone = date_default_timezone_get();
    }
    elseif ($timezone_setting === 'local_timezone') {
      $timezone = '';
    }

    // Resolve Date.
    $current_date = '';
    if ($show_date) {
      $format = $config['date_format'] ?? 'medium';
      $custom_format = ($format === 'custom') ? ($config['custom_date_format'] ?? 'Y-m-d') : '';
      $current_date = $this->dateFormatter->format(
        $this->time->getRequestTime(),
        ($format === 'custom' ? 'custom' : $format),
        $custom_format
      );
    }

    $data = $this->getLayoutData($layout, $config, $current_date, $timezone);

    return [
      '#theme' => 'digital_clock',
      '#data' => $data,
      '#attached' => [
        'library' => ['adc_block/adc_block.digital'],
        'drupalSettings' => [
          'config_data' => $data,
        ],
      ],
    ];
  }

  /**
   * Maps configuration and presets to layout data.
   */
  private function getLayoutData($layout, array $config, $current_date, $timezone) {
    $base_data = [
      'timezone' => $timezone,
      'current_date' => $current_date,
      'description_text' => Html::escape($config['description_text'] ?? ''),
    ];

    if ($layout === 'layout1') {
      return array_merge($base_data, [
        'layout' => 'custom',
        'show_date' => 0,
        'container_backgraound_color' => '#ffffff',
        'time_color' => '#000000',
        'date_color' => '#fbc1c1',
        'description_color' => '#850000',
        'time_font_size' => '50',
        'date_font_size' => '50',
        'description_font_size' => '42',
      ]);
    }

    // Custom layout mapping.
    return array_merge($base_data, [
      'layout' => 'custom',
      'show_date' => $config['show_date'] ?? FALSE,
      'container_backgraound_color' => $config['container_backgraound_color'] ?? '#ffffff',
      'container_box_shadow_enable' => $config['container_box_shadow_enable'] ?? FALSE,
      'container_box_shadow' => $config['container_box_shadow'] ?? '#000000',
      'time_color' => $config['time_color'] ?? '#000000',
      'date_color' => $config['date_color'] ?? '#000000',
      'description_color' => $config['description_color'] ?? '#000000',
      'time_font_size' => $config['time_font_size'] ?? '24',
      'date_font_size' => $config['date_font_size'] ?? '26',
      'description_font_size' => $config['description_font_size'] ?? '25',
      'time_text_shadow_enable' => $config['time_text_shadow_enable'] ?? FALSE,
      'time_text_shadow' => $config['time_text_shadow'] ?? '#ffffff',
      'date_text_shadow_enable' => $config['date_text_shadow_enable'] ?? FALSE,
      'date_text_shadow' => $config['date_text_shadow'] ?? '#ffffff',
      'description_text_shadow_enable' => $config['description_text_shadow_enable'] ?? FALSE,
      'description_text_shadow' => $config['description_text_shadow'] ?? '#ffffff',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->getConfiguration();

    $timezones = array_merge([
      'system_timezone' => $this->t('System Timezone'),
      'local_timezone' => $this->t('Local Timezone'),
    ], TimeZoneFormHelper::getOptionsListByRegion());

    $form['regional_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Regional settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['regional_settings']['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Default time zone'),
      '#default_value' => $config['timezone'] ?? 'system_timezone',
      '#options' => $timezones,
    ];

    $form['layout_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['layout_settings']['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Layout'),
      '#default_value' => $config['layout'] ?? 'layout1',
      '#options' => [
        'layout1' => $this->t('Layout 1'),
        'custom' => $this->t('Custom Layout'),
      ],
    ];

    $custom_visible = ['visible' => [':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom']]];

    $form['layout_settings']['show_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Date'),
      '#default_value' => $config['show_date'] ?? FALSE,
      '#states' => $custom_visible,
    ];

    // Load available date formats.
    $date_formats = [];
    $formats = $this->entityTypeManager->getStorage('date_format')->loadMultiple();
    foreach ($formats as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', [
        '@name' => $value->label(),
        '@date' => $this->dateFormatter->format($this->time->getRequestTime(), $machine_name),
      ]);
    }
    $date_formats['custom'] = $this->t('Custom');

    $form['layout_settings']['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats,
      '#default_value' => $config['date_format'] ?? 'medium',
      '#states' => [
        'visible' => [
          ':input[name="settings[layout_settings][show_date]"]' => ['checked' => TRUE],
          ':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['layout_settings']['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#default_value' => $config['custom_date_format'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[layout_settings][date_format]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['layout_settings']['container_backgraound_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Container Background Color'),
      '#default_value' => $config['container_backgraound_color'] ?? '#ffffff',
      '#states' => $custom_visible,
    ];

    $form['layout_settings']['time_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Time Color'),
      '#default_value' => $config['time_color'] ?? '#000000',
      '#states' => $custom_visible,
    ];

    $form['description_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Description settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['description_settings']['description_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description Text'),
      '#default_value' => $config['description_text'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $groups = ['regional_settings', 'layout_settings', 'description_settings'];
    foreach ($groups as $group) {
      if ($values = $form_state->getValue($group)) {
        foreach ($values as $key => $value) {
          $this->configuration[$key] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
