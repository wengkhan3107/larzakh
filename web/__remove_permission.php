<?php

use Drupal\user\Entity\Role;

// Load the role.
$role = Role::load('anonymous');
if ($role) {
  // Remove the permission.
  $permissions = $role->getPermissions();
  if (in_array('create test workflow_transition', $permissions)) {
    $role->revokePermission('create test workflow_transition');
    $role->save();
    echo "Permission removed.";
  } else {
    echo "Permission not found for this role.";
  }
} else {
  echo "Role not found.";
}