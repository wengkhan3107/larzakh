<?php

namespace Drupal\Tests\styleswitcher\Functional;

/**
 * Provides test assertions for the Style Switcher.
 */
trait AssertTrait {

  /**
   * Asserts that a block contains an expected list of styles.
   *
   * @param string[] $expected
   *   Expected list of style labels.
   * @param string $block_selector
   *   CSS selector of the Style Switcher block.
   * @param string $page_path
   *   (optional) A path to a page with the block.
   */
  protected function assertStylesList(array $expected, string $block_selector, string $page_path = '') {
    $assert = $this->assertSession();
    $this->drupalGet($page_path);

    if ($expected) {
      // First, test the block exists.
      $block = $assert->elementExists('css', $block_selector);
      $actual = [];

      foreach ($block->findAll('css', 'ul a') as $link) {
        $actual[] = $link->getText();
      }

      $this->assertSame(array_values($expected), $actual);
    }
    else {
      // Test the block doesn't exist.
      $assert->elementNotExists('css', $block_selector);
    }
  }

  /**
   * Asserts that an active style's CSS URL ends with a given suffix.
   *
   * @param string $expected_suffix
   *   Expected URL suffix.
   * @param string $theme
   *   (optional) Name of the theme to check an active style for. NULL for
   *   $this->defaultTheme.
   */
  protected function assertActiveStylePath(string $expected_suffix, string $theme = NULL) {
    $session = $this->getSession();

    $this->drupalGet('styleswitcher/css/' . ($theme ?? $this->defaultTheme));
    $this->assertStringEndsWith($expected_suffix, $session->getCurrentUrl());
  }

}
