(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.analog_behavior = {
    attach(context) {
      // Helper function defined inside to maintain scope
      function htAnalogClockStatic() {}
      htAnalogClockStatic.presetDefault = {
        hasShadow: 'TRUE',
        shadowColor: '#000',
        shadowBlur: 10,
        drawSecondHand: 'TRUE',
        drawMajorTicks: 'TRUE',
        drawMinorTicks: 'TRUE',
        drawBorder: 'TRUE',
        drawFill: 'TRUE',
        drawTexts: 'TRUE',
        drawPin: 'TRUE',
        majorTicksColor: '#f88',
        minorTicksColor: '#fa0',
        majorTicksLength: 10.0,
        minorTicksLength: 7.0,
        majorTicksWidth: 0.005,
        minorTicksWidth: 0.0025,
        fillColor: '#333',
        pinColor: '#f88',
        pinRadius: 5.0,
        borderColor: '#000',
        borderWidth: 2.0,
        secondHandColor: '#f00',
        minuteHandColor: '#fff',
        hourHandColor: '#fff',
        fontColor: '#fff',
        fontName: 'Tahoma',
        fontSize: 10.0,
        fontWeight: 'normal',
        secondHandLength: 90.0,
        minuteHandLength: 70.0,
        hourHandLength: 50.0,
        secondHandWidth: 1.0,
        minuteHandWidth: 2.0,
        hourHandWidth: 3.0,
      };

      const core = (id, preset, options) => {
        const adcBlockCanvas = $(id)[0];
        const ctx = adcBlockCanvas.getContext('2d');
        const adcBlockBound = adcBlockCanvas.height;
        let adcBlockSafePad = 0;

        if (preset.hasShadow === 'TRUE') {
          adcBlockSafePad = 4;
        }

        const adcBlockRadius = adcBlockCanvas.height / 2 - adcBlockSafePad;
        const adcBlockSecondStep = (2 * Math.PI) / 60;
        const adcBlockHourStep = (2 * Math.PI) / 12;

        const adcBlockP2v = (value) => (value / 100.0) * adcBlockRadius;

        const adcBlockDrawMajorLines = () => {
          ctx.lineWidth = adcBlockP2v(preset.majorTicksLength);
          ctx.strokeStyle = preset.majorTicksColor;
          for (let i = 1; i <= 12; i++) {
            ctx.beginPath();
            ctx.arc(
              adcBlockRadius + adcBlockSafePad,
              adcBlockRadius + adcBlockSafePad,
              adcBlockRadius - ctx.lineWidth / 1.9,
              i * adcBlockHourStep - adcBlockP2v(preset.majorTicksWidth) / 2,
              i * adcBlockHourStep + adcBlockP2v(preset.majorTicksWidth) / 2,
            );
            ctx.stroke();
          }
        };

        const adcBlockDrawMinorLines = () => {
          ctx.strokeStyle = preset.minorTicksColor;
          const secHandLength = 96 * 2;
          for (let i = 0; i < 60; i++) {
            const angle = ((i - 3) * (Math.PI * 2)) / 60;
            ctx.lineWidth = adcBlockP2v(preset.minorTicksWidth);
            ctx.beginPath();
            const x1 =
              adcBlockCanvas.width / 2 + Math.cos(angle) * secHandLength;
            const y1 =
              adcBlockCanvas.height / 2 + Math.sin(angle) * secHandLength;
            const x2 =
              adcBlockCanvas.width / 2 +
              Math.cos(angle) * (secHandLength - secHandLength / 30);
            const y2 =
              adcBlockCanvas.height / 2 +
              Math.sin(angle) * (secHandLength - secHandLength / 30);
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
          }
        };

        const drawBorder = () => {
          ctx.strokeStyle = preset.borderColor;
          ctx.lineWidth = adcBlockP2v(preset.borderWidth);
          ctx.beginPath();
          ctx.arc(
            adcBlockRadius + adcBlockSafePad,
            adcBlockRadius + adcBlockSafePad,
            adcBlockRadius - ctx.lineWidth / 2,
            0,
            2 * Math.PI,
          );
          ctx.stroke();
        };

        const drawFill = () => {
          ctx.fillStyle = preset.fillColor;
          ctx.lineWidth = adcBlockP2v(preset.borderWidth);
          ctx.beginPath();
          ctx.arc(
            adcBlockRadius + adcBlockSafePad,
            adcBlockRadius + adcBlockSafePad,
            adcBlockRadius - ctx.lineWidth,
            0.0,
            2 * Math.PI,
          );
          ctx.fill();
        };

        const drawHandle = (angle, lengthPercent, widthPercent, color) => {
          const x = Math.cos(angle - Math.PI / 2) * adcBlockP2v(lengthPercent);
          const y = Math.sin(angle - Math.PI / 2) * adcBlockP2v(lengthPercent);
          ctx.lineWidth = adcBlockP2v(widthPercent);
          ctx.strokeStyle = color;
          ctx.beginPath();
          ctx.moveTo(
            adcBlockRadius + adcBlockSafePad,
            adcBlockRadius + adcBlockSafePad,
          );
          ctx.lineTo(
            adcBlockRadius + adcBlockSafePad + x,
            adcBlockRadius + adcBlockSafePad + y,
          );
          ctx.stroke();
        };

        const drawTexts = () => {
          const fontWeight = preset.fontWeight || 'normal';
          for (let i = 1; i <= 12; i++) {
            const angle = i * adcBlockHourStep;
            const x = Math.cos(angle - Math.PI / 2) * adcBlockP2v(80.0);
            const y = Math.sin(angle - Math.PI / 2) * adcBlockP2v(80.0);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = `${fontWeight} ${adcBlockP2v(preset.fontSize).toString()}px ${preset.fontName}`;
            ctx.fillStyle = preset.fontColor;
            ctx.beginPath();
            ctx.fillText(
              i.toString(),
              adcBlockRadius + adcBlockSafePad + x,
              adcBlockRadius + adcBlockSafePad + y,
            );
            ctx.stroke();
          }
        };

        const drawPin = () => {
          ctx.fillStyle = preset.pinColor;
          ctx.beginPath();
          ctx.arc(
            adcBlockRadius + adcBlockSafePad,
            adcBlockRadius + adcBlockSafePad,
            adcBlockP2v(preset.pinRadius),
            0.0,
            2 * Math.PI,
          );
          ctx.fill();
        };

        const changeTimezone = (date, ianatz) => {
          const invdate = new Date(
            date.toLocaleString('en-US', { timeZone: ianatz }),
          );
          const diff = date.getTime() - invdate.getTime();
          return new Date(date.getTime() - diff);
        };

        const draw = () => {
          ctx.clearRect(0.0, 0.0, adcBlockBound, adcBlockBound);
          ctx.lineCap = 'butt';
          if (preset.drawFill === 'TRUE') drawFill();
          if (preset.drawMinorTicks === 'TRUE') adcBlockDrawMinorLines();
          if (preset.drawMajorTicks === 'TRUE') adcBlockDrawMajorLines();
          if (preset.drawBorder === 'TRUE') drawBorder();
          if (preset.drawTexts === 'TRUE') drawTexts();

          let date = new Date();
          if (options.timezone && options.timezone !== 'NULL') {
            date = changeTimezone(date, options.timezone);
          }

          const s = date.getSeconds();
          let m = date.getMinutes();
          let h = date.getHours();
          m += s / 60.0;
          h += m / 60.0;

          ctx.lineCap = 'round';
          drawHandle(
            h * adcBlockHourStep,
            preset.hourHandLength,
            preset.hourHandWidth,
            preset.hourHandColor,
          );
          drawHandle(
            m * adcBlockSecondStep,
            preset.minuteHandLength,
            preset.minuteHandWidth,
            preset.minuteHandColor,
          );

          if (preset.drawSecondHand === 'TRUE') {
            drawHandle(
              s * adcBlockSecondStep,
              preset.secondHandLength,
              preset.secondHandWidth,
              preset.secondHandColor,
            );
          }
          if (preset.drawPin === 'TRUE') drawPin();

          window.requestAnimationFrame(draw);
        };

        const adcBlockInitialize = () => {
          const $canvas = $(adcBlockCanvas);
          const height = $canvas.height();
          adcBlockCanvas.style.maxWidth = '100%';
          adcBlockCanvas.style.width = `${height}px`;
          adcBlockCanvas.width = height;

          if (preset.hasShadow === 'TRUE') {
            ctx.shadowOffsetX = 0.0;
            ctx.shadowOffsetY = 0.0;
            ctx.shadowBlur = preset.shadowBlur;
            ctx.shadowColor = preset.shadowColor;
          }
          draw();
        };

        adcBlockInitialize();
      };

      // Define jQuery plugin
      $.fn.htAnalogClock = function (preset, options) {
        return this.each(function () {
          const _preset = $.extend(
            {},
            htAnalogClockStatic.presetDefault,
            preset || {},
          );
          const _options = $.extend({ timezone: 'NULL' }, options || {});
          core(this, _preset, _options);
        });
      };

      // Execution logic replacing .ready()
      $(once('adc-analog-clock', '.adc_block-analog', context)).each(
        function () {
          const $this = $(this);
          if ($this[0].hasAttribute('data-config')) {
            const config = JSON.parse($this.attr('data-config'));
            $this.htAnalogClock(config, { timezone: config.timezone });
          }
        },
      );
    },
  };
})(jQuery, Drupal, drupalSettings);
