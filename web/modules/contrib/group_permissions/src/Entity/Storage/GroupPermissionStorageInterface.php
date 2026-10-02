<?php

namespace Drupal\group_permissions\Entity\Storage;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines an interface for group content entity storage classes.
 */
interface GroupPermissionStorageInterface {

  /**
   * Retrieves Group permission entity for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group content entities for.
   *
   * @return \Drupal\group\Entity\GroupPermissiontInterface[]
   *   A list of GroupPermission entity matching the criteria.
   */
  public function loadByGroup(GroupInterface $group);

  /**
   * Get all active group permissions.
   *
   * @return array
   *   List of group permissions
   */
  public function getAllActive();

  /**
   * Gets list of revision IDs having a given user as group permissions author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Group permissions revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
