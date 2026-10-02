<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Tests with themes that have styleswitcher config.
 *
 * @group styleswitcher
 */
class MultiThemeTest extends BrowserTestBase {

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
   * An associative array of styles for themes.
   *
   * @var array[]
   */
  protected $themeStyles;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->themeStyles = [
      'styleswitcher_test_theme' => [
        'back' => ['Default', 'Yellow on Purple', 'Green text', 'Blue back'],
        'front' => ['Yellow on Purple', 'Green text', 'Blue back'],
      ],
      'styleswitcher_test_base_theme' => [
        'back' => ['Default', 'Yellow on Purple'],
        'front' => ['Default', 'Yellow on Purple'],
      ],
      'stark' => [
        'back' => ['Default'],
        'front' => [],
      ],
    ];

    $this->enablePageCaching();
    $this->drupalPlaceBlock('local_tasks_block', ['id' => 'local_tasks_block']);
    \Drupal::service('theme_installer')->install(array_keys($this->themeStyles));

    // Place the Style Switcher block in each of themes.
    foreach (array_keys($this->themeStyles) as $theme) {
      $this->drupalPlaceBlock('styleswitcher_styleswitcher', [
        'id' => 'styleswitcher_' . $theme,
        'theme' => $theme,
      ]);
    }
  }

  /**
   * Tests config theme forms.
   */
  public function testConfigThemeForms() {
    $assert = $this->assertSession();

    $user = $this->drupalCreateUser(['administer styleswitcher']);
    $this->drupalLogin($user);

    foreach ($this->themeStyles as $theme => $styles) {
      $styles = $styles['back'];

      // Test the tab exists.
      $this->drupalGet('admin/config/user-interface/styleswitcher');
      $tabs = $this->getSession()->getPage()
        ->find('css', '#block-local-tasks-block');
      $theme_config_path = '/admin/config/user-interface/styleswitcher/settings/' . $theme;
      $tab = $assert->elementExists('css', 'a[href$="' . $theme_config_path . '"]', $tabs);

      // Test the form exists.
      $tab->click();
      $form = $assert->elementExists('css', 'form#styleswitcher-config-theme');

      // Test the number of styles.
      $rows = $form->findAll('css', '#styleswitcher-styles-table tbody tr');
      $this->assertCount(count($styles), $rows);

      // Test styles are in order.
      foreach ($styles as $i => $label) {
        $label_cell = $rows[$i]->find('css', 'td:first-child');

        $this->assertStringContainsString($label, $label_cell->getText());
      }
    }
  }

  /**
   * Tests links in the Style Switcher block.
   */
  public function testBlockLinks() {
    foreach ($this->themeStyles as $theme => $styles) {
      // Open a page where the $theme is active.
      $page_path = 'styleswitcher-test-theme-per-page/' . $theme;
      $block_selector = '#block-styleswitcher-' . strtr($theme, '_', '-');

      $this->assertStylesList($styles['front'], $block_selector, $page_path);
    }
  }

  /**
   * Tests the dynamic link tag.
   */
  public function testDynamicLink() {
    $assert = $this->assertSession();

    foreach (array_keys($this->themeStyles) as $theme) {
      // Open a page where the $theme is active.
      $this->drupalGet('styleswitcher-test-theme-per-page/' . $theme);
      $link = $assert->elementExists('css', 'head > link:last-child[rel="stylesheet"]');

      // The href may contain path prefix and query params.
      $this->assertStringContainsString('/styleswitcher/css/' . $theme, $link->getAttribute('href'));
    }
  }

  /**
   * Tests style switching without javascript.
   */
  public function testNoJsStyleSwitching() {
    $assert = $this->assertSession();

    // Open a page where the test theme is active. The theme's default style
    // must be active at first.
    $this->drupalGet('styleswitcher-test-theme-per-page/styleswitcher_test_theme');
    $this->checkStyleFullyApplied('blue_back', 'styleswitcher_test_theme', TRUE);

    // Test style switching.
    $this->click('a.style-switcher.style-green-text');
    // Test we're on the same page after the click.
    $assert->addressEquals('styleswitcher-test-theme-per-page/styleswitcher_test_theme');
    $this->checkStyleFullyApplied('green_text', 'styleswitcher_test_theme');

    // Leave the shared style (provided by a base theme) as active for this
    // theme before going to the base theme.
    $this->click('a.style-switcher.style-yellow-on-purple');
    $this->checkStyleFullyApplied('yellow_on_purple', 'styleswitcher_test_theme');

    // Open a page where the base theme is active. Although the shared style is
    // active for the child theme, it must not be active for this one. This
    // theme doesn't have own default style, so the blank style must be active.
    $this->drupalGet('styleswitcher-test-theme-per-page/styleswitcher_test_base_theme');
    $this->checkStyleFullyApplied('default', 'styleswitcher_test_base_theme', TRUE);

    $this->click('a.style-switcher.style-yellow-on-purple');
    // Test we're on the same page after the click.
    $assert->addressEquals('styleswitcher-test-theme-per-page/styleswitcher_test_base_theme');
    $this->checkStyleFullyApplied('yellow_on_purple', 'styleswitcher_test_base_theme');

    // Test the blank style resets others.
    $this->click('a.style-switcher.style-default');
    $this->checkStyleFullyApplied('default', 'styleswitcher_test_base_theme');

    // Test the active style on the previous theme is preserved.
    $this->drupalGet('styleswitcher-test-theme-per-page/styleswitcher_test_theme');
    $this->checkStyleFullyApplied('yellow_on_purple', 'styleswitcher_test_theme');
  }

  /**
   * Checks the style is fully applied.
   *
   * @param string $name
   *   Style name.
   * @param string $theme
   *   Theme name.
   * @param bool $no_cookie
   *   (optional) Whether a cookie is unset or set.
   */
  protected function checkStyleFullyApplied(string $name, string $theme, bool $no_cookie = FALSE) {
    $styles = [
      'default' => [
        'type' => 'custom',
        'css_suffix' => 'styleswitcher/css/' . $theme,
      ],
      'yellow_on_purple' => [
        'type' => 'theme',
        'css_suffix' => '/yellow-on-purple.css',
      ],
      'green_text' => [
        'type' => 'theme',
        'css_suffix' => '/green-fg.css',
      ],
      'blue_back' => [
        'type' => 'theme',
        'css_suffix' => '/blue-bg.css',
      ],
    ];

    $session = $this->getSession();

    // Test the cookie.
    $cookie = $no_cookie ? NULL : ($styles[$name]['type'] . '/' . $name);
    $this->assertSame($cookie, $session->getCookie('styleswitcher[' . $theme . ']'));

    // Test an active style's CSS URL.
    $this->assertActiveStylePath($styles[$name]['css_suffix'], $theme);

    // Return to the HTML page.
    $this->drupalGet('styleswitcher-test-theme-per-page/' . $theme);
  }

}
