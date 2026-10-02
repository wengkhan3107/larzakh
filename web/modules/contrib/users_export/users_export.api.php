<?php

/**
 * @file
 * Hooks for the users_export module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the instruction.
 *
 * @param array $uids
 *   Contains an array of user ids to export.
 * @param array $context
 *   Context of the export (format, sort).
 */
function hook_users_export_user_list_alter(array &$uids, array &$context) {
  $context['format'] = 'csv';
}

/**
 * @} End of "addtogroup hooks".
 */
