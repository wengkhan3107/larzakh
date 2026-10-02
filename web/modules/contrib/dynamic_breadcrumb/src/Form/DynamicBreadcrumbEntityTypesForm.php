<?php

namespace Drupal\dynamic_breadcrumb\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for entity types that will be used by the module..
 */
class DynamicBreadcrumbEntityTypesForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dynamic_breadcrumb.entity_types_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dynamic_breadcrumb_entity_types_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dynamic_breadcrumb.entity_types_config');
    $breadcrumb_settings_link = Link::createFromRoute('Dynamic breadcrumb customization form', 'dynamic_breadcrumb.dynamic_breadcrumb_config_form');
    $form['form_description'] = [
      '#markup' => $this->t("<h5>Please choose the entity types that you want to be used by the module.</h5>
            <p>The breadcrumb of the entity types selected in the checkboxes below will be configurable from <a href='@link'>this form</a>.</p>",
        ['@link' => $breadcrumb_settings_link->getUrl()->toString()]),
    ];
    $entity_types = [];
    $entity_types_definitions = $this->entityTypeManager->getDefinitions();
    // Get all entity types.
    foreach ($entity_types_definitions as $entity_type_definition) {
      if ($entity_type_definition->getLinkTemplate('canonical') && $entity_type_definition instanceof ContentEntityTypeInterface) {
        $entity_types[$entity_type_definition->getBundleLabel()] = $entity_type_definition->id();
      }
    }
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#title' => $this->t('Entity types used by the module.'),
    ];

    $form['fieldset']['entity_types'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Entity types'),
      '#options'       => array_flip($entity_types),
      '#default_value' => $config->get('entity_types') ?? [],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_values = $form_state->getValues();
    // Saves Entity types.
    $this->config('dynamic_breadcrumb.entity_types_config')
      ->set('entity_types', $form_values['entity_types'])
      ->save();
  }

}
