<?php

/**
 * @file
 * DB additions for the migration path testing of styleswitcher.
 *
 * @depends core/modules/migrate_drupal/tests/fixtures/drupal7.php
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->insert('system')
  ->fields([
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ])
  ->values([
    'filename' => 'modules/styleswitcher/styleswitcher.module',
    'name' => 'styleswitcher',
    'type' => 'module',
    'owner' => '',
    'status' => '1',
    'bootstrap' => '0',
    'schema_version' => '0',
    'weight' => '0',
    'info' => serialize([]),
  ])
  ->execute();

$connection->insert('block')
  ->fields([
    'module',
    'delta',
    'theme',
    'status',
    'region',
    'pages',
    'title',
    'cache',
  ])
  ->values([
    'module' => 'styleswitcher',
    'delta' => 'styleswitcher',
    'theme' => 'bartik',
    'status' => '1',
    'region' => 'sidebar_first',
    'pages' => '',
    'title' => 'Switch Styles',
    'cache' => '4',
  ])
  ->values([
    'module' => 'styleswitcher',
    'delta' => 'styleswitcher',
    'theme' => 'seven',
    'status' => '0',
    'region' => '-1',
    'pages' => '',
    'title' => 'Switch Styles',
    'cache' => '4',
  ])
  ->execute();

$variables = [
  'styleswitcher_enable_overlay' => 0,
  'styleswitcher_7206_theme_default' => 'a_theme_name',
  'styleswitcher_custom_styles' => [
    'custom/something' => [
      'label' => 'Something',
      'name' => 'custom/something',
      'path' => 'public://css/a-custom-style-path.css',
    ],
    'custom/default' => [
      'name' => 'custom/default',
      'label' => 'Default',
      'path' => NULL,
    ],
  ],
  'styleswitcher_styles_settings' => [
    'bortik' => [
      'custom/default' => [
        'weight' => -1,
        'status' => TRUE,
        'is_default' => TRUE,
      ],
      'theme/green_text' => [
        'weight' => 1,
        'status' => TRUE,
      ],
      'custom/default_1' => [
        'weight' => -1,
        'status' => FALSE,
      ],
    ],
    'se7en' => [
      'theme/headless_design' => [
        'weight' => '0',
        'status' => 1,
        'is_default' => TRUE,
      ],
      'custom/default' => [
        'weight' => '10',
        'status' => 0,
        'is_default' => FALSE,
      ],
    ],
  ],
];

$query = $connection->insert('variable')->fields(['name', 'value']);

foreach ($variables as $name => $value) {
  $query->values(['name' => $name, 'value' => serialize($value)]);
}

$query->execute();
