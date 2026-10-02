<?php

namespace Drupal\group_permissions\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group_permissions\GroupPermissionsManager;
use Symfony\Component\Routing\Route;

/**
 * Entity create access check for group permission entity.
 */
class GroupPermissionEntityCreateAccessCheck implements AccessInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManager
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a GroupPermissionEntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\group_permissions\GroupPermissionsManager $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionsManager $group_permissions_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupPermissionsManager = $group_permissions_manager;
  }

  /**
   * Checks access to the entity create operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, GroupInterface $group) {
    if ($route->getRequirement('_group_permission_create_access') !== 'TRUE') {
      return AccessResult::neutral();
    }

    // Check for the group permission creation access.
    return $this->entityTypeManager
      ->getAccessControlHandler('group_permission')
      ->createAccess('group_permission', $account, ['group' => $group], TRUE);
  }

}
