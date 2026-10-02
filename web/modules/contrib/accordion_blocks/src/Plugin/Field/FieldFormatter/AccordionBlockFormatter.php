<?php

namespace Drupal\accordion_blocks\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'accordion_widget_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "accordion_widget_formatter",
 *   label = @Translation("Accordion Widget"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class AccordionBlockFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      // Protect ourselves from recursive rendering.
      static $depth = 0;
      $depth++;
      if ($depth > 20) {
        $this->loggerFactory->get('entity')
          ->error('Recursive rendering detected when rendering entity @entity_type @entity_id. Aborting rendering.', [
            '@entity_type' => $entity->getEntityTypeId(),
            '@entity_id' => $entity->id(),
          ]);
        return $elements;
      }

      if ($entity->id()) {
        $view_builder = \Drupal::service('entity_type.manager')
          ->getViewBuilder($entity->getEntityTypeId());
        $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());
        $items[$delta]->_attributes += [
          'resource' => $entity->toUrl(),
          'title' => $label,
        ];

      }
      else {
        // This is an "auto_create" item.
        $elements[$delta] = ['#markup' => $label];
      }
      $depth = 0;
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#theme'] = 'accordion_block_formatter';
    $elements['#attached']['library'][] = 'accordion_blocks/accordion-widget';
    $blocks = [];
    foreach ($items as $key => $value) {
      $blocks[$key] = [
        'content' => $elements[$key],
        'title' => $items[$key]->_attributes['title'],
      ];
    }
    $elements['#blocks'] = $blocks;
    return $elements;
  }

}
