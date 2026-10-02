<?php

namespace Drupal\styleswitcher_test_theme_per_page\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Style Switcher Test Theme Per Page routes.
 */
class Controller extends ControllerBase {

  /**
   * Returns a demo page with an active theme set by the parameter.
   *
   * @return array
   *   A render array.
   */
  public function demo() {
    return [];
  }

}
