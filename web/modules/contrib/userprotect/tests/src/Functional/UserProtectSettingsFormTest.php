<?php

namespace Drupal\Tests\userprotect\Functional;

/**
 * Tests the User Protect settings form.
 *
 * @group userprotect
 */
class UserProtectSettingsFormTest extends UserProtectBrowserTestBase {

  /**
   * The operating account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->drupalCreateUser(['userprotect.administer']);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests enabling the display_applied_protections_message setting.
   */
  public function testEnableSetting() {
    // Start by disabling the setting programmatically since it's enabled by
    // default.
    \Drupal::configFactory()->getEditable('userprotect.settings')
      ->set('display_applied_protections_message', FALSE)
      ->save();

    // Verify the setting is disabled.
    $this->assertFalse(\Drupal::config('userprotect.settings')->get('display_applied_protections_message'));

    // Enable the setting through the form.
    $this->drupalGet('admin/config/people/userprotect/settings');
    $edit = [
      'display_applied_protections_message' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Verify the setting was saved.
    $this->assertTrue(\Drupal::config('userprotect.settings')->get('display_applied_protections_message'));

    // Submit the form again without changes to verify the current setting is
    // displayed correctly.
    $this->drupalGet('admin/config/people/userprotect/settings');
    $this->assertSession()->checkboxChecked('display_applied_protections_message');
    $this->submitForm([], 'Save configuration');

    // Verify the setting is still enabled after submitting without changes.
    $this->assertTrue(\Drupal::config('userprotect.settings')->get('display_applied_protections_message'));
  }

  /**
   * Tests disabling the display_applied_protections_message setting.
   */
  public function testDisableSetting() {
    // Verify the setting is enabled by default.
    $this->assertTrue(\Drupal::config('userprotect.settings')->get('display_applied_protections_message'));

    // Disable the setting through the form.
    $this->drupalGet('admin/config/people/userprotect/settings');
    $edit = [
      'display_applied_protections_message' => FALSE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Verify the setting was saved.
    $this->assertFalse(\Drupal::config('userprotect.settings')->get('display_applied_protections_message'));

    // Submit the form again without changes to verify the current setting is
    // displayed correctly.
    $this->drupalGet('admin/config/people/userprotect/settings');
    $this->assertSession()->checkboxNotChecked('display_applied_protections_message');
    $this->submitForm([], 'Save configuration');

    // Verify the setting is still disabled after submitting without changes.
    $this->assertFalse(\Drupal::config('userprotect.settings')->get('display_applied_protections_message'));
  }

}
