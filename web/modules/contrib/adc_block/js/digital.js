(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.digital = {
    attach(context) {
      const changeTimezone = function (date, ianatz) {
        const invdate = new Date(
          date.toLocaleString('en-US', {
            timeZone: ianatz,
          }),
        );
        const diff = date.getTime() - invdate.getTime();
        return new Date(date.getTime() - diff);
      };

      setInterval(() => {
        // Changed .filter() to .each() to resolve array-callback-return
        $('.adc_block-digitaltime', context).each(function () {
          const $this = $(this);
          const timezone = $this.attr('data-timezone');
          let time = new Date();

          if (this.hasAttribute('data-timezone')) {
            time = changeTimezone(time, timezone);
          }

          let hours = time.getHours();
          let minutes = time.getMinutes();
          let seconds = time.getSeconds();
          let amPm = '';

          if (hours > 12) {
            hours -= 12;
            amPm = 'PM';
          } else if (hours === 0) {
            hours = 12;
            amPm = 'AM';
          } else {
            amPm = 'AM';
          }

          hours = hours >= 10 ? hours : `0${hours}`;
          minutes = minutes >= 10 ? minutes : `0${minutes}`;
          seconds = seconds >= 10 ? seconds : `0${seconds}`;

          // Changed clock_data to clockData to resolve camelcase
          const clockData = `${hours}:${minutes}:${seconds} ${amPm}`;
          $this.html(clockData);
        });
      }, 1000);
    },
  };
})(jQuery, Drupal, drupalSettings);
