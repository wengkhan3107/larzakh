<?php

/**
 * @file
 * Provides views data for the birthday module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 */
function views_birthday_field_views_data_alter(array &$data, FieldStorageConfigInterface $field) {
  if ($field->getType() === 'datetime') {
    $field_name = $field->getName() . '_' . $field->getMainPropertyName();

    foreach ($data as $table_name => $table_data) {
      $reference = $data[$table_name][$field_name];

      $data[$table_name][$field_name . '_next_birthday'] = $reference;
      unset($data[$table_name][$field_name . '_next_birthday']['argument']);

      $title_args = $data[$table_name][$field_name . '_next_birthday']['title']->getArguments();
      $data[$table_name][$field_name . '_next_birthday']['title'] =
          t('@label (@name:@column)', [
            '@label' => $title_args['@label'],
            '@name' => $title_args['@name'],
            '@column' => 'next_birthday',
          ]);
      $data[$table_name][$field_name . '_next_birthday']['help'] = t('Next birthday date');
      $data[$table_name][$field_name . '_next_birthday']['filter']['id'] = 'birthday';
      $data[$table_name][$field_name . '_next_birthday']['sort']['id'] = 'birthday';
    }
  }
}
