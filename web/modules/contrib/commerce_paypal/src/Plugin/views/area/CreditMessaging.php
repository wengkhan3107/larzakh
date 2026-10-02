<?php

namespace Drupal\commerce_paypal\Plugin\views\area;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Calculator;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a PayPal Credit messaging area handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("commerce_paypal_credit_messaging")
 */
class CreditMessaging extends AreaPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['placement'] = ['default' => 'cart'];
    $options['style'] = ['default' => 'text'];
    $options['color'] = ['default' => 'blue'];
    $options['ratio'] = ['default' => '1x1'];
    $options['logo_type'] = ['default' => 'primary'];
    $options['logo_position'] = ['default' => 'left'];
    $options['text_size'] = ['default' => '12'];
    $options['text_color'] = ['default' => 'black'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['empty']['#description'] = $this->t("Even if selected, this area handler will never render if a valid order cannot be found in the View's arguments.");

    $form['placement'] = [
      '#type' => 'radios',
      '#title' => $this->t('Placement type'),
      '#options' => [
        'cart' => $this->t('Cart'),
        'payment' => $this->t('Payment'),
      ],
      '#default_value' => $this->options['placement'] ?? 'cart',
    ];

    $form['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Style'),
      '#default_value' => $this->options['style'],
      '#options' => [
        'flex' => $this->t('Banner'),
        'text' => $this->t('Text'),
      ],
    ];

    $states = [
      'visible' => [
        ':input[name="settings[style]"]' => ['value' => 'flex'],
      ],
    ];

    $form['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color'),
      '#default_value' => $this->options['color'],
      '#options' => [
        'blue' => $this->t('Blue'),
        'black' => $this->t('Black'),
        'white' => $this->t('White'),
        'white-no-border' => $this->t('White with no border'),
        'gray' => $this->t('Gray'),
        'monochrome' => $this->t('Monochrome'),
        'grayscale' => $this->t('Grayscale'),
      ],
      '#states' => $states,
    ];

    $form['ratio'] = [
      '#type' => 'select',
      '#title' => $this->t('Ratio'),
      '#default_value' => $this->options['ratio'],
      '#options' => [
        '1x1' => $this->t('Square (1x1)'),
        '1x4' => $this->t('Tall (1x4)'),
        '8x1' => $this->t('Wide (8x1)'),
        '20x1' => $this->t('Wide and narrow (20x1)'),
      ],
      '#states' => $states,
    ];

    $states = [
      'visible' => [
        ':input[name="settings[style]"]' => ['value' => 'text'],
      ],
    ];

    $form['logo_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo type'),
      '#description' => $this->t('See examples of these options in the <a href="https://developer.paypal.com/docs/limited-release/sdk-credit-messaging/reference/reference-tables/#logo-type" target="_blank">PayPal documentation</a>.'),
      '#default_value' => $this->options['logo_type'],
      '#options' => [
        'primary' => $this->t('Stacked PayPal Credit logo'),
        'alternative' => $this->t('Single line PayPal Credit logo'),
        'inline' => $this->t('Single line PayPal Credit logo without monogram'),
        'none' => $this->t('No logo, only text'),
      ],
      '#states' => $states,
    ];

    $form['logo_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Logo position'),
      '#default_value' => $this->options['logo_position'],
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'top' => $this->t('Top'),
      ],
      '#states' => $states,
    ];

    $form['text_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Text size'),
      '#default_value' => $this->options['text_size'],
      '#options' => [
        '10' => $this->t('10'),
        '11' => $this->t('11'),
        '12' => $this->t('12'),
        '13' => $this->t('13'),
        '14' => $this->t('14'),
        '15' => $this->t('15'),
        '16' => $this->t('16'),
      ],
      '#states' => $states,
    ];

    $form['text_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Text color'),
      '#default_value' => $this->options['text_color'],
      '#options' => [
        'black' => $this->t('Black'),
        'white' => $this->t('White'),
        'monochrome' => $this->t('Monochrome'),
        'grayscale' => $this->t('Grayscale'),
      ],
      '#states' => $states,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      $order_storage = $this->entityTypeManager->getStorage('commerce_order');
      foreach ($this->view->argument as $argument) {
        // First look for an order_id argument.
        if (!$argument instanceof NumericArgument) {
          continue;
        }
        if (!in_array($argument->getField(), ['commerce_order.order_id', 'commerce_order_item.order_id'])) {
          continue;
        }
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        $order = $order_storage->load($argument->getValue());
        if (!$order) {
          return [];
        }
        $element = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'data-pp-message' => '',
            'data-pp-placement' => $this->options['placement'],
            'data-pp-amount' => Calculator::trim($order->getTotalPrice()->getNumber()),
            'data-pp-style-layout' => $this->options['style'],
          ],
          '#attached' => ['library' => ['commerce_paypal/credit_messaging']],
        ];

        if ($this->options['style'] === 'flex') {
          $element['#attributes'] += [
            'data-pp-style-color' => $this->options['color'],
            'data-pp-style-ratio' => $this->options['ratio'],
          ];
        }
        else {
          $element['#attributes'] += [
            'data-pp-style-logo-type' => $this->options['logo_type'],
            'data-pp-style-logo-position' => $this->options['logo_position'],
            'data-pp-style-text-size' => $this->options['text_size'],
            'data-pp-style-text-color' => $this->options['text_color'],
          ];
        }

        return $element;
      }
    }
    return [];
  }

}
