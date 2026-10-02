<?php

namespace Drupal\Tests\styleswitcher\Functional\Update;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Helps install database additions for a testing of update paths.
 */
class UpdatePathTestInstallHelper {

  /**
   * The DB connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected static $connection;

  /**
   * Gets the DB connection object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The DB connection object.
   */
  protected static function getConnection(): Connection {
    if (!isset(static::$connection)) {
      static::$connection = Database::getConnection();
    }

    return static::$connection;
  }

  /**
   * Enables a module or theme by adding it to the core.extension config.
   *
   * @param string $name
   *   Extension name.
   * @param int $weight
   *   (optional) Extension weight.
   * @param string $type
   *   (optional) Extension type (module/theme).
   */
  public static function enableExtension(string $name, int $weight = 0, string $type = 'module') {
    $extensions = static::getConnection()->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();

    $extensions = unserialize($extensions, ['allowed_classes' => FALSE]);
    $extensions[$type][$name] = $weight;
    $extensions = serialize($extensions);

    static::getConnection()->update('config')
      ->fields(['data' => $extensions])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();
  }

  /**
   * Sets a schema version for a module or profile.
   *
   * @param string $name
   *   Extension name.
   * @param int $version
   *   Version to set, e.g. 8000.
   */
  public static function setSchemaVersion(string $name, int $version) {
    static::getConnection()->merge('key_value')
      ->condition('collection', 'system.schema')
      ->condition('name', $name)
      ->fields([
        'collection' => 'system.schema',
        'name' => $name,
        'value' => serialize($version),
      ])
      ->execute();
  }

  /**
   * Imports a config from a YAML file.
   *
   * @param string $name
   *   Config name.
   * @param string $filename
   *   Path and name of the YAML file to import.
   */
  public static function addConfigYml(string $name, string $filename) {
    $config = Yaml::decode(file_get_contents($filename));

    static::getConnection()->insert('config')
      ->fields([
        'collection' => '',
        'name' => $name,
        'data' => serialize($config),
      ])
      ->execute();
  }

}
