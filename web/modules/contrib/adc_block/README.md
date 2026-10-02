# Analog Digital Clock (ADC Block)

Provides configurable analog and digital clock blocks with multiple
layout options, timezone support, and customizable styling.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Block Types](#block-types)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)
- [Development](#development)
- [Maintainers](#maintainers)

## Features

- **Analog Clock Block**: Canvas-based analog clock with customizable
hands, colors, and layouts
- **Digital Clock Block**: Real-time digital clock with date display
options
- **SVG Clock Variants**: Dynamic SVG-based clock implementations
- **Multiple Pre-built Layouts**: 7 pre-configured layout options for
quick setup
- **Custom Styling**: Full control over colors, fonts, sizes, and visual
effects
- **Timezone Support**: Display time in any timezone or use system/local
timezone
- **Date Format Options**: Multiple date format options including custom
formats
- **Real-time Updates**: JavaScript-powered live clock updates
- **Responsive Design**: Works seamlessly across different screen sizes

## Requirements

This module requires:

- Drupal core: ^10 || ^11
- Block module (core)

## Installation

### Using Composer (Recommended)

```bash
composer require drupal/adc_block
drush en adc_block
```

### Manual Installation

1. Download and extract the module to `/modules/contrib/adc_block`
2. Enable the module:
   ```bash
   drush en adc_block
   ```
   Or enable via the Drupal UI at: Admin > Extend

## Configuration

### Placing a Block

1. Navigate to **Structure** > **Block layout** (`/admin/structure/block`)
2. Click **Place block** in your desired region
3. Search for "Analog Clock" or "Digital Clock"
4. Click **Place block**
5. Configure the block settings (see below)
6. Click **Save block**

### Block Configuration Options

#### Regional Settings

- **Timezone**: 
  - System Timezone: Uses the site's default timezone
  - Local Timezone: Uses the visitor's browser timezone
  - Specific Timezone: Choose from standard timezone options (e.g.,
  America/New_York, Europe/London)

#### Analog Clock Layout Settings

**Pre-built Layouts**:
- Layout 1: Dark theme with orange second hand
- Layout 2: Transparent with shadow effect
- Layout 3: Blue background with white hands
- Layout 4-7: Various color combinations
- Custom: Full customization options

**Custom Layout Options**:
- Fill Color & Transparency
- Border Color & Width
- Font Color & Weight
- Hand Colors (Hour, Minute, Second)
- Hand Lengths & Widths
- Tick Marks (Major & Minor)
- Shadow Effects
- Font Size

#### Digital Clock Layout Settings

- **Show Date**: Toggle date display
- **Date Format**: 
  - Short, Medium, Long
  - HTML formats (date, datetime, month, time, etc.)
  - Custom format (using PHP date format strings)
- **Container Background Color**
- **Box Shadow Effects**
- **Time & Date Colors**
- **Font Sizes**
- **Text Shadow Effects**

#### Text Content Settings

- **Heading**: Optional heading text above the clock
- **Footer**: Optional footer text below the clock (Analog Clock only)
- **Description**: Optional description text (Digital Clock only)

## Usage

### Basic Example

After installation, place the Analog Clock block:

```php
// Programmatic block placement example
$block = \Drupal\block\Entity\Block::create([
  'id' => 'analog_clock_sidebar',
  'plugin' => 'adc_block_block',
  'region' => 'sidebar_first',
  'theme' => 'your_theme',
  'settings' => [
    'timezone' => 'America/New_York',
    'layout' => 'layout1',
    'heading' => 'Current Time',
  ],
]);
$block->save();
```

### Theming

Override the default templates by copying them to your theme:

```
yourtheme/templates/analog-clock.html.twig
yourtheme/templates/digital-clock.html.twig
yourtheme/templates/svg-clock-analog-dynamic.html.twig
yourtheme/templates/svg-clock-digital-dynamic.html.twig
```

### Custom CSS

Override default styles in your theme:

```css
/* Override analog clock styles */
.adc_block-analog-container {
  padding: 20px;
  background: #f5f5f5;
}

/* Override digital clock styles */
#adc_block-screen {
  border-radius: 10px;
}
```

## Block Types

### 1. Analog Clock Block
**Plugin ID**: `adc_block_block`

Canvas-based analog clock with rotating hands. Ideal for traditional
clock displays.

### 2. Digital Clock Block
**Plugin ID**: `adc_block_digital_block`

Digital time display with optional date. Perfect for modern interfaces.

### 3. Analog Clock SVG Block
**Plugin ID**: `adc_block_svg_block`

SVG-based analog clock for scalable, high-quality displays.

### 4. Digital Clock SVG Block
**Plugin ID**: `adc_block_digital_svg_block`

SVG-based digital clock implementation.

## Troubleshooting

### Clock Not Displaying

1. Clear all caches: `drush cr`
2. Verify JavaScript is enabled in your browser
3. Check browser console for JavaScript errors
4. Ensure the block is placed in a visible region

### Time Not Updating

1. Clear browser cache
2. Check that JavaScript libraries are loading
3. Verify no JavaScript conflicts with other modules

### Timezone Issues

1. Verify site timezone: **Configuration** > **Regional and language**
> **Regional settings**
2. Check PHP timezone settings: `php -i | grep timezone`
3. Use browser's local timezone option if system timezone is incorrect

### Styling Issues

1. Clear theme cache: `drush cr`
2. Check for CSS conflicts in browser developer tools
3. Verify library attachments are loading

## API Documentation

### Theme Hook Variables

#### analog_clock
- `data`: JSON-encoded configuration data
- `content`: Array containing:
  - `heading`: Optional heading text
  - `footer`: Optional footer text

#### digital_clock
- `data`: Array containing all configuration values (colors, fonts,
timezone, etc.)

### Alter Hooks

```php
/**
 * Implements hook_adc_block_layout_options_alter().
 */
function mymodule_adc_block_layout_options_alter(&$layouts) {
  // Add custom layout option
  $layouts['my_custom_layout'] = t('My Custom Layout');
}
```

## Performance Considerations

- **Cache Disabled**: Clock blocks have cache disabled (`getCacheMaxAge()
returns 0`) to ensure real-time updates
- **JavaScript Updates**: Clocks update via JavaScript every second;
minimal server load
- **Asset Aggregation**: Enable CSS/JS aggregation for production:
**Configuration** > **Performance**

## Accessibility

- Clocks provide visual time display
- Consider adding ARIA labels for screen readers:
  ```twig
  <div aria-label="Current time: {{ time }}">
  ```

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Internet Explorer 11+ (with polyfills)
- Mobile browsers (iOS Safari, Chrome Mobile)

Maintainers
-----------

*   **Sujan Shrestha** - [sujan-shrestha](https://www.drupal.org/u/sujan-shrestha)


## Support

- Issue queue: [https://www.drupal.org/project/issues/adc_block](https://www.drupal.org/project/issues/adc_block)
