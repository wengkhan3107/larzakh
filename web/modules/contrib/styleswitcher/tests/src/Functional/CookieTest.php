<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests Style Switcher cookies.
 *
 * @group styleswitcher
 */
class CookieTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use AssertTrait;
  use HelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'styleswitcher',
    'styleswitcher_test_theme_per_page',
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
    $this->drupalPlaceBlock('styleswitcher_styleswitcher', [
      'id' => 'styleswitcher',
    ]);
  }

  /**
   * Tests the cookie is set for the whole site.
   */
  public function testCookie() {
    $this->config('styleswitcher.custom_styles')
      ->set('styles', $this->composeStylesMockingPaths(['A', 'B']))
      ->save();

    $settings = $this->composeStylesSettings(['custom/a', 'custom/b']);
    $settings['custom/a']['is_default'] = TRUE;

    $this->config('styleswitcher.styles_settings')
      ->set("settings.{$this->defaultTheme}", $settings)
      ->save();

    // Check the default style is acting as active without a cookie.
    $this->assertActiveStylePath('custom/a');

    // Click a style, and test the cookie.
    $this->drupalGet('user/login');
    $this->click('a.style-switcher.style-b');
    $this->assertSame('custom/b', $this->getSession()->getCookie("styleswitcher[{$this->defaultTheme}]"));

    // Check the cookie on another page.
    $this->drupalGet("styleswitcher-test-theme-per-page/{$this->defaultTheme}");
    $this->assertSame('custom/b', $this->getSession()->getCookie("styleswitcher[{$this->defaultTheme}]"));
  }

}
