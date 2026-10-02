(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.linkTracker = {
    attach: function (context, settings) {
      // Only bind to <a> that has data-track="true"
      $('a[data-track="true"]', context).once('link-tracker').on('click', function (e) {
        var href = $(this).attr('href');
        var label = $(this).data('label') || ''; // Optional extra info

        $.ajax({
          url: Drupal.url('user-activity-logger/track-click'),
          type: 'POST',
          data: {
            href: href,
            label: label
          },
          dataType: 'json'
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
