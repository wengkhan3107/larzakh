<?php

namespace Drupal\group_permissions\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks group permissions.
 */
class GroupPermissionsEnabledValidator extends ConstraintValidator {

  /**
   * Type-hinting in parent Symfony class is off, let's fix that.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function validate($group_permission, Constraint $constraint) {
    /** @var \Drupal\group_permissions\Entity\GroupPermissionInterface $group_permission */
    $group_type = $group_permission->getGroup()->getGroupType();
    if ($group_type->getThirdPartySetting('group_permissions', 'enabled', 0) === 0) {
      $this->context->addViolation($constraint->message, [
        '%group_type' => $group_type->label(),
      ]);
    }
  }

}
