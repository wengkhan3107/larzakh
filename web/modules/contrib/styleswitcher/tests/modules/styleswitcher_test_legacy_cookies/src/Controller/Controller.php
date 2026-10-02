<?php

namespace Drupal\styleswitcher_test_legacy_cookies\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Style Switcher Test Legacy Cookies routes.
 */
class Controller extends ControllerBase {

  /**
   * The Datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new Controller.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The DateTime service.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, TimeInterface $time) {
    $this->themeHandler = $theme_handler;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('datetime.time')
    );
  }

  /**
   * Sets cookies.
   *
   * @param int $version
   *   Version of a Style Switcher cookie to set.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response object.
   */
  public function cookieSetter($version) {
    $theme = $this->themeHandler->getDefault();
    $expires = $this->time->getRequestTime() + 60;
    $path = base_path();

    switch ($version) {
      case 1:
        setcookie('styleSwitcher', 'Active', $expires, $path);
        break;

      case 2:
        setcookie('styleswitcher', 'active', $expires, $path);
        break;

      case 3:
        setcookie('styleswitcher', 'theme/active', $expires, $path);
        break;

      case 4:
        setcookie('styleswitcher[' . $theme . ']', 'theme/active', $expires, $path);
        break;
    }

    return $this->redirect('<front>');
  }

}
