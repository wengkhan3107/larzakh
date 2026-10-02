<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Style Switcher block.
 *
 * @group styleswitcher
 */
class BlockTest extends BrowserTestBase {

  use HelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['styleswitcher', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the Style Switcher block exists and functions as a normal block.
   */
  public function testBlockExists() {
    $assert = $this->assertSession();

    $user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($user);

    // Test if the block exists.
    $this->drupalGet('admin/structure/block/add/styleswitcher_styleswitcher');
    $assert->fieldValueEquals('id', 'styleswitcher');

    // Test if the block is configurable.
    $label = $this->randomString();
    $edit = [
      'settings[label]' => $label,
      'region' => 'sidebar_first',
    ];
    $this->submitForm($edit, 'Save block');
    $assert->pageTextContains('The block configuration has been saved.');

    // Test the block is added to the block layout.
    $assert->elementTextContains('css', '#blocks', $label);
  }

}
