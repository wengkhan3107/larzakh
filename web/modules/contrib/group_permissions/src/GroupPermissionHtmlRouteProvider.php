<?php

namespace Drupal\group_permissions;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group permission entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class GroupPermissionHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($canonical_route = $this->getCanonicalRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.canonical", $canonical_route);
    }

    if ($add_route = $this->getAddRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.add-form", $add_route);
    }

    if ($edit_route = $this->getEditRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.edit-form", $edit_route);
    }

    if ($delete_route = $this->getDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.delete-form", $delete_route);
    }

    if ($history_route = $this->getHistoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version-history", $history_route);
    }

    if ($revision_route = $this->getViewRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $revision_route);
    }

    if ($revert_route = $this->getRevisionRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision-revert", $revert_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision-delete", $delete_route);
    }

    return $collection;
  }

  /**
   * Get revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $link_key
   *   Link key.
   * @param array $route_defaults
   *   Array of route defaults: controller, form, _title and etc.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getGroupPermissionRoute($entity_type, $link_key, $route_defaults) {
    $route = NULL;
    if ($entity_type->hasLinkTemplate($link_key)) {
      $route = new Route($entity_type->getLinkTemplate($link_key));
      $route
        ->setDefaults($route_defaults)
        ->setRequirement('_group_permission', 'override group permissions')
        ->setRequirement('_group_permissions_enabled', 'TRUE')
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          'group' => [
            'type' => 'entity:group',
          ],
        ]);
    }
    return $route;
  }

  /**
   * Gets the delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteRoute(EntityTypeInterface $entity_type) {
    return $this->getGroupPermissionRoute(
      $entity_type,
      'delete-form',
      [
        '_form' => '\Drupal\group_permissions\Form\GroupPermissionDeleteForm',
        '_title' => 'Delete group permissions',
      ]
    )->setRequirement('_group_permission_entity_access', 'entity.delete');
  }

  /**
   * Gets the canonical route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    return $this->getGroupPermissionRoute(
      $entity_type,
      'canonical',
      [
        '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::view',
        '_title' => 'View group permission',
      ]
    );
  }

  /**
   * Gets the add route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddRoute(EntityTypeInterface $entity_type) {
    return $this->getGroupPermissionRoute(
      $entity_type,
      'add-form',
      [
        '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::addGroupPermission',
        '_title' => 'Add group permission',
      ]
    )->setRequirement('_group_permission_create_access', 'TRUE');
  }

  /**
   * Gets the edit route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEditRoute(EntityTypeInterface $entity_type) {
    return $this->getGroupPermissionRoute(
      $entity_type,
      'edit-form',
      [
        '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::editGroupPermission',
        '_title' => 'Edit group permission',
      ]
    )->setRequirement('_group_permission_entity_access', 'entity.update');
  }

  /**
   * Gets the version history route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getHistoryRoute(EntityTypeInterface $entity_type) {
    return $this->getGroupPermissionRoute(
      $entity_type,
      'version-history',
      [
        '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::revisionOverview',
        '_title' => 'Delete group permissions',
      ]
    )->setRequirement('_group_permission_entity_access', 'entity.list revision');
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getViewRevisionRoute(EntityTypeInterface $entity_type) {
    return $this->setRevisionParameter($this->getGroupPermissionRoute(
      $entity_type,
      'revision',
      [
        '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::revisionShow',
        '_title_callback' => '\Drupal\group_permissions\Controller\GroupPermissionsController::revisionPageTitle',
      ]
    )->setRequirement('_group_permission_entity_access', 'entity.view revision'));
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRevertRoute(EntityTypeInterface $entity_type) {
    return $this->setRevisionParameter($this->getGroupPermissionRoute(
      $entity_type,
      'revision-revert',
      [
        '_form' => '\Drupal\group_permissions\Form\GroupPermissionRevisionRevertForm',
        '_title' => 'Revert to earlier revision',
      ]
    )->setRequirement('_group_permission_entity_access', 'entity.revert revision'));
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type) {
    return $this->setRevisionParameter($this->getGroupPermissionRoute(
      $entity_type,
      'revision-delete',
      [
        '_form' => '\Drupal\group_permissions\Form\GroupPermissionRevisionDeleteForm',
        '_title' => 'Delete revision',
      ]
    )->setRequirement('_group_permission_entity_access', 'entity.delete revision'));
  }

  /**
   * Set revision parameters.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   Route.
   *
   * @return \Symfony\Component\Routing\Route
   *   Modified route.
   */
  protected function setRevisionParameter(Route $route) {
    $parameters = $route->getOption('parameters');
    $parameters['group_permission_revision'] = [
      'type' => 'entity_revision:group_permission',
      'converter' => 'paramconverter.entity_revision',
    ];
    $route->setOption('parameters', $parameters);
    return $route;
  }

}
