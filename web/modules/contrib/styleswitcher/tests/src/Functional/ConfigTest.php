<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests Style Switcher configs.
 *
 * @group styleswitcher
 */
class ConfigTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use AssertTrait;
  use HelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'styleswitcher',
    'block',
    'dynamic_page_cache',
    'page_cache',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->enablePageCaching();

    // Place the block and visit a page so the empty block is cached.
    $this->drupalPlaceBlock('styleswitcher_styleswitcher', [
      'id' => 'styleswitcher',
    ]);
    $this->drupalGet('');

    // Create initial custom styles config.
    $this->config('styleswitcher.custom_styles')
      ->set('styles', $this->composeStylesMockingPaths(range('J', 'A')))
      ->save();
  }

  /**
   * Tests the custom styles config.
   */
  public function testConfigCustomStyles() {
    $config = $this->config('styleswitcher.custom_styles');
    $expected = array_column($config->get('styles'), 'label');
    $this->assertStylesList($expected, '#block-styleswitcher');

    // Add a style.
    $config->set('styles.custom/z', $this->composeStyleMockingPath('Z'))->save();
    $expected[] = 'Z';
    $this->assertStylesList($expected, '#block-styleswitcher');

    // Remove a style.
    $i = array_rand($expected);
    $name = 'custom/' . strtolower($expected[$i]);
    $config->clear("styles.$name")->save();

    unset($expected[$i]);
    $this->assertStylesList($expected, '#block-styleswitcher');

    // Relabel a style.
    $i = array_rand($expected);
    $name = 'custom/' . strtolower($expected[$i]);
    $new_label = $this->randomString();
    $config->set("styles.$name.label", $new_label)->save();

    $expected[$i] = $new_label;
    $this->assertStylesList($expected, '#block-styleswitcher');
  }

  /**
   * Tests active style path changes according to the config changes.
   */
  public function testConfigCustomStyleChangesPath() {
    $config = $this->config('styleswitcher.custom_styles');
    $name = array_rand($config->get('styles'));

    $this->drupalGet('');
    $this->click('a.style-switcher.style-' . substr($name, 7));
    $this->assertActiveStylePath($name);

    $new_path = 'new_path';
    $config->set("styles.$name.path", $new_path)->save();
    $this->assertActiveStylePath($new_path);
  }

  /**
   * Tests the styles settings config.
   */
  public function testConfigStylesSettings() {
    $config_styles = $this->config('styleswitcher.custom_styles');
    $config_settings = $this->config('styleswitcher.styles_settings');
    $styles = $config_styles->get('styles');
    $names = array_keys($styles);
    $settings = $this->composeStylesSettings($names);

    // Set weights by a random order.
    shuffle($names);

    foreach ($names as $i => $name) {
      $settings[$name]['weight'] = $i;
    }

    // Set a random default.
    $default = array_rand($settings);
    $settings[$default]['is_default'] = TRUE;
    $config_settings->set("settings.{$this->defaultTheme}", $settings)->save();

    $expected = array_column(array_merge(array_flip($names), $styles), 'label');
    $this->assertStylesList($expected, '#block-styleswitcher');
    // Check the default style by testing an active style's CSS URL.
    $this->assertActiveStylePath($default);

    // Change order.
    shuffle($names);

    foreach ($names as $i => $name) {
      $config_settings->set("settings.{$this->defaultTheme}.$name.weight", $i);
    }

    $config_settings->save();
    $expected = array_column(array_merge(array_flip($names), $styles), 'label');
    $this->assertStylesList($expected, '#block-styleswitcher');

    // Disable random styles.
    foreach (array_rand($names, 5) as $i) {
      $name = $names[$i];
      $config_settings->set("settings.{$this->defaultTheme}.$name.status", FALSE);
      unset($names[$i], $expected[$i]);
    }

    $config_settings->save();
    $this->assertStylesList($expected, '#block-styleswitcher');

    // Set a new default.
    $config_settings->set("settings.{$this->defaultTheme}.$default.is_default", FALSE);
    $enabled = array_flip($names);
    unset($enabled[$default]);
    $default = array_rand($enabled);
    $config_settings->set("settings.{$this->defaultTheme}.$default.is_default", TRUE)->save();
    $this->assertActiveStylePath($default);

    // Remove a custom style, but not from styles settings.
    $config_styles->clear("styles.$default")->save();

    unset($expected[array_search($default, $names)]);
    $this->assertStylesList($expected, '#block-styleswitcher');
  }

  /**
   * Tests active style is replaced with default one when deleted.
   */
  public function testActiveStyleOnStyleDelete() {
    $config_styles = $this->config('styleswitcher.custom_styles');
    $config_settings = $this->config('styleswitcher.styles_settings');

    [$default, $active] = array_rand($config_styles->get('styles'), 2);
    $settings = $this->composeStylesSettings(array_keys($config_styles->get('styles')));
    $settings[$default]['is_default'] = TRUE;
    $config_settings->set("settings.{$this->defaultTheme}", $settings)->save();

    // Click a style, and confirm it's active.
    $this->drupalGet('');
    $this->click('a.style-switcher.style-' . substr($active, 7));
    $this->assertActiveStylePath($active);

    // Remove an active style.
    $config_styles->clear("styles.$active")->save();
    $this->assertActiveStylePath($default);
  }

}
