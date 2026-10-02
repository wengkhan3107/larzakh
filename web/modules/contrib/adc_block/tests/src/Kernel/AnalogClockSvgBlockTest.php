<?php

namespace Drupal\Tests\adc_block\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Form\FormState;

/**
 * Tests the SVG Analog Clock block configuration and build.
 *
 * @group adc_block
 */
class AnalogClockSvgBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'user', 'adc_block'];

  /**
   * Tests that the block configuration flattens correctly on submit.
   */
  public function testBlockConfigurationFlattening() {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    /** @var \Drupal\adc_block\Plugin\Block\AnalogClockSvgBlock $block */
    $block = $block_manager->createInstance('svg_clock_analog_dynamic', []);

    // Simulate the nested values from the form.
    $form_state = new FormState();
    $values = [
      'dimensions' => ['clock_size' => 500],
      'background' => ['bg_type' => 'gradient', 'bg_color' => '#000000'],
      'effects' => ['enable_glow' => TRUE],
    ];
    $form_state->setValues($values);

    // Run the submit handler.
    $form = [];
    $block->blockSubmit($form, $form_state);

    // Verify the configuration was flattened into the main array.
    $config = $block->getConfiguration();
    $this->assertEquals(500, $config['clock_size']);
    $this->assertEquals('gradient', $config['bg_type']);
    $this->assertTrue($config['enable_glow']);
  }

  /**
   * Tests the build render array.
   */
  public function testBlockBuildOutput() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block = $block_manager->createInstance('svg_clock_analog_dynamic', [
      'clock_size' => 450,
    ]);

    $build = $block->build();

    // Verify the theme hook and basic variable passing.
    $this->assertEquals('svg_clock_analog_dynamic', $build['#theme']);
    $this->assertStringContainsString('analog-clock-', $build['#block_id']);
    $this->assertEquals(450, $build['#config']['clock_size']);
    $this->assertContains('adc_block/clocks', $build['#attached']['library']);
  }

}
