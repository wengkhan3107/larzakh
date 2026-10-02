<?php

namespace Drupal\styleswitcher\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the styleswitcher module.
 */
class DefaultController extends ControllerBase {

  /**
   * The cookie must live 365 days more.
   */
  const COOKIE_EXPIRE = 31536000;

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
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new DefaultController.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The DateTime service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, TimeInterface $time, FileUrlGeneratorInterface $file_url_generator) {
    $this->themeHandler = $theme_handler;
    $this->time = $time;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('datetime.time'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * Switches style when JS is disabled.
   *
   * @param array $style
   *   New active style. The structure of an array is the same as returned from
   *   styleswitcher_style_load().
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Response object.
   *
   * @see styleswitcher_style_load()
   */
  public function styleswitcherSwitch(array $style) {
    if ($style['status']) {
      $this->saveUserPreference($style['theme'], $style['name']);
    }

    return $this->redirect('<front>');
  }

  /**
   * Redirects to CSS file of currently active style.
   *
   * @param string $theme
   *   Name of the theme to find the active style for. This argument is needed
   *   to know what the page user came from and what theme was used there.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object.
   */
  public function styleswitcherCss($theme) {
    // Prevent resource incorrect interpretation.
    $headers = ['Content-Type' => 'text/css'];

    $path = $this->activeStylePath($theme);

    if (isset($path)) {
      $url = $this->fileUrlGenerator->generateAbsoluteString($path);
      return new TrustedRedirectResponse($url, 302, $headers);
    }
    else {
      return new Response('', 200, $headers);
    }
  }

  /**
   * Finds the style active for current user and returns its path.
   *
   * This function is called at every page request before styleswitcherSwitch()
   * or JS' Drupal.styleSwitcher.switchStyle() so we can update old user cookies
   * here once and not bother about it in other places.
   *
   * @param string $theme
   *   Name of the theme to find the active style for.
   *
   * @return string|null
   *   The path property of active style. It can be NULL if active style is the
   *   blank one.
   *
   * @see \Drupal\styleswitcher\Controller\DefaultController::styleswitcherSwitch()
   * @see Drupal.styleSwitcher.switchStyle()
   */
  protected function activeStylePath($theme) {
    // Symfony's InputBag doesn't allow arrays, access the $_COOKIE super global
    // directly.
    // @see \Symfony\Component\HttpFoundation\InputBag::get()
    // @codingStandardsIgnoreLine
    if (isset($_COOKIE['styleswitcher'])) {
      // @codingStandardsIgnoreLine
      $cookie = $_COOKIE['styleswitcher'];

      if (!is_array($cookie)) {
        // This style with its settings belongs to the theme which was default
        // before styleswitcher_update_7206(). If there's no variable, try the
        // default theme, it could still be the same one.
        $style_theme = $this->config('styleswitcher.settings')
          ->get('7206_theme_default') ?? $this->themeHandler->getDefault();

        if (strpos($cookie, '/')) {
          if (styleswitcher_style_load($cookie, $style_theme)) {
            $name = $cookie;
          }
        }
        // Check non-prefixed names too. Try theme's styles before custom
        // because it is more likely that theme's style names remained the same,
        // and custom ones took their places later.
        elseif (($style = styleswitcher_style_load($cookie, $style_theme, 'theme')) || ($style = styleswitcher_style_load($cookie, $style_theme, 'custom'))) {
          $name = $style['name'];
        }

        // Remove this old cookie.
        setcookie('styleswitcher', '', 0, base_path());
        $cookie = [];

        if (isset($name)) {
          // And save the new one.
          $this->saveUserPreference($style_theme, $name);
          $cookie[$style_theme] = $name;
        }
      }

      if (isset($cookie[$theme])) {
        $active = styleswitcher_style_load($cookie[$theme], $theme);
      }
    }
    // Check for cookie with old name, which contained style label. Check only
    // theme's styles because cookie name was changed when styles were still
    // only in theme .info.
    // @codingStandardsIgnoreLine
    elseif (isset($_COOKIE['styleSwitcher'])) {
      // @codingStandardsIgnoreLine
      $name = 'theme/' . _styleswitcher_style_name($_COOKIE['styleSwitcher']);

      // Remove this old cookie.
      setcookie('styleSwitcher', '', 0, base_path());

      // We actually do not know what theme was used (it was a global $theme)
      // when user switched to this style. So let us just set this style as
      // active for every theme which has a style with this name.
      $themes = array_keys($this->themeHandler->listInfo());
      foreach ($themes as $style_theme) {
        if ($style = styleswitcher_style_load($name, $style_theme)) {
          $this->saveUserPreference($style_theme, $name);

          if ($theme == $style_theme) {
            $active = $style;
          }
        }
      }
    }

    if (empty($active)) {
      $active = styleswitcher_style_load(styleswitcher_default_style_key($theme), $theme);
    }

    return $active['path'];
  }

  /**
   * Saves the style key to the cookie.
   *
   * @param string $theme_key
   *   Name of the theme to save the style for.
   * @param string $style_key
   *   Style key to save.
   */
  protected function saveUserPreference($theme_key, $style_key) {
    $request_time = $this->time->getRequestTime();
    setcookie('styleswitcher[' . $theme_key . ']', $style_key, $request_time + static::COOKIE_EXPIRE, base_path());
  }

}
