(function ($, Drupal) {
  Drupal.behaviors.time_clock = {
    attach: function (context, settings) {
      let digiClock = document.querySelector("#dynamic_time");
      setInterval(
        function () {
          digiClock.innerText = digitalClock(settings);
        }, 1000
      );
    }
  };

  function digitalClock(settings) {
    let timezone = settings.timezone;
    let timeformat = (settings.timeformat == 1) ? false : true;
    let today = new Date();
    today = today.toLocaleString("en-US", {timeZone:timezone, hour12:timeformat});
    today = today.split(',');
    return today[1].trim();
  }
})(jQuery, Drupal);
