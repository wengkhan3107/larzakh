<?php

namespace Drupal\Tests\userprotect\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\userprotect\Plugin\UserProtection\UserProtectionManager;
use Drupal\userprotect_test\Plugin\UserProtection\AnnotateUserProtectionPlugin;
use Drupal\userprotect_test\Plugin\UserProtection\AttributeUserProtectionPlugin;

/**
 * Tests UserProtectionManager with both annotated and attributed plugins.
 *
 * @group userprotect
 */
class UserProtectionManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['userprotect', 'userprotect_test'];

  /**
   * The UserProtection plugin manager.
   */
  protected UserProtectionManager $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Get a plugin manager for testing.
    $this->pluginManager = $this->container->get('plugin.manager.userprotect.user_protection');
  }

  /**
   * Tests if UserProtection plugins defined with annotation can be found.
   */
  public function testFindAnnotatedPlugins() {
    $definitions = $this->pluginManager->getDefinitions();

    $expected = [
      'id' => 'annotate_user_protection',
      'label' => new TranslatableMarkup('Annotate User Protection plugin'),
      'description' => new TranslatableMarkup('Used for testing if this plugin is found by UserProtectionManager.'),
      'weight' => 0,
      'status' => FALSE,
      'provider' => 'userprotect_test',
      'class' => AnnotateUserProtectionPlugin::class,
    ];

    $actual = $definitions['annotate_user_protection'];
    // Remove dependencies key if present (added in Drupal 11.3+).
    unset($actual['dependencies']);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests if UserProtection plugins defined with attributes can be found.
   */
  public function testFindAttributedPlugins() {
    if (!class_exists('\Drupal\Component\Plugin\Attribute\Plugin')) {
      // No need to execute test.
      $this->markTestSkipped('Attribute-plugins are not supported in Drupal 9.');
    }
    $definitions = $this->pluginManager->getDefinitions();

    $expected = [
      'id' => 'attribute_user_protection',
      'label' => new TranslatableMarkup('Attribute User Protection plugin'),
      'description' => new TranslatableMarkup('Used for testing if this plugin is found by \\Drupal\\userprotect\\Plugin\\UserProtection\\UserProtectionManager.'),
      'weight' => 0,
      'status' => FALSE,
      'provider' => 'userprotect_test',
      'class' => AttributeUserProtectionPlugin::class,
    ];

    $actual = $definitions['attribute_user_protection'];
    // Remove dependencies key if present (added in Drupal 11.3+).
    unset($actual['dependencies']);

    $this->assertEquals($expected, $actual);
  }

}
