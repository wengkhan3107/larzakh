<?php

namespace Drupal\group_permissions;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupAccessResult;
use Drupal\group_permissions\Entity\GroupPermissionInterface;

/**
 * Access controller for the Group permission entity.
 *
 * @see \Drupal\group_permissions\Entity\GroupPermission.
 */
class GroupPermissionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof GroupPermissionInterface);

    $operations = [
      'delete',
      'update',
      'list revision',
      'view revision',
      'revert revision',
      'delete revision',
    ];
    if (in_array($operation, $operations)) {
      return GroupAccessResult::allowedIfHasGroupPermission($entity->getGroup(), $account, 'override group permissions');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return GroupAccessResult::allowedIfHasGroupPermission($context['group'], $account, 'override group permissions');
  }

}
