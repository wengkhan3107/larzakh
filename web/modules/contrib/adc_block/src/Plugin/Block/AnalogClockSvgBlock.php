<?php

namespace Drupal\adc_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a fully configurable Analog SVG Clock block.
 *
 * @Block(
 *   id = "svg_clock_analog_dynamic",
 *   admin_label = @Translation("SVG Clock - Analog"),
 *   category = @Translation("Analog Digital Clock")
 * )
 */
class AnalogClockSvgBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Clock dimensions.
      'clock_size' => 300,
      'clock_border_width' => 4,

      // Colors - Background.
      'bg_type' => 'solid',
      'bg_color' => '#ffffff',
      'bg_gradient_start' => '#667eea',
      'bg_gradient_end' => '#764ba2',
      'bg_gradient_type' => 'linear',

      // Colors - Border.
      'border_color' => '#333333',
      'border_type' => 'solid',

      // Numbers.
      'show_numbers' => TRUE,
      'number_type' => 'arabic',
      'number_color' => '#333333',
      'number_size' => 12,

      // Hour markers/ticks.
      'show_hour_markers' => TRUE,
      'hour_marker_color' => '#666666',
      'hour_marker_width' => 3,
      'hour_marker_length' => 6,

      // Minute markers/ticks.
      'show_minute_markers' => TRUE,
      'minute_marker_color' => '#999999',
      'minute_marker_width' => 1,
      'minute_marker_length' => 4,

      // Hour hand.
      'hour_hand_color' => '#333333',
      'hour_hand_width' => 6,
      'hour_hand_length' => 45,

      // Minute hand.
      'minute_hand_color' => '#555555',
      'minute_hand_width' => 4,
      'minute_hand_length' => 70,

      // Second hand.
      'second_hand_color' => '#e74c3c',
      'second_hand_width' => 2,
      'second_hand_length' => 85,
      'show_second_hand' => TRUE,

      // Center dot.
      'center_dot_size' => 8,
      'center_dot_color' => '#333333',
      'center_dot_border_width' => 0,
      'center_dot_border_color' => '#ffffff',

      // Effects.
      'enable_glow' => FALSE,
      'glow_color' => '#00ffff',
      'glow_intensity' => 3,

      'enable_shadow' => FALSE,
      'shadow_color' => '#000000',
      'shadow_blur' => 10,
      'shadow_opacity' => 30,

      // Timezone.
      'timezone' => 'default',
      'custom_timezone' => 'UTC',

    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    // Clock Dimensions.
    $form['dimensions'] = [
      '#type' => 'details',
      '#title' => $this->t('Clock Dimensions'),
      '#open' => TRUE,
    ];

    $form['dimensions']['clock_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Clock size (diameter in pixels)'),
      '#default_value' => $config['clock_size'],
      '#min' => 100,
      '#max' => 1000,
      '#required' => TRUE,
    ];

    $form['dimensions']['clock_border_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Border width (pixels)'),
      '#default_value' => $config['clock_border_width'],
      '#min' => 0,
      '#max' => 20,
    ];

    // Background Colors.
    $form['background'] = [
      '#type' => 'details',
      '#title' => $this->t('Background'),
      '#open' => TRUE,
    ];

    $form['background']['bg_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Background type'),
      '#options' => [
        'solid' => $this->t('Solid color'),
        'gradient' => $this->t('Gradient'),
      ],
      '#default_value' => $config['bg_type'],
    ];

    $form['background']['bg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background color'),
      '#default_value' => $config['bg_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[background][bg_type]"]' => ['value' => 'solid'],
        ],
      ],
    ];

    $form['background']['bg_gradient_start'] = [
      '#type' => 'color',
      '#title' => $this->t('Gradient start color'),
      '#default_value' => $config['bg_gradient_start'],
      '#states' => [
        'visible' => [
          ':input[name="settings[background][bg_type]"]' => ['value' => 'gradient'],
        ],
      ],
    ];

    $form['background']['bg_gradient_end'] = [
      '#type' => 'color',
      '#title' => $this->t('Gradient end color'),
      '#default_value' => $config['bg_gradient_end'],
      '#states' => [
        'visible' => [
          ':input[name="settings[background][bg_type]"]' => ['value' => 'gradient'],
        ],
      ],
    ];

    $form['background']['bg_gradient_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Gradient type'),
      '#options' => [
        'linear' => $this->t('Linear'),
        'radial' => $this->t('Radial'),
      ],
      '#default_value' => $config['bg_gradient_type'],
      '#states' => [
        'visible' => [
          ':input[name="settings[background][bg_type]"]' => ['value' => 'gradient'],
        ],
      ],
    ];

    // Border.
    $form['border'] = [
      '#type' => 'details',
      '#title' => $this->t('Border'),
      '#open' => FALSE,
    ];

    $form['border']['border_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Border type'),
      '#options' => [
        'solid' => $this->t('Solid color'),
        'rainbow' => $this->t('Rainbow gradient'),
      ],
      '#default_value' => $config['border_type'],
    ];

    $form['border']['border_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Border color'),
      '#default_value' => $config['border_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[border][border_type]"]' => ['value' => 'solid'],
        ],
      ],
    ];

    // Numbers.
    $form['numbers'] = [
      '#type' => 'details',
      '#title' => $this->t('Numbers'),
      '#open' => FALSE,
    ];

    $form['numbers']['show_numbers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show hour numbers'),
      '#default_value' => $config['show_numbers'],
    ];

    $form['numbers']['number_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Number type'),
      '#options' => [
        'arabic' => $this->t('Arabic (1-12)'),
        'roman' => $this->t('Roman (I-XII)'),
        'dots' => $this->t('Dots only'),
      ],
      '#default_value' => $config['number_type'],
      '#states' => [
        'visible' => [
          ':input[name="settings[numbers][show_numbers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['numbers']['number_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Number color'),
      '#default_value' => $config['number_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[numbers][show_numbers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['numbers']['number_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Number size (pixels)'),
      '#default_value' => $config['number_size'],
      '#min' => 8,
      '#max' => 40,
      '#states' => [
        'visible' => [
          ':input[name="settings[numbers][show_numbers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Hour Markers.
    $form['hour_markers'] = [
      '#type' => 'details',
      '#title' => $this->t('Hour Markers (Ticks)'),
      '#open' => FALSE,
    ];

    $form['hour_markers']['show_hour_markers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show hour markers'),
      '#default_value' => $config['show_hour_markers'],
    ];

    $form['hour_markers']['hour_marker_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Hour marker color'),
      '#default_value' => $config['hour_marker_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[hour_markers][show_hour_markers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['hour_markers']['hour_marker_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Hour marker width (pixels)'),
      '#default_value' => $config['hour_marker_width'],
      '#min' => 1,
      '#max' => 10,
      '#states' => [
        'visible' => [
          ':input[name="settings[hour_markers][show_hour_markers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['hour_markers']['hour_marker_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Hour marker length (pixels)'),
      '#default_value' => $config['hour_marker_length'],
      '#min' => 5,
      '#max' => 50,
      '#states' => [
        'visible' => [
          ':input[name="settings[hour_markers][show_hour_markers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Minute Markers.
    $form['minute_markers'] = [
      '#type' => 'details',
      '#title' => $this->t('Minute Markers (Ticks)'),
      '#open' => FALSE,
    ];

    $form['minute_markers']['show_minute_markers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show minute markers'),
      '#default_value' => $config['show_minute_markers'],
    ];

    $form['minute_markers']['minute_marker_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Minute marker color'),
      '#default_value' => $config['minute_marker_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[minute_markers][show_minute_markers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['minute_markers']['minute_marker_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Minute marker width (pixels)'),
      '#default_value' => $config['minute_marker_width'],
      '#min' => 1,
      '#max' => 5,
      '#states' => [
        'visible' => [
          ':input[name="settings[minute_markers][show_minute_markers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['minute_markers']['minute_marker_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minute marker length (pixels)'),
      '#default_value' => $config['minute_marker_length'],
      '#min' => 3,
      '#max' => 30,
      '#states' => [
        'visible' => [
          ':input[name="settings[minute_markers][show_minute_markers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Hour Hand.
    $form['hour_hand'] = [
      '#type' => 'details',
      '#title' => $this->t('Hour Hand'),
      '#open' => FALSE,
    ];

    $form['hour_hand']['hour_hand_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Hour hand color'),
      '#default_value' => $config['hour_hand_color'],
    ];

    $form['hour_hand']['hour_hand_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Hour hand width (pixels)'),
      '#default_value' => $config['hour_hand_width'],
      '#min' => 1,
      '#max' => 20,
    ];

    $form['hour_hand']['hour_hand_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Hour hand length (% of radius)'),
      '#default_value' => $config['hour_hand_length'],
      '#min' => 20,
      '#max' => 90,
      '#description' => $this->t('Percentage of clock radius (0-100)'),
    ];

    // Minute Hand.
    $form['minute_hand'] = [
      '#type' => 'details',
      '#title' => $this->t('Minute Hand'),
      '#open' => FALSE,
    ];

    $form['minute_hand']['minute_hand_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Minute hand color'),
      '#default_value' => $config['minute_hand_color'],
    ];

    $form['minute_hand']['minute_hand_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Minute hand width (pixels)'),
      '#default_value' => $config['minute_hand_width'],
      '#min' => 1,
      '#max' => 15,
    ];

    $form['minute_hand']['minute_hand_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minute hand length (% of radius)'),
      '#default_value' => $config['minute_hand_length'],
      '#min' => 30,
      '#max' => 95,
      '#description' => $this->t('Percentage of clock radius (0-100)'),
    ];

    // Second Hand.
    $form['second_hand'] = [
      '#type' => 'details',
      '#title' => $this->t('Second Hand'),
      '#open' => FALSE,
    ];

    $form['second_hand']['show_second_hand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show second hand'),
      '#default_value' => $config['show_second_hand'],
    ];

    $form['second_hand']['second_hand_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Second hand color'),
      '#default_value' => $config['second_hand_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[second_hand][show_second_hand]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['second_hand']['second_hand_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Second hand width (pixels)'),
      '#default_value' => $config['second_hand_width'],
      '#min' => 1,
      '#max' => 10,
      '#states' => [
        'visible' => [
          ':input[name="settings[second_hand][show_second_hand]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['second_hand']['second_hand_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Second hand length (% of radius)'),
      '#default_value' => $config['second_hand_length'],
      '#min' => 40,
      '#max' => 98,
      '#description' => $this->t('Percentage of clock radius (0-100)'),
      '#states' => [
        'visible' => [
          ':input[name="settings[second_hand][show_second_hand]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Center Dot.
    $form['center_dot'] = [
      '#type' => 'details',
      '#title' => $this->t('Center Dot'),
      '#open' => FALSE,
    ];

    $form['center_dot']['center_dot_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Center dot size (radius in pixels)'),
      '#default_value' => $config['center_dot_size'],
      '#min' => 0,
      '#max' => 30,
    ];

    $form['center_dot']['center_dot_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Center dot color'),
      '#default_value' => $config['center_dot_color'],
    ];

    $form['center_dot']['center_dot_border_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Center dot border width (pixels)'),
      '#default_value' => $config['center_dot_border_width'],
      '#min' => 0,
      '#max' => 10,
    ];

    $form['center_dot']['center_dot_border_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Center dot border color'),
      '#default_value' => $config['center_dot_border_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[center_dot][center_dot_border_width]"]' => ['!value' => '0'],
        ],
      ],
    ];

    // Effects.
    $form['effects'] = [
      '#type' => 'details',
      '#title' => $this->t('Visual Effects'),
      '#open' => FALSE,
    ];

    $form['effects']['enable_glow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable glow effect'),
      '#default_value' => $config['enable_glow'],
    ];

    $form['effects']['glow_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Glow color'),
      '#default_value' => $config['glow_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[effects][enable_glow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['effects']['glow_intensity'] = [
      '#type' => 'number',
      '#title' => $this->t('Glow intensity'),
      '#default_value' => $config['glow_intensity'],
      '#min' => 1,
      '#max' => 10,
      '#states' => [
        'visible' => [
          ':input[name="settings[effects][enable_glow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['effects']['enable_shadow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable drop shadow'),
      '#default_value' => $config['enable_shadow'],
    ];

    $form['effects']['shadow_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Shadow color'),
      '#default_value' => $config['shadow_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[effects][enable_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['effects']['shadow_blur'] = [
      '#type' => 'number',
      '#title' => $this->t('Shadow blur (pixels)'),
      '#default_value' => $config['shadow_blur'],
      '#min' => 0,
      '#max' => 50,
      '#states' => [
        'visible' => [
          ':input[name="settings[effects][enable_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['effects']['shadow_opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Shadow opacity (%)'),
      '#default_value' => $config['shadow_opacity'],
      '#min' => 0,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="settings[effects][enable_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Timezone.
    $form['timezone_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Timezone'),
      '#open' => FALSE,
    ];

    $timezones = [
      'default' => $this->t('Site default timezone'),
      'user' => $this->t('User timezone'),
      'custom' => $this->t('Custom timezone'),
    ];

    $form['timezone_settings']['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone source'),
      '#options' => $timezones,
      '#default_value' => $config['timezone'],
    ];

    $form['timezone_settings']['custom_timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Custom timezone'),
      '#options' => $this->getTimezoneOptions(),
      '#default_value' => $config['custom_timezone'],
      '#states' => [
        'visible' => [
          ':input[name="settings[timezone_settings][timezone]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    // Flatten nested values.
    foreach (['dimensions', 'background', 'border', 'numbers', 'hour_markers',
      'minute_markers', 'hour_hand', 'minute_hand', 'second_hand',
      'center_dot', 'effects', 'timezone_settings',
    ] as $group) {
      if (isset($values[$group])) {
        foreach ($values[$group] as $key => $value) {
          $this->configuration[$key] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;
    $block_id = 'analog-clock-' . uniqid();

    return [
      '#theme' => 'svg_clock_analog_dynamic',
      '#block_id' => $block_id,
      '#config' => $config,
      '#attached' => [
        'library' => ['adc_block/clocks'],
      ],
    ];
  }

  /**
   * Get timezone options.
   */
  protected function getTimezoneOptions() {
    $timezones = [];
    foreach (\DateTimeZone::listIdentifiers() as $timezone) {
      $timezones[$timezone] = str_replace('_', ' ', $timezone);
    }
    return $timezones;
  }

}
