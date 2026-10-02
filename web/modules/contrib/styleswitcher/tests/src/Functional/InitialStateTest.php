<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WebAssert;

/**
 * Tests the module in its initial state.
 *
 * @group styleswitcher
 */
class InitialStateTest extends BrowserTestBase {

  use HelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['styleswitcher'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the dynamic link tag exists and it's the last of stylesheets.
   */
  public function testDynamicLink() {
    $assert = $this->assertSession();
    $performance_config = $this->config('system.performance');

    $is_preprocessed = $performance_config->get('css.preprocess');

    $this->drupalGet('');
    $this->checkLinkElement($assert, $is_preprocessed);

    // Revert CSS preprocess value.
    $is_preprocessed = !$is_preprocessed;
    $performance_config->set('css.preprocess', $is_preprocessed)->save();

    $this->drupalGet('user/login');
    $this->checkLinkElement($assert, $is_preprocessed);
  }

  /**
   * Checks the link element has all necessary attributes.
   *
   * @param \Drupal\Tests\WebAssert $assert
   *   WebAssert object.
   * @param bool $is_css_preprocessed
   *   Indicated whether CSS are optimized.
   */
  protected function checkLinkElement(WebAssert $assert, bool $is_css_preprocessed) {
    $message = $is_css_preprocessed ? 'CSS are optimized.' : 'CSS are not optimized.';

    $link = $assert->elementExists('css', 'head > link:last-child[rel="stylesheet"]');
    $this->assertSame('styleswitcher-css', $link->getAttribute('id'), $message);
    $this->assertSame('all', $link->getAttribute('media'), $message);

    // The href may contain path prefix and query params.
    $this->assertStringContainsString("/styleswitcher/css/{$this->defaultTheme}", $link->getAttribute('href'), $message);
  }

  /**
   * Tests access to admin routes.
   */
  public function testAdminAccess() {
    $assert = $this->assertSession();

    $paths = [
      'admin/config/user-interface/styleswitcher',
      "admin/config/user-interface/styleswitcher/settings/{$this->defaultTheme}",
      'admin/config/user-interface/styleswitcher/add',
      'admin/config/user-interface/styleswitcher/custom/default',
      'admin/config/user-interface/styleswitcher/custom/default/delete',
    ];

    /** @var string[]|null $permissions */
    /** @var int $code */
    foreach ($this->providerAdminAccess() as [$permissions, $code]) {
      if (isset($permissions)) {
        $user = $this->drupalCreateUser($permissions);
        $this->drupalLogin($user);
      }

      foreach ($paths as $path) {
        $this->drupalGet($path);
        $assert->statusCodeEquals($code);
      }

      if (isset($permissions)) {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Data provider for testAdminAccess().
   *
   * @return array[]
   *   The data sets to test.
   */
  public function providerAdminAccess(): array {
    return [
      [NULL, 403],
      [[], 403],
      [['administer styleswitcher'], 200],
    ];
  }

  /**
   * Tests the Configure link for the Style Switcher exists.
   */
  public function testModuleConfigureLink() {
    $assert = $this->assertSession();

    /** @var string[] $permissions */
    /** @var string $path */
    foreach ($this->providerModuleConfigureLink() as [$permissions, $path]) {
      $user = $this->drupalCreateUser($permissions);
      $this->drupalLogin($user);

      $this->drupalGet($path);
      $assert->linkByHrefExists('/admin/config/user-interface/styleswitcher');

      $this->drupalLogout();
    }
  }

  /**
   * Data provider for testModuleConfigureLink().
   *
   * @return array[]
   *   The data sets to test.
   */
  public function providerModuleConfigureLink(): array {
    return [
      [['administer styleswitcher', 'administer modules'], 'admin/modules'],
      [
        ['administer styleswitcher', 'access administration pages'],
        'admin/config/user-interface',
      ],
    ];
  }

}
