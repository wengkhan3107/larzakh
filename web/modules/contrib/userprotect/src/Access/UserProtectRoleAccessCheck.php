<?php

namespace Drupal\userprotect\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines an access control handler for user roles.
 *
 * @package Drupal\userprotect\Access
 */
class UserProtectRoleAccessCheck implements AccessInterface {

  /**
   * Custom access check for the /user/%/roles.
   *
   * This check will only occur when role_delegation is enabled.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    // Try to get the user from route parameters. This would be the user whose
    // roles may be edited.
    $user = $route_match->getParameter('user');

    // If "user" is not available as a route parameter, we can't perform this
    // check.
    if (!$user instanceof UserInterface) {
      return AccessResult::neutral();
    }

    // Check if the account may edit the roles of the user.
    $access_result = $user->access('user_roles', $account) ? AccessResult::allowed() : AccessResult::forbidden();
    return $access_result->cachePerUser()->addCacheableDependency($user);
  }

}
