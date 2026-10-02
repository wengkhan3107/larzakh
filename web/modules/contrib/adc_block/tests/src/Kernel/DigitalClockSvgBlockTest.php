<?php

namespace Drupal\Tests\adc_block\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Form\FormState;

/**
 * Tests the SVG Digital Clock block configuration and build logic.
 *
 * @group adc_block
 */
class DigitalClockSvgBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'user', 'adc_block'];

  /**
   * Tests that the nested form values.
   *
   * Correctly flattened into configuration.
   */
  public function testBlockConfigurationFlattening() {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    /** @var \Drupal\adc_block\Plugin\Block\DigitalClockSvgBlock $block */
    $block = $block_manager->createInstance('svg_clock_digital_dynamic', []);

    // Simulate the nested values from the complex blockForm.
    $form_state = new FormState();
    $values = [
      'display' => [
        'clock_width' => 450,
        'time_format' => '12',
      ],
      'font' => [
        'font_family' => 'sans-serif',
        'font_weight' => '900',
      ],
      'advanced' => [
        'uppercase' => TRUE,
      ],
    ];
    $form_state->setValues($values);

    // Run the submit handler.
    $form = [];
    $block->blockSubmit($form, $form_state);

    // Verify the configuration was flattened into the
    // main plugin configuration.
    $config = $block->getConfiguration();
    $this->assertEquals(450, $config['clock_width']);
    $this->assertEquals('12', $config['time_format']);
    $this->assertEquals('sans-serif', $config['font_family']);
    $this->assertTrue($config['uppercase']);
  }

  /**
   * Tests the build render array for correct theme and dynamic ID.
   */
  public function testBlockBuildOutput() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block = $block_manager->createInstance('svg_clock_digital_dynamic', [
      'time_color' => '#00FF00',
      'font_family' => 'monospace',
    ]);

    $build = $block->build();

    // Verify the theme hook.
    $this->assertEquals('svg_clock_digital_dynamic', $build['#theme']);

    // Verify dynamic ID generation (ensures unique SVG containers).
    $this->assertStringContainsString('digital-clock-', $build['#block_id']);

    // Verify configuration pass-through.
    $this->assertEquals('#00FF00', $build['#config']['time_color']);
    $this->assertEquals('monospace', $build['#config']['font_family']);

    // Verify asset attachment.
    $this->assertContains('adc_block/clocks', $build['#attached']['library']);
  }

  /**
   * Tests the timezone options helper.
   */
  public function testTimezoneOptions() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block = $block_manager->createInstance('svg_clock_digital_dynamic', []);

    // Use reflection to test the protected method.
    $class = new \ReflectionClass(get_class($block));
    $method = $class->getMethod('getTimezoneOptions');
    $method->setAccessible(TRUE);

    $options = $method->invoke($block);

    $this->assertArrayHasKey('UTC', $options);
    $this->assertArrayHasKey('America/New_York', $options);
    $this->assertEquals('America/New York', $options['America/New_York']);
  }

}
