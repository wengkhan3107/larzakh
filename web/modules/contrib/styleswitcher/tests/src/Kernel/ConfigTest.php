<?php

namespace Drupal\Tests\styleswitcher\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Style Switcher configs.
 *
 * @group styleswitcher
 */
class ConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['styleswitcher'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
  }

  /**
   * Tests that corresponding configs are deleted when themes are uninstalled.
   */
  public function testThemesUninstalled() {
    $theme_installer = \Drupal::service('theme_installer');

    // Install a theme and create some styles settings.
    $theme_installer->install(['styleswitcher_test_theme']);
    $this->config('styleswitcher.styles_settings')
      ->set('settings.styleswitcher_test_theme', ['style' => []])
      ->save();

    // Uninstall a theme.
    $theme_installer->uninstall(['styleswitcher_test_theme']);
    $this->assertArrayNotHasKey('styleswitcher_test_theme', $this->config('styleswitcher.styles_settings')->get('settings'));
  }

}
