<?php

namespace Drupal\download_count\Plugin\QueueWorker;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Queue Worker for Download count cache.
 *
 * @QueueWorker(
 *   id = "download_count",
 *   title = @Translation("Download Count Cache Processor"),
 *   cron = {"time" = 60}
 * )
 */
class DownloadCountCacheProcessor extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Logs a notice.
    $connection = Database::getConnection();
    $connection->merge('download_count_cache')
      ->key([
        'type' => $data->type,
        'id' => $data->id,
        'fid' => $data->fid,
        'date' => $data->date,
      ])
      ->fields([
        'count' => $data->count,
      ])
      ->expression('count', 'count + :inc', [':inc' => $data->count])
      ->execute();
  }

}
