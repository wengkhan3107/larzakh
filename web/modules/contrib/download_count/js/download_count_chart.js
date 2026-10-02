/**
 * @file
 */

(function ($) {
  Drupal.behaviors.download_count_sparklines = {
    attach: function (context, settings) {
      document.querySelectorAll('.peity-data').forEach(e => peity(e, "line", {
        width: 900,
        height: 250
      }))
    }
  }
})(jQuery);
