<?php

namespace Drupal\group_permissions\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that group permissions are enabled.
 *
 * @Constraint(
 *   id = "GroupPermissionsEnabled",
 *   label = @Translation("Checks that group permissions are enabled", context = "Validation"),
 *   type = "entity:group_permission"
 * )
 */
class GroupPermissionsEnabled extends Constraint {

  /**
   * The message to show when group permissions are disabled.
   *
   * @var string
   */
  public $message = 'Group permissions are disabled for %group_type" group type';

}
