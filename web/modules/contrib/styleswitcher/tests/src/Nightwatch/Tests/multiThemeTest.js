/**
 * @file
 * Tests Style Switcher JS settings and behaviors.
 */

module.exports = {
  '@tags': ['styleswitcher'],

  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      // eslint-disable-next-line no-unused-expressions
      browser
        // Install modules.
        .drupalRelativeURL('/admin/modules')
        .click('input[name="modules[styleswitcher][enable]"]')
        .click('#edit-modules-testing > summary')
        .click(
          'input[name="modules[styleswitcher_test_theme_per_page][enable]"]',
        )
        .click('input[name="modules[block][enable]"]')
        .click('input[name="modules[dynamic_page_cache][enable]"]')
        .click('input[name="modules[page_cache][enable]"]')
        .click('input[type="submit"]')

        // Enable the test theme.
        .drupalRelativeURL('/admin/appearance')
        .click('a[title="Install Style Switcher Test as default theme"]')

        // Enable page caching.
        .drupalRelativeURL('/admin/config/development/performance')
        .click('select[name="page_cache_maximum_age"]')
        .click('option[value="300"]')
        .click('input[value="Save configuration"]')

        // Disable the overlay when styles are being switched.
        // @todo Test with enabled overlay.
        .drupalRelativeURL('/admin/config/user-interface/styleswitcher')
        .click('input[name="enable_overlay"]')
        .click('input[type="submit"]')
        .expect.element('input[name="enable_overlay"]').not.selected;

      // Place Style Switcher blocks in each of themes to test.
      [
        'styleswitcher_test_theme',
        'styleswitcher_test_base_theme',
        'stark',
      ].forEach((theme) => {
        browser
          .drupalRelativeURL(
            `/admin/structure/block/add/styleswitcher_styleswitcher/${theme}?region=sidebar_first`,
          )
          .click('input[type="submit"]');
      });
    });
  },

  after(browser) {
    browser.drupalUninstall();
  },

  'Test js settings': (browser) => {
    browser
      // Test the Style Switcher js behavior and settings are present when the
      // block with styles is present.
      .drupalRelativeURL(
        '/styleswitcher-test-theme-per-page/styleswitcher_test_theme',
      )
      .waitForElementVisible('body', 1000)
      .execute(
        'return typeof Drupal.behaviors.styleSwitcher;',
        [],
        (result) => {
          browser.verify.strictEqual(result.value, 'object');
        },
      )
      .execute('return drupalSettings.styleSwitcher;', [], (result) => {
        browser.verify.strictEqual(typeof result.value, 'object');
        browser.verify.strictEqual(
          result.value.theme,
          'styleswitcher_test_theme',
        );
        browser.verify.strictEqual(result.value.enableOverlay, false);
      })

      // Test the Style Switcher js behavior and settings are absent when the
      // Style Switcher block is absent.
      .drupalRelativeURL('/styleswitcher-test-theme-per-page/stark')
      .waitForElementVisible('body', 1000)
      .execute(
        'window.Drupal = window.Drupal || { behaviors: {} }; return typeof Drupal.behaviors.styleSwitcher;',
        [],
        (result) => {
          browser.verify.strictEqual(result.value, 'undefined');
        },
      )
      .execute(
        'window.drupalSettings = window.drupalSettings || {}; return drupalSettings.styleSwitcher;',
        [],
        (result) => {
          browser.verify.strictEqual(result.value, null);
        },
      )

      .drupalLogAndEnd({ onlyOnError: false });
  },

  'Test style switching': (browser) => {
    // Styles provided by the installed themes.
    const styles = {};
    styles.default = {
      hyphenated: 'default',
      type: 'custom',
      bg: 'rgba(255, 255, 255, 1)',
      fg: 'rgba(0, 0, 0, 1)',
    };
    styles.yellow_on_purple = {
      hyphenated: 'yellow-on-purple',
      type: 'theme',
      bg: 'rgba(128, 0, 128, 1)',
      fg: 'rgba(255, 255, 0, 1)',
    };
    styles.green_text = {
      hyphenated: 'green-text',
      type: 'theme',
      bg: styles.default.bg,
      fg: 'rgba(0, 128, 0, 1)',
    };
    styles.blue_back = {
      hyphenated: 'blue-back',
      type: 'theme',
      bg: 'rgba(0, 0, 255, 1)',
      fg: styles.default.fg,
    };

    const checkStyleFullyApplied = (name, theme, noCookie = false) => {
      // Test the document styles.
      browser.assert.cssProperty('body', 'background-color', styles[name].bg);
      browser.assert.cssProperty('body', 'color', styles[name].fg);

      // Test the style is marked as active, and it's the only active one in the
      // list.
      browser.expect.elements('a.style-switcher.active').count.equal(1);
      // eslint-disable-next-line no-unused-expressions
      browser.expect.element(
        `a.style-switcher.active.style-${styles[name].hyphenated}`,
      ).present;

      // Test the cookie.
      browser.getCookie(`styleswitcher[${theme}]`, (cookie) => {
        if (noCookie) {
          browser.assert.strictEqual(cookie, null);
        } else {
          browser.assert.strictEqual(
            cookie.value,
            `${styles[name].type}%2F${name}`,
          );
        }
      });
    };

    // Open a page where the test theme is active. The theme's default style
    // must be active at first.
    let theme = 'styleswitcher_test_theme';
    browser
      .drupalRelativeURL(`/styleswitcher-test-theme-per-page/${theme}`)
      .waitForElementVisible('body', 1000);
    checkStyleFullyApplied('blue_back', theme, true);

    // Place a new page element. If it doesn't exist after a link clicking, that
    // means a page is reloaded.
    browser.execute(
      // eslint-disable-next-line func-names, prefer-arrow-callback
      function () {
        const el = document.createElement('div');
        document.body.appendChild(el);
        el.setAttribute('id', 'styleswitcher-test-page-not-reloaded');
      },
    );

    // Test style switching.
    browser.click('a.style-switcher.style-green-text');
    checkStyleFullyApplied('green_text', theme);

    // eslint-disable-next-line no-unused-expressions
    browser.expect.element('#styleswitcher-test-page-not-reloaded').present;

    // Leave the shared style (provided by a base theme) as active for this
    // theme before going to the base theme.
    browser.click('a.style-switcher.style-yellow-on-purple');
    checkStyleFullyApplied('yellow_on_purple', theme);

    // Open a page where the base theme is active. Although the shared style is
    // active for the child theme, it must not be active for this one. This
    // theme doesn't have own default style, so the blank style must be active.
    theme = 'styleswitcher_test_base_theme';
    browser
      .drupalRelativeURL(`/styleswitcher-test-theme-per-page/${theme}`)
      .waitForElementVisible('body', 1000);
    checkStyleFullyApplied('default', theme, true);

    browser.click('a.style-switcher.style-yellow-on-purple');
    checkStyleFullyApplied('yellow_on_purple', theme);

    // Test the blank style resets others.
    browser.click('a.style-switcher.style-default');
    checkStyleFullyApplied('default', theme);

    // Test that routes are not cached, PHP can read the cookie set from JS and
    // the active style on the previous theme is preserved.
    theme = 'styleswitcher_test_theme';
    browser
      .drupalRelativeURL(`/styleswitcher-test-theme-per-page/${theme}`)
      .waitForElementVisible('body', 1000);
    checkStyleFullyApplied('yellow_on_purple', theme);

    // Check the cookie on another page.
    browser
      .drupalRelativeURL('/user/login')
      .waitForElementVisible('body', 1000);
    checkStyleFullyApplied('yellow_on_purple', theme);

    browser.drupalLogAndEnd({ onlyOnError: false });
  },
};
