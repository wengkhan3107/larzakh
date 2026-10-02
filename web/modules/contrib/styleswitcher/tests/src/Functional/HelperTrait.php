<?php

namespace Drupal\Tests\styleswitcher\Functional;

/**
 * Provides helper methods for the Style Switcher testing.
 */
trait HelperTrait {

  /**
   * Retrieves a Drupal path and checks for exceptions.
   *
   * @see \Drupal\Tests\UiHelperTrait::drupalGet()
   *
   * @todo Remove this method once the https://www.drupal.org/i/3137242 mystery
   *   is solved.
   */
  protected function drupalGet($path, array $options = [], array $headers = []) {
    $out = parent::drupalGet($path, $options, $headers);

    if (strpos($out, 'The website encountered an unexpected error') !== FALSE) {
      $out = preg_replace('/<\/?br\s*\/?>|<pre class="backtrace">/i', PHP_EOL, $out);
      $out = trim(htmlspecialchars_decode(strip_tags($out), ENT_QUOTES));
      throw new \Exception($out);
    }

    return $out;
  }

  /**
   * Fills and submits a form and checks for exceptions.
   *
   * @see \Drupal\Tests\UiHelperTrait::submitForm()
   *
   * @todo Remove this method once the https://www.drupal.org/i/3137242 mystery
   *   is solved.
   */
  protected function submitForm(array $edit, $submit, $form_html_id = NULL) {
    parent::submitForm($edit, $submit, $form_html_id);
    $out = $this->getSession()->getPage()->getContent();

    if (strpos($out, 'The website encountered an unexpected error') !== FALSE) {
      $out = preg_replace('/<\/?br\s*\/?>|<pre class="backtrace">/i', PHP_EOL, $out);
      $out = trim(htmlspecialchars_decode(strip_tags($out), ENT_QUOTES));
      throw new \Exception($out);
    }
  }

  /**
   * Helps compose the Styles array.
   *
   * @param array $label_paths
   *   Array with "label => path" associations.
   * @param string $type
   *   (optional) Style type: "custom" or "theme".
   *
   * @return array[]
   *   Styles list as in the config.
   */
  protected function composeStyles(array $label_paths, string $type = 'custom'): array {
    $styles = [];

    foreach ($label_paths as $label => $path) {
      $name = $type . '/' . strtolower($label);
      $styles[$name] = ['name' => $name, 'label' => $label, 'path' => $path];
    }

    return $styles;
  }

  /**
   * Helps compose the Styles array, mocking styles' paths.
   *
   * @param string[] $labels
   *   Styles' labels.
   * @param string $type
   *   (optional) Style type: "custom" or "theme".
   *
   * @return array[]
   *   Styles list as in the config.
   */
  protected function composeStylesMockingPaths(array $labels, string $type = 'custom'): array {
    $styles = [];

    foreach ($labels as $label) {
      $name = $type . '/' . strtolower($label);
      $styles[$name] = ['name' => $name, 'label' => $label, 'path' => $name];
    }

    return $styles;
  }

  /**
   * Helps compose a single Style, mocking its path.
   *
   * @param string $label
   *   Style's label.
   * @param string $type
   *   (optional) Style type: "custom" or "theme".
   *
   * @return array
   *   A Style with a name, a label and a path.
   */
  protected function composeStyleMockingPath(string $label, string $type = 'custom'): array {
    $styles = $this->composeStylesMockingPaths([$label], $type);
    return reset($styles);
  }

  /**
   * Helps compose the Styles Settings array with default values.
   *
   * @param string[] $keys
   *   Styles' names.
   *
   * @return array[]
   *   Styles Settings list as in the config.
   */
  protected function composeStylesSettings(array $keys): array {
    return array_fill_keys($keys, [
      'weight' => 0,
      'status' => TRUE,
      'is_default' => FALSE,
    ]);
  }

}
