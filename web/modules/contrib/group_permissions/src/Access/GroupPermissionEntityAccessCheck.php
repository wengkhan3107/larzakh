<?php

namespace Drupal\group_permissions\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\group_permissions\GroupPermissionsManager;
use Symfony\Component\Routing\Route;

/**
 * Entity access check for group permission entity.
 */
class GroupPermissionEntityAccessCheck implements AccessInterface {

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a GroupPermissionEntityAccessCheck object.
   *
   * @param \Drupal\group_permissions\GroupPermissionsManager $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(GroupPermissionsManager $group_permissions_manager) {
    $this->groupPermissionsManager = $group_permissions_manager;
  }

  /**
   * Checks access to the entity operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   * @param \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permission_revision
   *   The group permission revision.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, GroupInterface $group, GroupPermissionInterface $group_permission_revision = NULL) {
    // Split the entity type and the operation.
    $requirement = $route->getRequirement('_group_permission_entity_access');
    [, $operation] = explode('.', $requirement);

    if (!empty($group_permission_revision)) {
      return $group_permission_revision->access($operation, $account, TRUE);
    }

    // Get group permission entity.
    $group_permission_entity = $this->groupPermissionsManager->loadByGroup($group);

    // Check access when the group permission entity exists.
    if ($group_permission_entity) {
      return $group_permission_entity->access($operation, $account, TRUE);
    }

    return AccessResult::forbidden();
  }

}
