<?php

namespace Drupal\Tests\styleswitcher\Kernel\Migrate\d7;

use Drupal\block\Entity\Block;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of the Style Switcher block to a configuration entity.
 *
 * @group styleswitcher
 */
class MigrateBlockTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['styleswitcher', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/migrate/drupal7.styleswitcher.php');

    // Install the themes used for this test.
    $this->container->get('theme_installer')->install(['olivero', 'claro']);

    $this->installConfig(static::$modules);

    // Set Olivero and Claro as the default public and admin theme.
    $config = $this->config('system.theme');
    $config->set('default', 'olivero');
    $config->set('admin', 'claro');
    $config->save();

    $this->executeMigrations(['d7_styleswitcher_block']);
    block_rebuild();
  }

  /**
   * Tests the block migration.
   *
   * @see \Drupal\Tests\block\Kernel\Migrate\d7\MigrateBlockTest::assertEntity()
   * @see \Drupal\Tests\block\Kernel\Migrate\d7\MigrateBlockTest::testBlockMigration()
   */
  public function testBlockMigration() {
    $block = Block::load('bartik_styleswitcher_styleswitcher');
    $this->assertInstanceOf(Block::class, $block);
    /** @var \Drupal\block\BlockInterface $block */
    $this->assertSame('styleswitcher_styleswitcher', $block->getPluginId());

    $this->assertSame('sidebar', $block->getRegion());
    $this->assertSame('olivero', $block->getTheme());
    $this->assertTrue($block->status());

    $config = $this->config('block.block.bartik_styleswitcher_styleswitcher');
    $this->assertSame('Switch Styles', $config->get('settings.label'));
    $this->assertSame('visible', $config->get('settings.label_display'));

    // Assert that disabled blocks did not migrate.
    $non_existent_blocks = ['seven_styleswitcher_styleswitcher'];
    $this->assertTrue(empty(Block::loadMultiple($non_existent_blocks)));
  }

}
