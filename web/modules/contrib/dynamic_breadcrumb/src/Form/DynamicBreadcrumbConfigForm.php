<?php

namespace Drupal\dynamic_breadcrumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings form for dynamic breadcrumb.
 */
class DynamicBreadcrumbConfigForm extends ConfigFormBase {

  /**
   * EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dynamic_breadcrumb.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dynamic_breadcrumb_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $dynamic_breadcrumb_config = $this->config('dynamic_breadcrumb.settings');
    $entity_types_config = $this->config('dynamic_breadcrumb.entity_types_config')->get('entity_types');
    $entity_types = array_filter($entity_types_config);

    foreach ($entity_types as $key => $entity_type) {
      $bundles = [];
      $bundles_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles_info as $bundle_machine_name => $bundleInfo) {
        $bundles[$bundle_machine_name] = $bundleInfo['label'];
      }
      if ($entity_type === "taxonomy_term") {
        $entity_type = "term";
      }

      $form[$entity_type] = [
        '#type' => 'details',
        '#title' => $this->t('@key settings', ['@key' => $key]),
        '#weight' => 5,
        '#open' => TRUE,
        '#tree' => TRUE,
      ];

      foreach ($bundles as $bundle_key => $bundle) {
        $bundle_name = str_replace('_', '-', $bundle_key);
        $form[$entity_type][$bundle_name]["checkbox"] = [
          '#type' => 'checkbox',
          '#title' => $bundle,
          '#default_value' => $dynamic_breadcrumb_config->get($entity_type . "." . $bundle_name . ".checkbox"),
          '#attributes' => [
            'class ' => $bundle_name . "_checkbox",
          ],
        ];
        $conditional_states = [
          ':input[class=' . $bundle_name . '_checkbox]' => ['checked' => TRUE],
        ];
        $form[$entity_type][$bundle_name]['value'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Value'),
          '#default_value' => $dynamic_breadcrumb_config->get($entity_type . "." . $bundle_name . ".value"),
          '#size' => 60,
          '#states' => [
            'visible' => $conditional_states,
            'required' => $conditional_states,
          ],
        ];
      }
      $form[$entity_type]['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$entity_type],
        '#show_restricted' => TRUE,
        '#weight' => 9,
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $dynamic_breadcrumb_config = $this->config('dynamic_breadcrumb.settings');
    $form_values = $form_state->getValues();
    unset($form_values["submit"]);
    unset($form_values["form_build_id"]);
    unset($form_values["form_token"]);
    unset($form_values["form_id"]);
    unset($form_values["op"]);
    foreach ($form_values as $key => $form_value) {
      $dynamic_breadcrumb_config->set($key, $form_value);
    }
    $dynamic_breadcrumb_config->save();
    $this->messenger()->addMessage($this->t("The breadcrumb settings have been saved successfully!"));
  }

}
