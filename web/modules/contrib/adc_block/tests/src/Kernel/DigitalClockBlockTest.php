<?php

namespace Drupal\Tests\adc_block\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Form\FormState;

/**
 * Tests the Digital Clock block logic, DI, and configuration.
 *
 * @group adc_block
 */
class DigitalClockBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'block', 'adc_block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Required to load date format entities used in the form.
    $this->installEntitySchema('date_format');
    $this->installConfig(['system']);
  }

  /**
   * Tests that the block is correctly instantiated via ContainerFactory.
   */
  public function testBlockInstantiation() {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    $block = $block_manager->createInstance('adc_block_digital_block', []);

    $this->assertInstanceOf('\Drupal\adc_block\Plugin\Block\DigitalClockBlock', $block);
  }

  /**
   * Tests that build() returns the correct date format logic.
   */
  public function testBlockBuildLogic() {
    $block_manager = \Drupal::service('plugin.manager.block');

    // Configure block to show date.
    $config = [
      'layout' => 'custom',
      'show_date' => TRUE,
    // Standard Drupal format.
      'date_format' => 'html_date',
      'description_text' => '<em>Safe</em> <script>alert("bad")</script>',
    ];

    $block = $block_manager->createInstance('adc_block_digital_block', $config);
    $build = $block->build();

    // 1. Verify Security: Description must be escaped
    // (HTML tags removed/encoded).
    $this->assertStringNotContainsString('<script>', $build['#data']['description_text']);
    $this->assertStringContainsString('&lt;em&gt;Safe&lt;/em&gt;', $build['#data']['description_text']);

    // 2. Verify Date Logic: current_date should not be empty.
    $this->assertNotEmpty($build['#data']['current_date']);

    // 3. Verify Cache: Must be 0 for a clock.
    $this->assertEquals(0, $block->getCacheMaxAge());
  }

  /**
   * Tests the flattening of nested form values on submission.
   */
  public function testConfigurationSubmission() {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block = $block_manager->createInstance('adc_block_digital_block', []);

    $form_state = new FormState();
    $form_state->setValues([
      'layout_settings' => [
        'layout' => 'custom',
        'time_color' => '#FF0000',
      ],
      'description_settings' => [
        'description_text' => 'Hello World',
      ],
    ]);

    $form = [];
    $block->submitConfigurationForm($form, $form_state);

    $config = $block->getConfiguration();
    $this->assertEquals('#FF0000', $config['time_color']);
    $this->assertEquals('Hello World', $config['description_text']);
  }

}
