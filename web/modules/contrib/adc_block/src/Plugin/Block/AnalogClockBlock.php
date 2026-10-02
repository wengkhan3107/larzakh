<?php

namespace Drupal\adc_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;

/**
 * Provides a Block to display an Analog Clock.
 *
 * @Block(
 * id = "adc_block_block",
 * admin_label = @Translation("Analog Clock")
 * )
 */
class AnalogClockBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'num_places' => 5,
      'label_display' => FALSE,
      'layout' => 'layout1',
      'timezone' => 'system_timezone',
      'heading' => '',
      'footer' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $layout = $config['layout'] ?? 'layout2';
    $timezone_setting = $config['timezone'] ?? 'system_timezone';

    // Resolve Timezone.
    $timezone = $timezone_setting;
    if ($timezone_setting === 'system_timezone') {
      $timezone = date_default_timezone_get();
    }
    elseif ($timezone_setting === 'local_timezone') {
      $timezone = '';
    }

    // Security: Sanitize user-inputted text content.
    $content = [
      'heading' => Html::escape($config['heading'] ?? ''),
      'footer' => Html::escape($config['footer'] ?? ''),
    ];

    // Fetch the specific layout data.
    $layout_data = $this->getLayoutData($layout, $config);
    $layout_data['layout'] = $layout;
    $layout_data['timezone'] = $timezone;

    return [
      'clock' => [
        '#theme' => 'analog_clock',
    // Use Drupal Json utility.
        '#data' => Json::encode($layout_data),
        '#content' => $content,
      ],
      '#attached' => [
        'drupalSettings' => [
          'layout_data' => $layout_data,
        ],
        'library' => ['adc_block/adc_block.analog'],
      ],
    ];
  }

  /**
   * Helper to keep the build() method clean and optimized.
   */
  private function getLayoutData($layout, array $config) {
    // Preset definitions moved to a helper to avoid massive switch blocks.
    $presets = [
      'layout1' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "hasShadow" => FALSE,
        "fillColor" => "#292a2d",
        "borderColor" => "#ffffff",
        "borderWidth" => "1.5",
        "fontColor" => "#ffffff",
        "fontWeight" => "bold",
        "pinColor" => "#ffffff",
        "majorTicksColor" => "#ffffff",
        "minorTicksColor" => "#ffffff",
        "hourHandColor" => "#ffffff",
        "minuteHandColor" => "#ffffff",
        "secondHandColor" => "#ffa000",
        "secondHandWidth" => "1",
        "secondHandLength" => "90",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
      'layout2' => [
        "hasShadow" => TRUE,
        "shadowColor" => "#a7a5a5",
        "majorTicksWidth" => "0.1",
        "minorTicksWidth" => "0.05",
        "fillColor" => "rgba(255, 255, 255, 0)",
        "borderColor" => "#fff",
        "borderWidth" => "3.0",
        "fontColor" => "#fff",
        "fontWeight" => "bold",
        "pinColor" => "#fff",
        "majorTicksColor" => "transparent",
        "minorTicksColor" => "transparent",
        "hourHandColor" => "#fff",
        "minuteHandColor" => "#fff",
        "secondHandColor" => "#fff",
        "secondHandWidth" => "2.0",
        "secondHandLength" => "70.0",
        "minuteHandLength" => "60.0",
        "hourHandLength" => "40.0",
      ],
      'layout3' => [
        'majorTicksWidth' => "0.1",
        'minorTicksWidth' => "0.05",
        'fillColor' => "#0007e0",
        'borderColor' => "#fff",
        'borderWidth' => "3.0",
        'fontColor' => "#fff",
        'fontWeight' => "bold",
        'pinColor' => "#fff",
        'majorTicksColor' => "transparent",
        'minorTicksColor' => "transparent",
        'hourHandColor' => "#fff",
        'minuteHandColor' => "#fff",
        'secondHandColor' => "#fff",
        'secondHandWidth' => "2.0",
        'secondHandLength' => "70.0",
        'minuteHandLength' => "60.0",
        'hourHandLength' => "40.0",
      ],
      'layout4' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "shadowColor" => "#a7a5a5",
        "hasShadow" => TRUE,
        "fillColor" => "#ffffff",
        "borderColor" => "#000000",
        "borderWidth" => "2.5",
        "fontColor" => "#000000",
        "fontWeight" => "bolder",
        "pinColor" => "#ff0000",
        "majorTicksColor" => "#000000",
        "minorTicksColor" => "#000000",
        "hourHandColor" => "#000000",
        "minuteHandColor" => "#000000",
        "secondHandColor" => "#ff0044",
        "secondHandWidth" => "1",
        "secondHandLength" => "90",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
      'layout5' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "shadowColor" => "#a7a5a5",
        "hasShadow" => TRUE,
        "fillColor" => "#ffffff",
        "borderColor" => "#000000",
        "borderWidth" => "0.1",
        "fontColor" => "#000000",
        "fontWeight" => "bolder",
        "pinColor" => "#ff0000",
        "majorTicksColor" => "#000000",
        "minorTicksColor" => "#000000",
        "hourHandColor" => "#000000",
        "minuteHandColor" => "#000000",
        "secondHandColor" => "#ff0044",
        "secondHandWidth" => "1",
        "secondHandLength" => "90",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
      'layout6' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "hasShadow" => FALSE,
        "fillColor" => "#091921",
        "borderColor" => "#000000",
        "borderWidth" => "0.1",
        "fontColor" => "#ffffff",
        "fontWeight" => "bolder",
        "pinColor" => "#ffffff",
        "majorTicksColor" => "#ffffff",
        "minorTicksColor" => "#ffffff",
        "hourHandColor" => "#ff0000",
        "minuteHandColor" => "#ffffff",
        "secondHandColor" => "#ffffff",
        "secondHandWidth" => "1",
        "secondHandLength" => "90",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
      'layout7' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "hasShadow" => FALSE,
        "fillColor" => "#fcfcfc",
        "borderColor" => "#ff0000",
        "borderWidth" => "10",
        "fontColor" => "#ffffff",
        "fontWeight" => "bolder",
        "pinColor" => "#ff0000",
        "majorTicksColor" => "#ffffff",
        "minorTicksColor" => "#ffffff",
        "hourHandColor" => "#000000",
        "minuteHandColor" => "#000000",
        "secondHandColor" => "#ff0000",
        "secondHandWidth" => "3",
        "minuteHandWidth" => "4.0",
        "hourHandWidth" => "5.0",
        "secondHandLength" => "82",
        "majorTicksLength" => "10.0",
        "minorTicksLength" => "7.0",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
      'layout8' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "hasShadow" => FALSE,
        "fillColor" => "#fcfcfc",
        "borderColor" => "#6bcaef",
        "borderWidth" => "10",
        "fontColor" => "#424a4e",
        "fontWeight" => "900",
        "pinColor" => "#424a4e",
        "majorTicksColor" => "#424a4e",
        "minorTicksColor" => "#424a4e",
        "hourHandColor" => "#424a42",
        "minuteHandColor" => "#424a4e",
        "secondHandColor" => "#eba65c",
        "secondHandWidth" => "2",
        "minuteHandWidth" => "2.0",
        "hourHandWidth" => "3.0",
        "secondHandLength" => "70",
        "majorTicksLength" => "10.0",
        "minorTicksLength" => "7.0",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "15",
      ],
      'layout9' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "hasShadow" => FALSE,
        "fillColor" => "#ffffff",
        "borderColor" => "#000000",
        "borderWidth" => "0.1",
        "fontColor" => "#424a4e",
        "fontWeight" => "bolder",
        "pinColor" => "#424a4e",
        "majorTicksColor" => "#424a4e",
        "minorTicksColor" => "#424a4e",
        "hourHandColor" => "#424a42",
        "minuteHandColor" => "#424a4e",
        "secondHandColor" => "#eba65c",
        "secondHandWidth" => "2",
        "minuteHandWidth" => "2.0",
        "hourHandWidth" => "3.0",
        "secondHandLength" => "70",
        "majorTicksLength" => "10.0",
        "minorTicksLength" => "7.0",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
      'layout10' => [
        "majorTicksWidth" => "0.005",
        "minorTicksWidth" => "0.5",
        "shadowColor" => "#858585",
        "hasShadow" => TRUE,
        "fillColor" => "#ffffff",
        "borderColor" => "#000000",
        "borderWidth" => "3",
        "fontColor" => "#000000",
        "fontWeight" => "bolder",
        "pinColor" => "#000000",
        "majorTicksColor" => "#000000",
        "minorTicksColor" => "#000000",
        "hourHandColor" => "#000000",
        "minuteHandColor" => "#000000",
        "secondHandColor" => "#000000",
        "secondHandWidth" => "2",
        "minuteHandWidth" => "2.0",
        "hourHandWidth" => "3.0",
        "secondHandLength" => "80",
        "majorTicksLength" => "10.0",
        "minorTicksLength" => "7.0",
        "minuteHandLength" => "70.0",
        "hourHandLength" => "50.0",
        "fontSize" => "10",
      ],
    ];

    if ($layout === 'custom') {
      return [
        'majorTicksWidth' => $config['majorTicksWidth'] ?? '0.005',
        'minorTicksWidth' => $config['minorTicksWidth'] ?? '0.4',
        'hasShadow' => (bool) ($config['hasShadow'] ?? FALSE),
        'shadowColor' => $config['shadowColor'] ?? '#000000',
        'fillColor' => ($config['fillColor_transparent'] ?? FALSE) ? 'transparent' : ($config['fillColor'] ?? '#333333'),
        'borderColor' => $config['borderColor'] ?? '#000000',
        'borderWidth' => $config['borderWidth'] ?? '2.0',
        'fontColor' => $config['fontColor'] ?? '#ffffff',
        'fontWeight' => $config['fontWeight'] ?? 'normal',
        'pinColor' => $config['pinColor'] ?? '#ff8888',
        'majorTicksColor' => ($config['majorTicksColor_transparent'] ?? FALSE) ? 'transparent' : ($config['majorTicksColor'] ?? '#ff8888'),
        'minorTicksColor' => ($config['minorTicksColor_transparent'] ?? FALSE) ? 'transparent' : ($config['minorTicksColor'] ?? '#ffaa00'),
        'hourHandColor' => $config['hourHandColor'] ?? '#ffffff',
        'minuteHandColor' => $config['minuteHandColor'] ?? '#ffffff',
        'secondHandColor' => $config['secondHandColor'] ?? '#ff0000',
        'secondHandWidth' => $config['secondHandWidth'] ?? '1.0',
        'minuteHandWidth' => $config['minuteHandWidth'] ?? '2.0',
        'hourHandWidth' => $config['hourHandWidth'] ?? '3.0',
        'secondHandLength' => $config['secondHandLength'] ?? '90.0',
        'majorTicksLength' => $config['majorTicksLength'] ?? '10.0',
        'minorTicksLength' => $config['minorTicksLength'] ?? '7.0',
        'minuteHandLength' => $config['minuteHandLength'] ?? '70.0',
        'hourHandLength' => $config['hourHandLength'] ?? '50.0',
        'fontSize' => $config['fontSize'] ?? '10.0',
      ];
    }

    return $presets[$layout] ?? $presets['layout1'];
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
        'layout2' => $this->t('Layout 2'),
        'layout3' => $this->t('Layout 3'),
        'layout4' => $this->t('Layout 4'),
        'layout5' => $this->t('Layout 5'),
        'layout6' => $this->t('Layout 6'),
        'layout7' => $this->t('Layout 7'),
        'layout8' => $this->t('Layout 8'),
        'layout9' => $this->t('Layout 9'),
        'layout10' => $this->t('Layout 10'),
        'custom' => $this->t('Custom Layout'),
      ],
    ];

    $custom_state = ['visible' => [':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom']]];

    $form['layout_settings']['majorTicksWidth'] = [
      '#type' => 'number',
      '#step' => '.001',
      '#min' => '0',
      '#max' => '1',
      '#title' => $this->t('Major Ticks Width'),
      '#default_value' => $config['majorTicksWidth'] ?? '0.005',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['minorTicksWidth'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '0',
      '#max' => '0.5',
      '#title' => $this->t('Minor Ticks Width'),
      '#default_value' => $config['minorTicksWidth'] ?? '0.4',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['minuteHandWidth'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '1',
      '#max' => '5',
      '#title' => $this->t('Minute Hand Width'),
      '#default_value' => $config['minuteHandWidth'] ?? '2.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['hourHandWidth'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '1',
      '#max' => '10',
      '#title' => $this->t('Hour Hand Width'),
      '#default_value' => $config['hourHandWidth'] ?? '3.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['hasShadow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clock Has Shadow'),
      '#default_value' => $config['hasShadow'] ?? FALSE,
      '#states' => $custom_state,
    ];

    $form['layout_settings']['shadowColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Clock Shadow Color'),
      '#default_value' => $config['shadowColor'] ?? '#000000',
      '#states' => [
        'visible' => [
          ':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom'],
          ':input[name="settings[layout_settings][hasShadow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['layout_settings']['fillColor_transparent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fill transparent'),
      '#default_value' => $config['fillColor_transparent'] ?? FALSE,
      '#states' => $custom_state,
    ];

    $form['layout_settings']['fillColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Clock Fill Color'),
      '#default_value' => $config['fillColor'] ?? '#333333',
      '#states' => [
        'visible' => [
          ':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom'],
          ':input[name="settings[layout_settings][fillColor_transparent]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['layout_settings']['borderColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Clock Border Color'),
      '#default_value' => $config['borderColor'] ?? '#000000',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['borderWidth'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '0.1',
      '#max' => '15',
      '#title' => $this->t('Clock Border Width'),
      '#default_value' => $config['borderWidth'] ?? '2.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['fontColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Font Color'),
      '#default_value' => $config['fontColor'] ?? '#ffffff',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['fontSize'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '10',
      '#max' => '35',
      '#title' => $this->t('Font Size'),
      '#default_value' => $config['fontSize'] ?? '10.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['fontWeight'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Weight'),
      '#default_value' => $config['fontWeight'] ?? 'normal',
      '#options' => [
        'normal' => $this->t('Normal'),
        'bold' => $this->t('Bold'),
        'bolder' => $this->t('Bolder'),
        'lighter' => $this->t('Lighter'),
        '100' => $this->t('100'),
        '200' => $this->t('200'),
        '300' => $this->t('300'),
        '400' => $this->t('400'),
        '500' => $this->t('500'),
        '600' => $this->t('600'),
        '700' => $this->t('700'),
        '800' => $this->t('800'),
        '900' => $this->t('900'),
      ],
      '#states' => $custom_state,
    ];

    $form['layout_settings']['pinColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Pin Color'),
      '#default_value' => $config['pinColor'] ?? '#ff8888',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['majorTicksColor_transparent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Major Ticks Color transparent'),
      '#default_value' => $config['majorTicksColor_transparent'] ?? FALSE,
      '#states' => $custom_state,
    ];

    $form['layout_settings']['majorTicksColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Major Ticks Color'),
      '#default_value' => $config['majorTicksColor'] ?? '#ff8888',
      '#states' => [
        'visible' => [
          ':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom'],
          ':input[name="settings[layout_settings][majorTicksColor_transparent]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['layout_settings']['minorTicksColor_transparent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Minor Ticks Color Transparent'),
      '#default_value' => $config['minorTicksColor_transparent'] ?? FALSE,
      '#states' => $custom_state,
    ];

    $form['layout_settings']['minorTicksColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Minor Ticks Color'),
      '#default_value' => $config['minorTicksColor'] ?? '#ffaa00',
      '#states' => [
        'visible' => [
          ':input[name="settings[layout_settings][layout]"]' => ['value' => 'custom'],
          ':input[name="settings[layout_settings][minorTicksColor_transparent]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['layout_settings']['hourHandColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Hour Hand Color'),
      '#default_value' => $config['hourHandColor'] ?? '#ffffff',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['minuteHandColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Minute Hand Color'),
      '#default_value' => $config['minuteHandColor'] ?? '#ffffff',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['secondHandColor'] = [
      '#type' => 'color',
      '#title' => $this->t('Second Hand Color'),
      '#default_value' => $config['secondHandColor'] ?? '#ff0000',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['secondHandWidth'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '1',
      '#max' => '15',
      '#title' => $this->t('Second Hand Width'),
      '#default_value' => $config['secondHandWidth'] ?? '1.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['secondHandLength'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '50',
      '#max' => '100',
      '#title' => $this->t('Second Hand Length'),
      '#default_value' => $config['secondHandLength'] ?? '90.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['minuteHandLength'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '50',
      '#max' => '100',
      '#title' => $this->t('Minute Hand Length'),
      '#default_value' => $config['minuteHandLength'] ?? '70.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['majorTicksLength'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '5',
      '#max' => '30',
      '#title' => $this->t('Major Ticks Length'),
      '#default_value' => $config['majorTicksLength'] ?? '10.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['minorTicksLength'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '5',
      '#max' => '30',
      '#title' => $this->t('Minor Ticks Length'),
      '#default_value' => $config['minorTicksLength'] ?? '7.0',
      '#states' => $custom_state,
    ];

    $form['layout_settings']['hourHandLength'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#min' => '50',
      '#max' => '100',
      '#title' => $this->t('Hour Hand Length'),
      '#default_value' => $config['hourHandLength'] ?? '50.0',
      '#states' => $custom_state,
    ];

    $form['description_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Text Content settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['description_settings']['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clock Heading'),
      '#default_value' => $config['heading'] ?? '',
    ];

    $form['description_settings']['footer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clock Footer'),
      '#default_value' => $config['footer'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Optimized saving by iterating through grouped values.
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
