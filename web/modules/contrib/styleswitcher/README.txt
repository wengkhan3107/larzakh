--------------------------------------------------------------------------------
                                Style Switcher 2
--------------------------------------------------------------------------------

The module allows website visitors to choose which stylesheet they would like to
view the site with.

--------------------------------------------------------------------------------
INTRODUCTION
--------------------------------------------------------------------------------

This module takes the fuss out of creating themes or building sites with
alternate stylesheets. Themer can provide a theme with alternate stylesheets.
Site builder can add alternate stylesheets found in the internet right in the
admin section. And this module presents all those styles to site visitors as a
list of links in a block. So any site visitor is able to choose the style of
the site they prefer. The module uses cookies so that when people return to the
site or visit a different page they still get their chosen style.

There are many commonly known JavaScript techniques to switch CSS stylesheets
"live", without reloading the page. Some examples of them and relevant
JavaScript files can be found here:
 * https://alistapart.com/article/alternate/
 * http://2008.kelvinluck.com/2006/05/switch-stylesheets-with-jquery.html
 * https://www.immortalwolf.com/code-repository/javascript-components/

This module uses a slightly different and more powerful mechanism. But the logic
is the same: click a link » get the new look of the site.

Works in all major browsers.

For more information about the module, visit the documentation guide:
 * https://www.drupal.org/docs/8/modules/style-switcher
To submit bug reports and feature suggestions, or to track changes:
 * https://www.drupal.org/project/issues/styleswitcher

--------------------------------------------------------------------------------
INSTALLATION
--------------------------------------------------------------------------------

Install as you would normally install a contributed Drupal module. For more
information refer:
 * https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

--------------------------------------------------------------------------------
UPDATE
--------------------------------------------------------------------------------

If you're updating from a previous minor version don't forget to run DB updates.
For more information refer:
 * https://www.drupal.org/docs/8/update/update-modules

--------------------------------------------------------------------------------
MIGRATION
--------------------------------------------------------------------------------

It's possible to migrate this module from Drupal 7 to 8.

It's important to migrate only from the latest stable 7.x-2 version of the
module. Refer to a list of releases to find out which is the latest one:
 * https://www.drupal.org/project/styleswitcher/releases?version=7.x-2
If you're using an earlier version, you first need to update to the latest one.

It's also possible to migrate from the styleswitcher 7.x-1.x-dev directly. If it
doesn't work for you, then you first need to update to the latest stable 7.x-2
release.

Migrating from Drupal 6 is not and will not be supported, as Drupal 6 reached
the end of its life.

For further migration instructions see:
 * https://www.drupal.org/docs/8/upgrade

--------------------------------------------------------------------------------
CONFIGURATION
--------------------------------------------------------------------------------

There are 2 ways to configure the list of styles for switching. And they can be
combined.

 * One way is for site admins:

   1. Create CSS files in site's public directory, for example: in the
      sites/default/files, or anywhere in the internet. Just remember that these
      locations along with the CSS files must be publicly accessible.

   2. Go to Administration » Configuration » User interface » Style Switcher.
      There you'll find a module's configuration form and a link to add
      stylesheets for switching. Add all your styles providing their labels and
      paths.

 * Another way is mostly for themers. For site builders it is appropriate only
   if you use a custom theme that you are able to edit:

   1. In your theme's directory or subdirectory create CSS files for each
      different style.

      Note that each stylesheet when switched is being added to the existing set
      of other site's stylesheets.

   2. In your theme's .info.yml file add a section like the following, replacing
      labels and paths with yours:

        styleswitcher:
          css:
            Light Blue: css/blue.css
            All Black: css/black.css
          default: All Black

      where "All Black" is the label of the style, and css/black.css is the
      location of the stylesheet file, relative to theme's root. A line with
      Default is optional - it is for new site visitors. If it is not set then
      none of alternatives will be visible until user explicitly clicks one.

      NOTE: Do not specify in this section any of other existing CSS loaded by
      Drupal core, modules or your theme. They are being loaded unconditionally.
      If you want to set some of your theme's stylesheets for switching then
      move them from .libraries.yml file to the "styleswitcher" section of
      .info.yml.

The next steps are for both of previous ways:

 3. In the Style Switcher admin section you can see tabs for each of site's
    enabled themes. There you can reorganize how style links will be displayed
    to visitors: you can reorder them, enable/disable some of them and choose
    another default style for each theme. This means that each of your site's
    themes can have its own set of styles for switching.

    Note that styles added in admin form will be available for all themes, but
    those provided in theme's .info.yml will only be available for that theme
    and its sub-themes, but not for other themes.

 4. Finally, go to Administration » Structure » Block layout, click "Place
    block", find "Style Switcher" block, place it wherever you want and press
    "Save". You can also configure the block as you like.

    If you want the style switching functionality in multiple themes then you
    need to place the block in them too.

That's it. The block will present links for each of the configured styles to the
site visitors.

--------------------------------------------------------------------------------
AUTHORS & MAINTAINERS
--------------------------------------------------------------------------------

 * Mike Shiyan [https://www.drupal.org/u/pingwin4eg]
 * Alan Burke [https://www.drupal.org/u/alanburke]
 * Roger López [https://www.drupal.org/u/zroger]
 * Kristjan Jansen [https://www.drupal.org/u/kika]
