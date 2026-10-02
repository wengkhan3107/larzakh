<?php

namespace Drupal\user_activity_logger\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for User Activity Logger.
 */
class UserActivityLoggerCommands extends DrushCommands {

  /**
   * Clear all user activity logs.
   *
   * @command user-activity:clear
   * @aliases uac
   * @usage drush user-activity:clear
   *   Clears all user activity logs.
   */
  public function clearLogs() {
    \Drupal::database()->truncate('user_activity_log')->execute();
    $this->logger()->success(dt('All user activity logs have been cleared.'));
  }
}
