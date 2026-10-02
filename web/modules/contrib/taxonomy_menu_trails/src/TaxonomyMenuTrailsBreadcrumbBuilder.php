<?php

namespace Drupal\taxonomy_menu_trails;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * {@inheritdoc}
 */
class TaxonomyMenuTrailsBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The configuration object generator.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuLinkManagerInterface $menu_link_manager,
    EntityRepositoryInterface $entity_repository,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    MenuActiveTrailInterface $menu_active_trail
  ) {
    $this->configFactory = $config_factory;
    $this->menuLinkManager = $menu_link_manager;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // This might be a "node" with no fields, e.g. a route to a "revision" URL,
    // so we don't check for taxonomy fields on unfieldable nodes:
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;
    if ($node_is_fieldable) {
      $bundle = $node_object->bundle();
      $entity_type = 'node';
      $type = $this->entityTypeManager->getStorage('node_type')
        ->load($bundle);
      // Check if node type is selected in content type taxonomy trails settings.
      // Check all the settings from content type configuration form.
      $configurations = $type->getThirdPartySetting('taxonomy_menu_trails', 'taxonomy_menu_trails', []);
      if (empty($configurations)) {
        return FALSE;
      }

      switch ($configurations['set_breadcrumb']) {
        case "never":
          return FALSE;
        case 'if_empty':
          $trailIds = $this->menuActiveTrail->getActiveTrailIds('main');
          if(!$trailIds) {
            return FALSE;
          }
          break;
        case 'always':
          break;
      }

      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions($entity_type, $bundle);
      foreach ($field_definitions as $field_name => $field_definition) {
        // Look for a "taxonomy attachment" by node field, regardless of language.
        if ($configurations['taxonomy_term_references'][$field_name] ?? NULL) {
          if (!empty($field_definition->getTargetBundle())) {
            // Check for term_reference/entity_reference fields from the content type.
            if ($field_definition->getType() == 'entity_reference') {
              // Check all taxonomy terms applying to the current page.
              foreach ($node_object->getFields() as $field) {
                if ($field->getSetting('target_type') == 'taxonomy_term') {
                  return TRUE;
                }
              }
            }

          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $node_object = $route_match->getParameters()->get('node');
    $node_is_fieldable = $node_object instanceof FieldableEntityInterface;

    if ($node_is_fieldable) {
      // Check all taxonomy terms applying to the current page.
      foreach ($node_object->getFields() as $field) {
        if ($field->getSetting('target_type') == 'taxonomy_term') {
          foreach ($field->referencedEntities() as $term) {
            $url = $term->toUrl();$route_links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters());
            if (!empty($route_links)) {
              $taxonomy_term_link = reset($route_links);
              $plugin_definition = $taxonomy_term_link->getPluginDefinition();
              $taxonomy_term_id = $plugin_definition['route_parameters']['taxonomy_term'];
              $parents = $this->entityTypeManager->getStorage('taxonomy_term')
                ->loadAllParents($taxonomy_term_id);
              foreach (array_reverse($parents) as $term1) {
                $term1 = $this->entityRepository->getTranslationFromContext($term1);
                $breadcrumb->addLink(Link::createFromRoute($term1->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $term1->id()]));
              }
            }
          }
        }
      }
    }
    return $breadcrumb;
  }

}
