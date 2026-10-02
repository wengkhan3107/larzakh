<?php

namespace Drupal\userprotect_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test controller for local task testing.
 */
class TestController extends ControllerBase {

  /**
   * Returns a simple test page.
   */
  public function testPage() {
    return [
      '#markup' => 'Test page',
    ];
  }

}
