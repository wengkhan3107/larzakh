<?php

namespace Drupal\Tests\adc_block\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Serialization\Json;

/**
 * Tests the Analog Clock block logic and configuration.
 *
 * @group adc_block
 */
class AnalogClockBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'block', 'adc_block'];

  /**
   * Tests that the block build returns sanitized and correct preset data.
   */
  public function testBlockBuildLogic() {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    // 1. Test Default Configuration.
    $config = [
      'layout' => 'layout1',
      'heading' => '<b>Unsafe Heading</b>',
    ];

    /** @var \Drupal\adc_block\Plugin\Block\AnalogClockBlock $block */
    $block = $block_manager->createInstance('adc_block_block', $config);
    $build = $block->build();

    // Verify Security: Heading must be escaped.
    $this->assertEquals('&lt;b&gt;Unsafe Heading&lt;/b&gt;', $build['clock']['#content']['heading']);

    // Verify Logic: Data must contain layout1 presets.
    $data = Json::decode($build['clock']['#data']);
    $this->assertEquals('layout1', $data['layout']);
    $this->assertEquals('#292a2d', $data['fillColor']);

    // 2. Test Custom Configuration.
    $custom_config = [
      'layout' => 'custom',
      'fillColor' => '#ff0000',
    ];
    $block = $block_manager->createInstance('adc_block_block', $custom_config);
    $build = $block->build();
    $data = Json::decode($build['clock']['#data']);

    $this->assertEquals('#ff0000', $data['fillColor']);
  }

  /**
   * Tests that cache max-age is always 0 for real-time clocks.
   */
  public function testCacheMaxAge() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block = $block_manager->createInstance('adc_block_block', []);
    $this->assertEquals(0, $block->getCacheMaxAge());
  }

}
