<?php

/**
 * @file
 * Provide views data for the download_count.module.
 */

/**
 * Implements hook_views_data().
 */
function download_count_views_data() {

  // Base table.
  $data['download_count']['table']['group'] = t('Download history');
  $data['download_count']['table']['base'] = [
    'field' => 'dcid',
    'title' => t('Download history'),
    'help' => t('Download history of download count module managed file downloads.'),
  ];

  // Join for files table.
  $data['download_count']['table']['join']['file_managed'] = [
    'left_field' => 'fid',
    'field' => 'fid',
  ];

  $data['download_count']['dcid'] = [
    'title' => t('ID'),
    'help' => t('The download count ID of the download.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['download_count']['fid'] = [
    'title' => t('File'),
    'help' => t('The file information of the downloaded file.'),
    'relationship' => [
      'base' => 'file_managed',
      'base field' => 'fid',
      'id' => 'standard',
      'label' => t('File'),
    ],
  ];

  $data['download_count']['uid'] = [
    'title' => t('User'),
    'help' => t('The user who downloaded the file.'),
    'relationship' => [
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('User'),
    ],
  ];

  // Type (entity type).
  $data['download_count']['type'] = [
    'title' => t('Entity Type'),
    'help' => t('The type of the entity from which the file was downloaded.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],

    'filter' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['download_count']['id'] = [
    'title' => t('Entity ID'),
    'help' => t('The id of the entity from which the file was downloaded.'),
    'field' => [
      'id' => 'numeric',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['download_count']['referrer'] = [
    'title' => t('Referrer'),
    'help' => t('Referrer URI.'),
    'field' => [
      'id' => 'url',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['download_count']['ip_address'] = [
    'title' => t('IP Address'),
    'help' => t('IP address of the user who downloaded the file.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['download_count']['timestamp'] = [
    'title' => t('Date-time Downloaded'),
    'help' => t('The date and time the file was downloaded.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function download_count_views_data_alter(&$data) {
  // Join for files table.
  $data['file_managed']['table']['join']['download_count'] = [
    'left_field' => 'fid',
    'field' => 'fid',
  ];
}
