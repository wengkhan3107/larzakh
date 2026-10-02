<?php

/**
 * @file
 * Rules specific functions for the download_count module.
 */

/**
 * Implements hook_rules_event_info().
 */
function download_count_rules_event_info() {
  return [
    'download_count_file_download' => [
      'group' => t('Download Count'),
      'label' => t('A file has been downloaded'),
      'module' => 'download_count',
      'variables' => [
        'file' => [
          'type' => 'file',
          'label' => t('Downloaded file'),
        ],
        'user' => [
          'type' => 'user',
          'label' => t('Downloading user'),
        ],
        'node' => [
          'type' => 'node',
          'label' => t('Node the file download is associated with'),
        ],
      ],
    ],
  ];
}
