<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests Style Switcher cookie updates.
 *
 * @group styleswitcher
 */
class CookieUpdateTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use AssertTrait;
  use HelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'styleswitcher',
    'styleswitcher_test_legacy_cookies',
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
  }

  /**
   * Tests cookie updates.
   *
   * @param int $version
   *   Version of a Style Switcher cookie to test.
   * @param string $cookie_name
   *   Old cookie name.
   */
  #[DataProvider('providerCookieUpdates')]
  public function testCookieUpdates(int $version, string $cookie_name) {
    $styles['custom/default'] = $this->composeStyleMockingPath('Default');
    // Mock a theme style by saving it to the custom styles config.
    $styles['theme/active'] = $this->composeStyleMockingPath('Active', 'theme');
    $this->config('styleswitcher.custom_styles')
      ->set('styles', $styles)
      ->save();

    $settings = $this->composeStylesSettings(['custom/default', 'theme/active']);
    $settings['custom/default']['is_default'] = TRUE;

    $this->config('styleswitcher.styles_settings')
      ->set("settings.{$this->defaultTheme}", $settings)
      ->save();

    $this->assertActiveStylePath('custom/default');
    $this->drupalGet('styleswitcher-test-legacy-cookies/' . $version);
    $this->assertActiveStylePath('theme/active');
    $this->assertSame('theme/active', $this->getSession()->getCookie("styleswitcher[{$this->defaultTheme}]"));
    $this->assertNull($this->getSession()->getCookie($cookie_name));
  }

  /**
   * Data provider for testCookieUpdates().
   *
   * @return array[]
   *   The data sets to test.
   */
  public static function providerCookieUpdates(): array {
    return [
      [1, 'styleSwitcher'],
      [2, 'styleswitcher'],
      [3, 'styleswitcher'],
    ];
  }

}
