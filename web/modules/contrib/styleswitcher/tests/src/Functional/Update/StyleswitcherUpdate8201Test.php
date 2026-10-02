<?php

namespace Drupal\Tests\styleswitcher\Functional\Update;

use Drupal\Component\Serialization\Yaml;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the styleswitcher_update_8201().
 *
 * @group styleswitcher
 * @group Update
 * @group legacy
 */
class StyleswitcherUpdate8201Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/8201/drupal-8.8.0.styleswitcher.php',
    ];
  }

  /**
   * Tests the styleswitcher_update_8201().
   */
  public function testStyleswitcherUpdate8201() {
    // Make sure we have the expected values before the update.
    $config = $this->config('styleswitcher.custom_styles');
    $this->assertNull($config->get('styles'));
    $this->assertNotNull($config->get('custom/default'));

    $config = $this->config('styleswitcher.styles_settings');
    $this->assertNull($config->get('settings'));
    $this->assertNotNull($config->get('seven.custom/default'));

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $config_names = [
      'styleswitcher.custom_styles',
      'styleswitcher.styles_settings',
    ];

    foreach ($config_names as $name) {
      $actual = $this->config($name)->getRawData();
      $expected = Yaml::decode(file_get_contents(__DIR__ . '/../../../fixtures/update/8201/' . $name . '.after.yml'));
      $this->assertSame($expected, $actual);
    }
  }

}
