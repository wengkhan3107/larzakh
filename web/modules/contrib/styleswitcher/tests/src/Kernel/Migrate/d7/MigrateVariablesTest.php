<?php

namespace Drupal\Tests\styleswitcher\Kernel\Migrate\d7;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Style Switcher variables to configuration objects.
 *
 * @group styleswitcher
 */
class MigrateVariablesTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['styleswitcher'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/migrate/drupal7.styleswitcher.php');

    $this->installConfig(static::$modules);
  }

  /**
   * Tests the variables migration.
   */
  public function testVariablesMigration() {
    // This somehow prevents DB connection serialization issues.
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_styleswitcher_settings',
      'd7_styleswitcher_custom_styles',
      'd7_styleswitcher_styles_settings',
    ]);

    // Make sure there's no migration exceptions.
    $this->assertSame([], $this->migrateMessages);

    // Make sure we have the expected values after the migration.
    $settings = $this->config('styleswitcher.settings');
    $this->assertFalse($settings->get('enable_overlay'));
    $this->assertSame('a_theme_name', $settings->get('7206_theme_default'));

    $config_names = [
      'styleswitcher.custom_styles',
      'styleswitcher.styles_settings',
    ];

    foreach ($config_names as $name) {
      $actual = $this->config($name)->getRawData();
      unset($actual['_core']);
      $expected = Yaml::decode(file_get_contents(__DIR__ . '/../../../../fixtures/migrate/' . $name . '.after.yml'));
      $this->assertSame($expected, $actual);
    }
  }

  /**
   * Tests migration with unset variables.
   */
  public function testMigrationWithUnsetVariables() {
    $variables = [
      'styleswitcher_enable_overlay',
      'styleswitcher_7206_theme_default',
      'styleswitcher_styles_settings',
    ];
    $this->sourceDatabase->delete('variable')
      ->condition('name', $variables, 'IN')
      ->execute();

    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_styleswitcher_settings',
      'd7_styleswitcher_styles_settings',
    ]);

    // Make sure there's no migration exceptions.
    $this->assertSame([], $this->migrateMessages);

    // Make sure default settings are as they were default in D7.
    $settings = $this->config('styleswitcher.settings');
    $this->assertTrue($settings->get('enable_overlay'));
    $this->assertNull($settings->get('7206_theme_default'));

    $this->assertSame([], $this->config('styleswitcher.styles_settings')->get('settings'));
  }

}
