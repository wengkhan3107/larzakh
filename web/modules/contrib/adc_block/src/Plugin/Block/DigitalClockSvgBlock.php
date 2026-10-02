<?php

namespace Drupal\adc_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a fully configurable Digital Clock block.
 *
 * @Block(
 *   id = "svg_clock_digital_dynamic",
 *   admin_label = @Translation("SVG Clock - Digital"),
 *   category = @Translation("Analog Digital Clock")
 * )
 */
class DigitalClockSvgBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // Display options.
      'clock_width' => 350,
      'time_format' => '24',
      'show_date' => TRUE,
      'show_day' => TRUE,
      'show_seconds' => TRUE,

      // Date format.
      'date_format' => 'long',
      'custom_date_format' => 'F j, Y',
      'day_format' => 'full',

      // Background.
      'bg_type' => 'solid',
      'bg_color' => '#000000',
      'bg_gradient_start' => '#1a1a2e',
      'bg_gradient_end' => '#16213e',
      'bg_gradient_angle' => '135',

      // Border.
      'border_width' => 4,
      'border_color' => '#333333',
      'border_radius' => 10,
      'border_style' => 'solid',

      // Time colors.
      'time_color' => '#ff0000',
      'date_color' => '#ff0000',
      'day_color' => '#ff0000',

      // Font settings.
      'font_family' => 'monospace',
      'time_font_size' => 48,
      'date_font_size' => 18,
      'day_font_size' => 14,
      'letter_spacing' => 5,
      'font_weight' => 'bold',

      // Padding.
      'padding_top' => 20,
      'padding_right' => 20,
      'padding_bottom' => 20,
      'padding_left' => 20,

      // Text effects.
      'enable_text_shadow' => TRUE,
      'text_shadow_color' => '#ff0000',
      'text_shadow_blur' => 20,
      'text_shadow_intensity' => 2,

      // Box shadow.
      'enable_box_shadow' => FALSE,
      'box_shadow_color' => '#000000',
      'box_shadow_blur' => 10,
      'box_shadow_spread' => 0,
      'box_shadow_x' => 0,
      'box_shadow_y' => 5,
      'box_shadow_opacity' => 50,

      // Timezone.
      'timezone' => 'default',
      'custom_timezone' => 'UTC',

      // Advanced.
      'text_align' => 'center',
      'uppercase' => FALSE,

    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    // Display Settings.
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => TRUE,
    ];

    $form['display']['clock_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Clock width (pixels)'),
      '#default_value' => $config['clock_width'],
      '#min' => 150,
      '#max' => 1000,
      '#required' => TRUE,
    ];

    $form['display']['time_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Time format'),
      '#options' => [
        '12' => $this->t('12-hour (with AM/PM)'),
        '24' => $this->t('24-hour'),
      ],
      '#default_value' => $config['time_format'],
    ];

    $form['display']['show_seconds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show seconds'),
      '#default_value' => $config['show_seconds'],
    ];

    $form['display']['show_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show date'),
      '#default_value' => $config['show_date'],
    ];

    $form['display']['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => [
        'long' => $this->t('Long (January 1, 2024)'),
        'short' => $this->t('Short (01/01/2024)'),
        'iso' => $this->t('ISO (2024-01-01)'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $config['date_format'],
      '#states' => [
        'visible' => [
          ':input[name="settings[display][show_date]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['display']['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#default_value' => $config['custom_date_format'],
      '#description' => $this->t('Use PHP date format. Examples: "F j, Y" = January 1, 2024, "m/d/Y" = 01/01/2024'),
      '#states' => [
        'visible' => [
          ':input[name="settings[display][date_format]"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['display']['show_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show day of week'),
      '#default_value' => $config['show_day'],
    ];

    $form['display']['day_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Day format'),
      '#options' => [
        'full' => $this->t('Full (Monday)'),
        'short' => $this->t('Short (Mon)'),
      ],
      '#default_value' => $config['day_format'],
      '#states' => [
        'visible' => [
          ':input[name="settings[display][show_day]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Background.
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

    $form['background']['bg_gradient_angle'] = [
      '#type' => 'number',
      '#title' => $this->t('Gradient angle (degrees)'),
      '#default_value' => $config['bg_gradient_angle'],
      '#min' => 0,
      '#max' => 360,
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

    $form['border']['border_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Border width (pixels)'),
      '#default_value' => $config['border_width'],
      '#min' => 0,
      '#max' => 20,
    ];

    $form['border']['border_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Border color'),
      '#default_value' => $config['border_color'],
    ];

    $form['border']['border_radius'] = [
      '#type' => 'number',
      '#title' => $this->t('Border radius (pixels)'),
      '#default_value' => $config['border_radius'],
      '#min' => 0,
      '#max' => 50,
    ];

    $form['border']['border_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Border style'),
      '#options' => [
        'solid' => $this->t('Solid'),
        'dashed' => $this->t('Dashed'),
        'dotted' => $this->t('Dotted'),
        'double' => $this->t('Double'),
      ],
      '#default_value' => $config['border_style'],
    ];

    // Colors.
    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Colors'),
      '#open' => TRUE,
    ];

    $form['colors']['time_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Time color'),
      '#default_value' => $config['time_color'],
    ];

    $form['colors']['date_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Date color'),
      '#default_value' => $config['date_color'],
    ];

    $form['colors']['day_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Day color'),
      '#default_value' => $config['day_color'],
    ];

    // Font Settings.
    $form['font'] = [
      '#type' => 'details',
      '#title' => $this->t('Font Settings'),
      '#open' => FALSE,
    ];

    $form['font']['font_family'] = [
      '#type' => 'select',
      '#title' => $this->t('Font family'),
      '#options' => [
        'monospace' => $this->t('Monospace (Courier New)'),
        'sans-serif' => $this->t('Sans-serif (Arial)'),
        'serif' => $this->t('Serif (Times)'),
        'system' => $this->t('System default'),
      ],
      '#default_value' => $config['font_family'],
    ];

    $form['font']['time_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Time font size (pixels)'),
      '#default_value' => $config['time_font_size'],
      '#min' => 16,
      '#max' => 120,
    ];

    $form['font']['date_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Date font size (pixels)'),
      '#default_value' => $config['date_font_size'],
      '#min' => 10,
      '#max' => 60,
    ];

    $form['font']['day_font_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Day font size (pixels)'),
      '#default_value' => $config['day_font_size'],
      '#min' => 8,
      '#max' => 50,
    ];

    $form['font']['letter_spacing'] = [
      '#type' => 'number',
      '#title' => $this->t('Letter spacing (pixels)'),
      '#default_value' => $config['letter_spacing'],
      '#min' => 0,
      '#max' => 20,
    ];

    $form['font']['font_weight'] = [
      '#type' => 'select',
      '#title' => $this->t('Font weight'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'bold' => $this->t('Bold'),
        '300' => $this->t('Light (300)'),
        '600' => $this->t('Semi-bold (600)'),
        '900' => $this->t('Extra-bold (900)'),
      ],
      '#default_value' => $config['font_weight'],
    ];

    // Padding.
    $form['padding'] = [
      '#type' => 'details',
      '#title' => $this->t('Padding'),
      '#open' => FALSE,
    ];

    $form['padding']['padding_top'] = [
      '#type' => 'number',
      '#title' => $this->t('Top padding (pixels)'),
      '#default_value' => $config['padding_top'],
      '#min' => 0,
      '#max' => 100,
    ];

    $form['padding']['padding_right'] = [
      '#type' => 'number',
      '#title' => $this->t('Right padding (pixels)'),
      '#default_value' => $config['padding_right'],
      '#min' => 0,
      '#max' => 100,
    ];

    $form['padding']['padding_bottom'] = [
      '#type' => 'number',
      '#title' => $this->t('Bottom padding (pixels)'),
      '#default_value' => $config['padding_bottom'],
      '#min' => 0,
      '#max' => 100,
    ];

    $form['padding']['padding_left'] = [
      '#type' => 'number',
      '#title' => $this->t('Left padding (pixels)'),
      '#default_value' => $config['padding_left'],
      '#min' => 0,
      '#max' => 100,
    ];

    // Text Shadow.
    $form['text_shadow'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Shadow / Glow'),
      '#open' => FALSE,
    ];

    $form['text_shadow']['enable_text_shadow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable text shadow/glow'),
      '#default_value' => $config['enable_text_shadow'],
    ];

    $form['text_shadow']['text_shadow_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Shadow/glow color'),
      '#default_value' => $config['text_shadow_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[text_shadow][enable_text_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['text_shadow']['text_shadow_blur'] = [
      '#type' => 'number',
      '#title' => $this->t('Blur radius (pixels)'),
      '#default_value' => $config['text_shadow_blur'],
      '#min' => 0,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="settings[text_shadow][enable_text_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['text_shadow']['text_shadow_intensity'] = [
      '#type' => 'number',
      '#title' => $this->t('Glow layers (intensity)'),
      '#default_value' => $config['text_shadow_intensity'],
      '#min' => 1,
      '#max' => 5,
      '#description' => $this->t('Number of shadow layers for stronger glow effect'),
      '#states' => [
        'visible' => [
          ':input[name="settings[text_shadow][enable_text_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Box Shadow.
    $form['box_shadow'] = [
      '#type' => 'details',
      '#title' => $this->t('Box Shadow'),
      '#open' => FALSE,
    ];

    $form['box_shadow']['enable_box_shadow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable box shadow'),
      '#default_value' => $config['enable_box_shadow'],
    ];

    $form['box_shadow']['box_shadow_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Shadow color'),
      '#default_value' => $config['box_shadow_color'],
      '#states' => [
        'visible' => [
          ':input[name="settings[box_shadow][enable_box_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['box_shadow']['box_shadow_x'] = [
      '#type' => 'number',
      '#title' => $this->t('Horizontal offset (pixels)'),
      '#default_value' => $config['box_shadow_x'],
      '#min' => -50,
      '#max' => 50,
      '#states' => [
        'visible' => [
          ':input[name="settings[box_shadow][enable_box_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['box_shadow']['box_shadow_y'] = [
      '#type' => 'number',
      '#title' => $this->t('Vertical offset (pixels)'),
      '#default_value' => $config['box_shadow_y'],
      '#min' => -50,
      '#max' => 50,
      '#states' => [
        'visible' => [
          ':input[name="settings[box_shadow][enable_box_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['box_shadow']['box_shadow_blur'] = [
      '#type' => 'number',
      '#title' => $this->t('Blur radius (pixels)'),
      '#default_value' => $config['box_shadow_blur'],
      '#min' => 0,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="settings[box_shadow][enable_box_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['box_shadow']['box_shadow_spread'] = [
      '#type' => 'number',
      '#title' => $this->t('Spread radius (pixels)'),
      '#default_value' => $config['box_shadow_spread'],
      '#min' => -50,
      '#max' => 50,
      '#states' => [
        'visible' => [
          ':input[name="settings[box_shadow][enable_box_shadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['box_shadow']['box_shadow_opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Shadow opacity (%)'),
      '#default_value' => $config['box_shadow_opacity'],
      '#min' => 0,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="settings[box_shadow][enable_box_shadow]"]' => ['checked' => TRUE],
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

    // Advanced.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
    ];

    $form['advanced']['text_align'] = [
      '#type' => 'select',
      '#title' => $this->t('Text alignment'),
      '#options' => [
        'left' => $this->t('Left'),
        'center' => $this->t('Center'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $config['text_align'],
    ];

    $form['advanced']['uppercase'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Uppercase text'),
      '#default_value' => $config['uppercase'],
      '#description' => $this->t('Convert all text to uppercase'),
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
    foreach (['display', 'background', 'border', 'colors', 'font', 'padding',
      'text_shadow', 'box_shadow', 'timezone_settings', 'advanced',
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
    $block_id = 'digital-clock-' . uniqid();

    return [
      '#theme' => 'svg_clock_digital_dynamic',
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
