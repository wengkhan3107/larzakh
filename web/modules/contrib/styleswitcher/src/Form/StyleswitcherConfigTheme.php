<?php

namespace Drupal\styleswitcher\Form;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Configure theme-specific styles settings.
 */
class StyleswitcherConfigTheme extends FormBase implements TrustedCallbackInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs the StyleswitcherConfigTheme.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('theme_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleswitcher_config_theme';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $theme
   *   Name of the theme to configure styles for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = '') {
    if (!$this->themeHandler->hasUi($theme)) {
      throw new NotFoundHttpException();
    }

    $styles = styleswitcher_style_load_multiple($theme);
    uasort($styles, 'styleswitcher_sort');
    $options = array_fill_keys(array_keys($styles), '');

    $form['theme_name'] = ['#type' => 'value', '#value' => $theme];

    $form['settings'] = [
      '#pre_render' => [[$this, 'preRenderSettingsAsTable']],
      '#tree' => TRUE,
    ];
    foreach ($styles as $name => $style) {
      $form['settings']['weight'][$name] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @label', ['@label' => $style['label']]),
        '#title_display' => 'invisible',
        '#delta' => $this->weightDelta($theme),
        '#default_value' => $style['weight'],
        '#weight' => $style['weight'],
        // Set special class for drag and drop updating.
        '#attributes' => ['class' => ['styleswitcher-style-weight']],
      ];
      $form['settings']['name'][$name] = [
        '#theme' => 'styleswitcher_admin_item',
        '#style' => $style,
      ];
      $form['settings']['label'][$name] = [
        '#markup' => $style['label'],
      ];
    }
    $form['settings']['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => array_keys(styleswitcher_style_load_multiple($theme, ['status' => TRUE])),
    ];
    $form['settings']['default'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => styleswitcher_default_style_key($theme),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme_name');
    $values = $form_state->getValue('settings');

    // Automatically enable the default style and the style which was default
    // previously because we will not get the value from that disabled checkbox.
    $values['enabled'][$values['default']] = 1;
    $values['enabled'][styleswitcher_default_style_key($theme)] = 1;

    $form_state->setValueForElement($form['settings'], $values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme_name');
    $values = $form_state->getValue('settings');
    $theme_settings = [];

    foreach (array_keys(styleswitcher_style_load_multiple($theme)) as $name) {
      $theme_settings[$name] = [
        'weight' => $values['weight'][$name],
        'status' => !empty($values['enabled'][$name]),
        'is_default' => ($values['default'] == $name),
      ];
    }

    // Get all settings (for all themes).
    $config = $this->configFactory()
      ->getEditable('styleswitcher.styles_settings');
    $settings = $config->get('settings') ?? [];
    $settings[$theme] = $theme_settings;
    $config->set('settings', $settings)->save();

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

  /**
   * Calculates #delta for style's weight element.
   *
   * @param string $theme
   *   Name of the theme for which styles the delta is calculated.
   *
   * @return int
   *   Optimal #delta value.
   */
  protected function weightDelta($theme) {
    $weights = [];

    foreach (styleswitcher_style_load_multiple($theme) as $style) {
      $weights[] = $style['weight'];
    }

    return max(abs(min($weights)), max($weights), floor(count($weights) / 2));
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderSettingsAsTable'];
  }

  /**
   * Render API callback: Wraps theme-specific styles settings in a table.
   */
  public function preRenderSettingsAsTable(array $element) {
    $header = [
      $this->t('Style'),
      $this->t('Enabled'),
      $this->t('Default'),
      $this->t('Weight'),
    ];
    $rows = [];

    if (!empty($element['weight'])) {
      foreach (Element::children($element['weight']) as $key) {
        if ($key == $element['default']['#default_value']) {
          $element['enabled'][$key]['#attributes']['disabled'] = 'disabled';
        }

        $label = $element['label'][$key]['#markup'];
        $element['enabled'][$key]['#title'] = $this->t('Enable @title', ['@title' => $label]);
        $element['enabled'][$key]['#title_display'] = 'invisible';
        $element['default'][$key]['#title'] = $this->t('Set @title as default', ['@title' => $label]);
        $element['default'][$key]['#title_display'] = 'invisible';

        // Build the table row.
        $row = [
          ['data' => $element['name'][$key]],
          ['data' => $element['enabled'][$key]],
          ['data' => $element['default'][$key]],
          ['data' => $element['weight'][$key]],
        ];
        $rows[] = [
          'data' => $row,
          'class' => ['draggable'],
        ];
      }
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No styles to switch.'),
      '#attributes' => ['id' => 'styleswitcher-styles-table'],
      '#tabledrag' => [
        0 => [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'styleswitcher-style-weight',
        ],
      ],
    ];

    return [$table];
  }

}
