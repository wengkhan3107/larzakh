/**
 * @file
 * Functionality that provides style switching without page reloading.
 */

((Drupal, drupalSettings, $, once) => {
  /**
   * Namespace for styleswitcher-related functionality for Drupal.
   *
   * @namespace
   */
  Drupal.styleSwitcher = {};

  /**
   * Gets the visitor's active style or saves the style key to the cookie.
   *
   * @param {string|undefined} styleName
   *   (optional) Style key to save.
   *
   * @return {string|undefined}
   *   The key of active style in case if styleName is not provided and
   *   corresponding cookie exists in visitor's browser.
   */
  Drupal.styleSwitcher.cookie = (styleName) => {
    const key = `styleswitcher[${drupalSettings.styleSwitcher.theme}]`;

    if (typeof styleName !== 'undefined') {
      let expires = new Date();
      expires.setTime(
        expires.getTime() + drupalSettings.styleSwitcher.cookieExpire * 1000,
      );
      expires = expires.toUTCString();

      // Follow PHP way: do not encode cookie name but encode the value. This
      // consistency must be preserved because both PHP and JS are used for work
      // with styleswitcher cookies.
      const value = encodeURIComponent(styleName);
      document.cookie = `${key}=${value}; expires=${expires}; path=${drupalSettings.path.baseUrl}`;
    } else if (document.cookie) {
      const cookies = document.cookie.split('; ');
      let i;
      let parts;

      for (i = 0; i < cookies.length; i++) {
        parts = cookies[i].split('=');

        if (parts[0] === key) {
          return decodeURIComponent(parts[1]);
        }
      }
    }
  };

  /**
   * Given the style object, switches stylesheets.
   *
   * @param {object} style
   *   A style object.
   */
  Drupal.styleSwitcher.switchStyle = (style) => {
    // Update the cookie first.
    Drupal.styleSwitcher.cookie(style.name);

    // Now switch the stylesheet. Path is absolute URL with scheme.
    $('#styleswitcher-css').attr('href', style.path);

    // Cosmetic changes.
    Drupal.styleSwitcher.switchActiveLink(style.name);
  };

  /**
   * Switches active style link.
   *
   * @param {string} styleName
   *   Machine name of the active style.
   */
  Drupal.styleSwitcher.switchActiveLink = (styleName) => {
    $('.style-switcher')
      .removeClass('active')
      .filter(`[data-rel="${styleName}"]`)
      .addClass('active');
  };

  /**
   * Builds an overlay for transition from one style to another.
   *
   * @return {HTMLElement}
   *   Dom object of overlay.
   */
  Drupal.styleSwitcher.buildOverlay = () =>
    $('<div>').attr('id', 'style-switcher-overlay').appendTo($('body')).hide();

  /**
   * Removes overlay.
   */
  Drupal.styleSwitcher.killOverlay = () => {
    $('#style-switcher-overlay').remove();
  };

  /**
   * Binds a switch behavior on links clicking.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach style switching behavior to relevant context.
   */
  Drupal.behaviors.styleSwitcher = {
    attach(context, settings) {
      // Set active link. It is not set from PHP, because of pages caching.
      const activeStyle =
        Drupal.styleSwitcher.cookie() || settings.styleSwitcher.default;
      Drupal.styleSwitcher.switchActiveLink(activeStyle);

      $(once('styleswitcher', '.style-switcher', context)).click((e) => {
        const $link = $(e.currentTarget).blur();
        const name = $link.attr('data-rel');
        const style = settings.styleSwitcher.styles[name];

        if (settings.styleSwitcher.enableOverlay) {
          const $overlay = Drupal.styleSwitcher.buildOverlay();

          $overlay.fadeIn('slow', () => {
            Drupal.styleSwitcher.switchStyle(style);
            $overlay.fadeOut('slow', Drupal.styleSwitcher.killOverlay);
          });
        } else {
          Drupal.styleSwitcher.switchStyle(style);
        }

        e.preventDefault();
      });
    },
  };
})(Drupal, drupalSettings, jQuery, once);
