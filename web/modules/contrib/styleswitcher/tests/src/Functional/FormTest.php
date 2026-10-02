<?php

namespace Drupal\Tests\styleswitcher\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Style Switcher forms.
 *
 * @group styleswitcher
 */
class FormTest extends BrowserTestBase {

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
   * Base path of the admin section.
   *
   * @var string
   */
  protected $adminPath = 'admin/config/user-interface/styleswitcher';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $user = $this->drupalCreateUser(['administer styleswitcher']);
    $this->drupalLogin($user);
  }

  /**
   * Tests the main admin form exists and functions.
   */
  public function testMainAdminForm() {
    $assert = $this->assertSession();

    // Test the form exists.
    $this->drupalGet($this->adminPath);
    $assert->elementExists('css', 'form#styleswitcher-admin');
    $assert->checkboxChecked('enable_overlay');

    // Test the form is configurable.
    $edit = [
      'enable_overlay' => '0',
    ];
    $this->submitForm($edit, 'Save configuration');
    $assert->pageTextContains('The configuration options have been saved.');
    $assert->checkboxNotChecked('enable_overlay');
  }

  /**
   * Tests add/edit and delete operations with a custom style.
   */
  public function testCustomStyleOps() {
    $assert = $this->assertSession();

    // Test the form exists.
    $this->drupalGet($this->adminPath . '/add');
    $assert->elementExists('css', 'form#styleswitcher-style-form');

    // Create a style.
    $path = 'public://styleswitcher_style.css';
    file_put_contents($path, '');
    $label = $this->randomString();
    $name = strtolower($this->randomMachineName(8));
    $edit = [
      'label' => $label,
      'name' => $name,
      'path' => $path,
    ];
    $this->submitForm($edit, 'Save');
    $assert->pageTextContains('has been saved.');

    // Test new style is in the table and config.
    $assert->elementTextContains('css', '#edit-styleswitcher-custom-styles tbody', $label);
    $this->assertArrayHasKey('custom/' . $name, $this->config('styleswitcher.custom_styles')->get('styles'));

    // Test the style is editable.
    $this->drupalGet($this->adminPath . '/custom/' . $name);
    $assert->elementExists('css', 'form#styleswitcher-style-form');
    $name = strtolower($this->randomMachineName(10));
    $edit = [
      'name' => $name,
    ];
    $this->submitForm($edit, 'Save');
    $assert->pageTextContains('has been saved.');
    $this->assertArrayHasKey('custom/' . $name, $this->config('styleswitcher.custom_styles')->get('styles'));

    // Test the style is deletable.
    $assert->linkByHrefExists($this->adminPath . '/custom/' . $name . '/delete');
    $this->drupalGet($this->adminPath . '/custom/' . $name);
    $assert->linkByHrefExists($this->adminPath . '/custom/' . $name . '/delete');
    $this->drupalGet($this->adminPath . '/custom/' . $name . '/delete');
    $assert->elementExists('css', 'form#styleswitcher-style-delete-form');
    $this->submitForm([], 'Confirm');
    $assert->pageTextContains('has been deleted.');
    $assert->elementTextNotContains('css', '#edit-styleswitcher-custom-styles tbody', $label);
    $this->assertArrayNotHasKey('custom/' . $name, $this->config('styleswitcher.custom_styles')->get('styles'));
  }

  /**
   * Tests edit and delete operations with a default empty style.
   */
  public function testEmptyStyleOps() {
    $assert = $this->assertSession();

    // Test the default empty style is in the table.
    $this->drupalGet($this->adminPath);
    $assert->elementTextContains('css', '#edit-styleswitcher-custom-styles tbody', 'Default');

    // Test the style is in the theme config form, default.
    $this->drupalGet($this->adminPath . "/settings/{$this->defaultTheme}");
    $assert->fieldValueEquals('settings[default]', 'custom/default');
    $assert->fieldDisabled('settings[enabled][custom/default]');

    // Test the style is editable and in the config after saving.
    $this->drupalGet($this->adminPath . '/custom/default');
    $assert->elementExists('css', 'form#styleswitcher-style-form');
    $label = $this->randomString();
    $name = strtolower($this->randomMachineName(10));
    $edit = [
      'label' => $label,
      'name' => $name,
    ];
    $this->submitForm($edit, 'Save');
    $assert->pageTextContains('has been saved.');
    $styles = [
      'custom/' . $name => [
        'name' => 'custom/' . $name,
        'label' => $label,
        'path' => NULL,
      ],
    ];
    $this->assertSame($styles, $this->config('styleswitcher.custom_styles')->get('styles'));
    $this->assertArrayNotHasKey('custom/default', $this->config('styleswitcher.custom_styles')->get('styles'));

    // Test the style is not deletable.
    $assert->linkByHrefNotExists($this->adminPath . '/custom/' . $name . '/delete');
    $this->drupalGet($this->adminPath . '/custom/' . $name);
    $assert->linkByHrefNotExists($this->adminPath . '/custom/' . $name . '/delete');
    $this->drupalGet($this->adminPath . '/custom/' . $name . '/delete');
    $assert->elementExists('css', 'form#styleswitcher-style-delete-form');
    $this->submitForm([], 'Confirm');
    $assert->pageTextContains('The blank style cannot be deleted.');
    $assert->elementTextContains('css', '#edit-styleswitcher-custom-styles tbody', $label);
    $this->assertArrayHasKey('custom/' . $name, $this->config('styleswitcher.custom_styles')->get('styles'));
  }

  /**
   * Tests a validation error when trying to add a style with existing name.
   */
  public function testAddStyleExistingName() {
    $assert = $this->assertSession();

    $this->config('styleswitcher.custom_styles')
      ->set('styles', $this->composeStyles(['A' => NULL]))
      ->save();

    $this->drupalGet($this->adminPath . '/add');
    $path = 'public://styleswitcher_style.css';
    file_put_contents($path, '');
    $edit = [
      'label' => 'B',
      'name' => 'a',
      'path' => $path,
    ];
    $this->submitForm($edit, 'Save');

    $assert->pageTextContains('The machine-readable name is already in use. It must be unique.');
    $assert->elementExists('css', 'form#styleswitcher-style-form');
    $this->assertSame('A', $this->config('styleswitcher.custom_styles')->get('styles.custom/a.label'));
  }

  /**
   * Tests a validation error on setting a style other existing name.
   */
  public function testEditStyleExistingName() {
    $assert = $this->assertSession();

    $path = 'public://styleswitcher_style.css';
    file_put_contents($path, '');

    $this->config('styleswitcher.custom_styles')
      ->set('styles', $this->composeStyles(['A' => $path, 'B' => NULL]))
      ->save();

    $this->drupalGet($this->adminPath . '/custom/b');
    $this->submitForm(['name' => 'a'], 'Save');

    $assert->pageTextContains('The machine-readable name is already in use. It must be unique.');
    $assert->elementExists('css', 'form#styleswitcher-style-form');
    $this->assertSame('A', $this->config('styleswitcher.custom_styles')->get('styles.custom/a.label'));
  }

  /**
   * Tests the styles settings config changes when a style is renamed via form.
   */
  public function testStylesSettingsOnStyleRename() {
    $this->config('styleswitcher.custom_styles')
      ->set('styles', $this->composeStyles(['A' => NULL]))
      ->save();
    $this->config('styleswitcher.styles_settings')
      ->set('settings.theme_z', ['custom/a' => []])
      ->save();

    // Rename the style via form.
    $this->drupalGet($this->adminPath . '/custom/a');
    $this->submitForm(['name' => 'b'], 'Save');

    $this->assertArrayHasKey('custom/b', $this->config('styleswitcher.styles_settings')->get('settings.theme_z'));
    $this->assertArrayNotHasKey('custom/a', $this->config('styleswitcher.styles_settings')->get('settings.theme_z'));
  }

  /**
   * Tests the config theme form exists and functions properly.
   */
  public function testConfigThemeForm() {
    $assert = $this->assertSession();

    // Mock custom styles.
    $styles = $this->composeStylesMockingPaths(range('A', 'J'));

    $default = array_rand($styles);
    $styles[$default]['path'] = NULL;

    $this->config('styleswitcher.custom_styles')
      ->set('styles', $styles)
      ->save();

    // Test the form exists.
    $this->drupalGet($this->adminPath . "/settings/{$this->defaultTheme}");

    // Test the config theme form's field values correspond to the styles config
    // without the style settings config.
    foreach (array_keys($styles) as $x) {
      $assert->elementTextContains('css', '#styleswitcher-styles-table tbody', 'Machine name: ' . $x);
      $assert->fieldValueEquals('settings[weight][' . $x . ']', 0);

      if ($x !== $default) {
        $assert->checkboxChecked('settings[enabled][' . $x . ']');
      }
    }

    $assert->fieldDisabled('settings[enabled][' . $default . ']');
    $assert->fieldValueEquals('settings[default]', $default);

    // Change field values.
    $edit = [];
    $expected = $this->composeStylesSettings(array_keys($styles));

    // Set random weights.
    foreach (array_keys($styles) as $x) {
      $weight = rand(-5, 5);
      $edit['settings[weight][' . $x . ']'] = $expected[$x]['weight'] = $weight;
    }

    // Disable 5 random styles, except the current default.
    unset($styles[$default]);
    $disabled = array_rand($styles, 5);

    foreach ($disabled as $x) {
      $edit['settings[enabled][' . $x . ']'] = $expected[$x]['status'] = FALSE;
    }

    // Set a random disabled style as default.
    $default = $disabled[array_rand($disabled)];
    $edit['settings[default]'] = $default;
    $expected[$default]['is_default'] = $expected[$default]['status'] = TRUE;

    $this->submitForm($edit, 'Save configuration');

    // Test config is saved correctly.
    $assert->pageTextContains('The configuration options have been saved.');
    $this->assertSame($expected, $this->config('styleswitcher.styles_settings')->get("settings.{$this->defaultTheme}"));
  }

}
