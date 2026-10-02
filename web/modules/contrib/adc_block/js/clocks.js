/**
 * @file
 * JavaScript for Analog Digital Clock module.
 */

(function (Drupal, once) {
  const romanNumerals = [
    'XII',
    'I',
    'II',
    'III',
    'IV',
    'V',
    'VI',
    'VII',
    'VIII',
    'IX',
    'X',
    'XI',
  ];

  /**
   * Get timezone offset for a specific timezone.
   */
  function getTimeInTimezone(config) {
    let now = new Date();

    if (config.timezone === 'custom' && config.custom_timezone) {
      // Create date in specific timezone
      const dateString = now.toLocaleString('en-US', {
        timeZone: config.custom_timezone,
      });
      now = new Date(dateString);
    } else if (config.timezone === 'user') {
      // Use browser's local timezone (default behavior)
      // No modification needed
    }
    // 'default' uses site timezone which is handled server-side

    return now;
  }

  /**
   * Create SVG analog clock with full configuration.
   */
  function createAnalogClock(wrapper, config) {
    const svg = wrapper.querySelector('svg');
    if (!svg) return;

    const center = 100;
    const radius = 90;
    const svgId = wrapper.id;
    let svgContent = '<defs>';

    // Background gradient if needed
    if (config.bg_type === 'gradient') {
      const gradId = `bg-grad-${svgId}`;
      if (config.bg_gradient_type === 'radial') {
        svgContent += `
          <radialGradient id="${gradId}">
            <stop offset="0%" stop-color="${config.bg_gradient_start}"/>
            <stop offset="100%" stop-color="${config.bg_gradient_end}"/>
          </radialGradient>
        `;
      } else {
        svgContent += `
          <linearGradient id="${gradId}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="${config.bg_gradient_start}"/>
            <stop offset="100%" stop-color="${config.bg_gradient_end}"/>
          </linearGradient>
        `;
      }
    }

    // Rainbow border if needed
    if (config.border_type === 'rainbow') {
      svgContent += `
        <linearGradient id="rainbow-${svgId}" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="red"/>
          <stop offset="16.67%" stop-color="orange"/>
          <stop offset="33.33%" stop-color="yellow"/>
          <stop offset="50%" stop-color="green"/>
          <stop offset="66.67%" stop-color="blue"/>
          <stop offset="83.33%" stop-color="indigo"/>
          <stop offset="100%" stop-color="violet"/>
        </linearGradient>
      `;
    }

    // Glow filter if enabled
    if (config.enable_glow) {
      const filterId = `glow-${svgId}`;
      const intensity = config.glow_intensity || 3;
      svgContent += `
        <filter id="${filterId}" x="-50%" y="-50%" width="200%" height="200%">
          <feGaussianBlur stdDeviation="${intensity}" result="coloredBlur"/>
          <feMerge>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
      `;
    }

    // Drop shadow filter if enabled
    if (config.enable_shadow) {
      const shadowId = `shadow-${svgId}`;
      const opacity = (config.shadow_opacity || 30) / 100;
      svgContent += `
        <filter id="${shadowId}" x="-50%" y="-50%" width="200%" height="200%">
          <feGaussianBlur in="SourceAlpha" stdDeviation="${config.shadow_blur || 10}"/>
          <feOffset dx="0" dy="4" result="offsetblur"/>
          <feComponentTransfer>
            <feFuncA type="linear" slope="${opacity}"/>
          </feComponentTransfer>
          <feMerge>
            <feMergeNode/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
      `;
    }

    svgContent += '</defs>';

    // Background circle
    const bgFill =
      config.bg_type === 'gradient'
        ? `url(#bg-grad-${svgId})`
        : config.bg_color;
    const borderStroke =
      config.border_type === 'rainbow'
        ? `url(#rainbow-${svgId})`
        : config.border_color;
    const shadowFilter = config.enable_shadow
      ? `filter="url(#shadow-${svgId})"`
      : '';

    svgContent += `
      <circle cx="${center}" cy="${center}" r="${radius}" 
        fill="${bgFill}" 
        stroke="${borderStroke}" 
        stroke-width="${config.clock_border_width || 4}"
        ${shadowFilter}
      />
    `;

    // Minute and hour markers
    const glowFilter = config.enable_glow ? `filter="url(#glow-${svgId})"` : '';

    for (let i = 0; i < 60; i++) {
      const isHourMarker = i % 5 === 0;

      if (
        (isHourMarker && config.show_hour_markers) ||
        (!isHourMarker && config.show_minute_markers)
      ) {
        const length = isHourMarker
          ? config.hour_marker_length || 15
          : config.minute_marker_length || 8;
        const width = isHourMarker
          ? config.hour_marker_width || 3
          : config.minute_marker_width || 1;
        const color = isHourMarker
          ? config.hour_marker_color
          : config.minute_marker_color;

        const angle = ((i * 6 - 90) * Math.PI) / 180;
        const innerRadius = radius - config.clock_border_width - length - 2;
        const outerRadius = radius - config.clock_border_width - 2;

        const x1 = center + Math.cos(angle) * innerRadius;
        const y1 = center + Math.sin(angle) * innerRadius;
        const x2 = center + Math.cos(angle) * outerRadius;
        const y2 = center + Math.sin(angle) * outerRadius;

        svgContent += `
          <line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" 
            stroke="${color}" 
            stroke-width="${width}"
            stroke-linecap="round"
            ${glowFilter}
          />
        `;
      }
    }

    // Numbers
    if (config.show_numbers) {
      const numberRadius =
        radius -
        config.clock_border_width -
        (config.hour_marker_length || 15) -
        15;

      for (let i = 1; i <= 12; i++) {
        const angle = ((i * 30 - 90) * Math.PI) / 180;
        const x = center + Math.cos(angle) * numberRadius;
        const y = center + Math.sin(angle) * numberRadius;

        if (config.number_type === 'dots') {
          svgContent += `
            <circle cx="${x}" cy="${y}" r="3" 
              fill="${config.number_color}"
              ${glowFilter}
            />
          `;
        } else {
          const text =
            config.number_type === 'roman' ? romanNumerals[i % 12] : i;
          const fontSize = config.number_size || 16;

          svgContent += `
            <text x="${x}" y="${y}" 
              text-anchor="middle" 
              dominant-baseline="middle"
              fill="${config.number_color}"
              font-size="${fontSize}"
              font-weight="bold"
              font-family="${config.number_type === 'roman' ? 'serif' : 'Arial, sans-serif'}"
              ${glowFilter}
            >${text}</text>
          `;
        }
      }
    }

    // Clock hands
    const availableRadius = radius - config.clock_border_width - 5;
    const hourHandLength = availableRadius * (config.hour_hand_length / 100);
    const minuteHandLength =
      availableRadius * (config.minute_hand_length / 100);
    const secondHandLength =
      availableRadius * (config.second_hand_length / 100);

    svgContent += `
      <g class="hour-hand">
        <line x1="${center}" y1="${center}" x2="${center}" y2="${center - hourHandLength}" 
          stroke="${config.hour_hand_color}" 
          stroke-width="${config.hour_hand_width || 6}"
          stroke-linecap="round"
          ${glowFilter}
        />
      </g>
      <g class="minute-hand">
        <line x1="${center}" y1="${center}" x2="${center}" y2="${center - minuteHandLength}" 
          stroke="${config.minute_hand_color}" 
          stroke-width="${config.minute_hand_width || 4}"
          stroke-linecap="round"
          ${glowFilter}
        />
      </g>
    `;

    if (config.show_second_hand) {
      svgContent += `
        <g class="second-hand">
          <line x1="${center}" y1="${center}" x2="${center}" y2="${center - secondHandLength}" 
            stroke="${config.second_hand_color}" 
            stroke-width="${config.second_hand_width || 2}"
            stroke-linecap="round"
            ${glowFilter}
          />
        </g>
      `;
    }

    // Center dot
    const centerDotSize = config.center_dot_size || 8;
    const centerDotBorder =
      config.center_dot_border_width > 0
        ? `stroke="${config.center_dot_border_color}" stroke-width="${config.center_dot_border_width}"`
        : '';

    svgContent += `
      <circle cx="${center}" cy="${center}" r="${centerDotSize}" 
        fill="${config.center_dot_color}"
        ${centerDotBorder}
        ${glowFilter}
      />
    `;

    svg.innerHTML = svgContent;
  }

  /**
   * Update analog clock hands.
   */
  function updateAnalogClock(wrapper, config) {
    const svg = wrapper.querySelector('svg');
    if (!svg) return;

    const now = getTimeInTimezone(config);
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    const hourAngle = (hours % 12) * 30 + minutes * 0.5;
    const minuteAngle = minutes * 6 + seconds * 0.1;
    const secondAngle = seconds * 6;

    const hourHand = svg.querySelector('.hour-hand');
    const minuteHand = svg.querySelector('.minute-hand');
    const secondHand = svg.querySelector('.second-hand');

    if (hourHand)
      hourHand.setAttribute('transform', `rotate(${hourAngle}, 100, 100)`);
    if (minuteHand)
      minuteHand.setAttribute('transform', `rotate(${minuteAngle}, 100, 100)`);
    if (secondHand)
      secondHand.setAttribute('transform', `rotate(${secondAngle}, 100, 100)`);
  }

  /**
   * Apply digital clock styles from configuration.
   */
  function applyDigitalClockStyles(wrapper, config) {
    const clockDiv = wrapper.querySelector('.digital-clock');
    if (!clockDiv) return;

    // Build background
    let background = config.bg_color;
    if (config.bg_type === 'gradient') {
      background = `linear-gradient(${config.bg_gradient_angle}deg, ${config.bg_gradient_start}, ${config.bg_gradient_end})`;
    }

    // Build border
    const border =
      config.border_width > 0
        ? `${config.border_width}px ${config.border_style} ${config.border_color}`
        : 'none';

    // Build padding
    const padding = `${config.padding_top}px ${config.padding_right}px ${config.padding_bottom}px ${config.padding_left}px`;

    // Build text shadow
    let textShadow = 'none';
    if (config.enable_text_shadow) {
      const shadows = [];
      const intensity = config.text_shadow_intensity || 1;
      for (let i = 0; i < intensity; i++) {
        const mult = i + 1;
        shadows.push(
          `0 0 ${config.text_shadow_blur * mult}px ${config.text_shadow_color}`,
        );
      }
      textShadow = shadows.join(', ');
    }

    // Build box shadow
    let boxShadow = 'none';
    if (config.enable_box_shadow) {
      const opacity = (config.box_shadow_opacity || 50) / 100;
      const r = parseInt(config.box_shadow_color.slice(1, 3), 16);
      const g = parseInt(config.box_shadow_color.slice(3, 5), 16);
      const b = parseInt(config.box_shadow_color.slice(5, 7), 16);
      boxShadow = `${config.box_shadow_x}px ${config.box_shadow_y}px ${config.box_shadow_blur}px ${config.box_shadow_spread}px rgba(${r},${g},${b},${opacity})`;
    }

    // Get font family
    let fontFamily = 'monospace';
    switch (config.font_family) {
      case 'sans-serif':
        fontFamily = 'Arial, Helvetica, sans-serif';
        break;
      case 'serif':
        fontFamily = 'Times, "Times New Roman", serif';
        break;
      case 'system':
        fontFamily = 'system-ui, -apple-system, sans-serif';
        break;
      default:
        fontFamily = '"Courier New", Courier, monospace';
    }

    // Apply styles to clock container
    Object.assign(clockDiv.style, {
      background,
      border,
      borderRadius: `${config.border_radius}px`,
      padding,
      boxShadow,
      textAlign: config.text_align,
      fontFamily,
      fontWeight: config.font_weight,
      textTransform: config.uppercase ? 'uppercase' : 'none',
    });

    // Apply styles to time
    const timeEl = clockDiv.querySelector('.digital-time');
    if (timeEl) {
      Object.assign(timeEl.style, {
        color: config.time_color,
        fontSize: `${config.time_font_size}px`,
        letterSpacing: `${config.letter_spacing}px`,
        textShadow,
      });
    }

    // Apply styles to date
    const dateEl = clockDiv.querySelector('.digital-date');
    if (dateEl) {
      Object.assign(dateEl.style, {
        color: config.date_color,
        fontSize: `${config.date_font_size}px`,
        letterSpacing: `${Math.max(1, config.letter_spacing - 2)}px`,
        textShadow,
        opacity: '0.9',
        marginTop: '8px',
      });
    }

    // Apply styles to day
    const dayEl = clockDiv.querySelector('.digital-day');
    if (dayEl) {
      Object.assign(dayEl.style, {
        color: config.day_color,
        fontSize: `${config.day_font_size}px`,
        letterSpacing: `${Math.max(1, config.letter_spacing - 3)}px`,
        textShadow,
        opacity: '0.8',
        marginTop: '6px',
      });
    }
  }

  /**
   * Update digital clock content.
   */
  function updateDigitalClock(wrapper, config) {
    const clockDiv = wrapper.querySelector('.digital-clock');
    if (!clockDiv) return;

    const now = getTimeInTimezone(config);
    let hours = now.getHours();
    const minutes = now.getMinutes();
    const seconds = now.getSeconds();

    // Format time
    let timeStr = '';
    if (config.time_format === '12') {
      const ampm = hours >= 12 ? ' PM' : ' AM';
      hours = hours % 12 || 12;
      const hoursStr = String(hours).padStart(2, '0');
      const minutesStr = String(minutes).padStart(2, '0');
      if (config.show_seconds) {
        const secondsStr = String(seconds).padStart(2, '0');
        timeStr = `${hoursStr}:${minutesStr}:${secondsStr}${ampm}`;
      } else {
        timeStr = `${hoursStr}:${minutesStr}${ampm}`;
      }
    } else {
      const hoursStr = String(hours).padStart(2, '0');
      const minutesStr = String(minutes).padStart(2, '0');
      if (config.show_seconds) {
        const secondsStr = String(seconds).padStart(2, '0');
        timeStr = `${hoursStr}:${minutesStr}:${secondsStr}`;
      } else {
        timeStr = `${hoursStr}:${minutesStr}`;
      }
    }

    // Update time
    const timeEl = clockDiv.querySelector('.digital-time');
    if (timeEl) {
      timeEl.textContent = timeStr;
    }

    // Update date if shown
    if (config.show_date) {
      const dateEl = clockDiv.querySelector('.digital-date');
      if (dateEl) {
        let dateStr = '';
        const months = [
          'January',
          'February',
          'March',
          'April',
          'May',
          'June',
          'July',
          'August',
          'September',
          'October',
          'November',
          'December',
        ];
        const date = now.getDate();
        const month = now.getMonth();
        const year = now.getFullYear();

        switch (config.date_format) {
          case 'short':
            dateStr = `${String(month + 1).padStart(2, '0')}/${String(date).padStart(2, '0')}/${year}`;
            break;
          case 'iso':
            dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
            break;
          case 'custom':
            // Basic custom date formatting
            dateStr = config.custom_date_format || 'F j, Y';
            dateStr = dateStr.replace('F', months[month]);
            dateStr = dateStr.replace('j', date);
            dateStr = dateStr.replace('Y', year);
            dateStr = dateStr.replace('m', String(month + 1).padStart(2, '0'));
            dateStr = dateStr.replace('d', String(date).padStart(2, '0'));
            break;
          default: // long
            dateStr = `${months[month]} ${date}, ${year}`;
        }

        dateEl.textContent = dateStr;
      }
    }

    // Update day if shown
    if (config.show_day) {
      const dayEl = clockDiv.querySelector('.digital-day');
      if (dayEl) {
        const days = [
          'Sunday',
          'Monday',
          'Tuesday',
          'Wednesday',
          'Thursday',
          'Friday',
          'Saturday',
        ];
        const dayName = days[now.getDay()];
        dayEl.textContent =
          config.day_format === 'short' ? dayName.substring(0, 3) : dayName;
      }
    }
  }

  /**
   * Initialize clocks.
   */
  Drupal.behaviors.svgClocksDynamic = {
    attach(context, settings) {
      // Initialize analog clocks
      once('analog-clock-init', '.analog-clock-wrapper', context).forEach(
        function (wrapper) {
          const configAttr = wrapper.getAttribute('data-config');
          if (configAttr) {
            try {
              const config = JSON.parse(configAttr);
              createAnalogClock(wrapper, config);
              updateAnalogClock(wrapper, config);
            } catch (e) {
              console.error('Error initializing analog clock:', e);
            }
          }
        },
      );

      // Initialize digital clocks
      once('digital-clock-init', '.digital-clock-wrapper', context).forEach(
        function (wrapper) {
          const configAttr = wrapper.getAttribute('data-config');
          if (configAttr) {
            try {
              const config = JSON.parse(configAttr);
              applyDigitalClockStyles(wrapper, config);
              updateDigitalClock(wrapper, config);
            } catch (e) {
              console.error('Error initializing digital clock:', e);
            }
          }
        },
      );

      // Update clocks every second
      if (!window.svgClocksDynamicInterval) {
        window.svgClocksDynamicInterval = setInterval(function () {
          // Update analog clocks
          document
            .querySelectorAll('.analog-clock-wrapper')
            .forEach(function (wrapper) {
              const configAttr = wrapper.getAttribute('data-config');
              if (configAttr) {
                try {
                  const config = JSON.parse(configAttr);
                  updateAnalogClock(wrapper, config);
                } catch (e) {
                  // Silent fail
                }
              }
            });

          // Update digital clocks
          document
            .querySelectorAll('.digital-clock-wrapper')
            .forEach(function (wrapper) {
              const configAttr = wrapper.getAttribute('data-config');
              if (configAttr) {
                try {
                  const config = JSON.parse(configAttr);
                  updateDigitalClock(wrapper, config);
                } catch (e) {
                  // Silent fail
                }
              }
            });
        }, 1000);
      }
    },
  };
})(Drupal, once);
