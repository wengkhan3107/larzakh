<?php

namespace Drupal\group_permissions\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks where group permissions are enabled.
 */
class GroupPermissionEnabledAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    if ($route->getRequirement('_group_permissions_enabled') !== 'TRUE') {
      return AccessResult::neutral();
    }

    $group = $route_match->getParameter('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    if ($group->getGroupType()->getThirdPartySetting('group_permissions', 'enabled', 0)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
