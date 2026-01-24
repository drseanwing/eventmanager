# Conference Starter Theme

A modern, professional WordPress theme designed for conferences, events, and professional gatherings. Includes seamless integration with the Event Management System (EMS) plugin.

## Version
- **Theme Version:** 1.1.0
- **Requires WordPress:** 5.9+
- **Tested up to:** 6.4
- **Requires PHP:** 7.4+
- **License:** GPL v2 or later

## Features

### Core Theme Features
- **Modern Design System**: CSS custom properties for consistent styling
- **Responsive Layout**: Mobile-first design with tablet and desktop breakpoints
- **Custom Logo Support**: Upload your organization's logo via Customizer
- **Navigation Menus**: Primary and footer menu locations
- **Widget Areas**: Sidebar and multiple footer widget areas
- **Customizer Options**: Extensive theme customization
- **Accessibility Ready**: Skip links, screen reader text, and semantic markup

### Design Tokens
The theme uses CSS custom properties for all design values:

```css
/* Typography */
--font-body: 'Work Sans', sans-serif
--font-heading: 'Poppins', sans-serif

/* Colors */
--color-primary: #222222
--color-accent: #1EC6B6
--color-accent-blue: #007bff

/* Spacing */
--spacing-xs: 5px
--spacing-sm: 10px
--spacing-md: 15px
--spacing-lg: 20px
--spacing-xl: 30px
--spacing-xxl: 40px

/* Components */
--border-radius: 4px
--box-shadow: 0 2px 5px rgba(0,0,0,0.08)
--transition-base: 0.3s ease
```

## EMS Plugin Integration

When the Event Management System plugin is active, the theme provides:

### Enhanced Templates
- **Single Event Template** (`/ems/single-event.php`): Full-width event pages with featured image header, gradient overlays, and automatic section integration
- **Event Archive Template** (`/ems/archive-event.php`): Grid layout with filtering, search, and date badges

### Gutenberg Blocks
Eight custom blocks registered in the EMS Blocks category:

| Block | Description | Key Settings |
|-------|-------------|--------------|
| `ems/event-list` | Display event grid | Limit (1-24), Show Past, Columns (1-4) |
| `ems/event-schedule` | Event schedule display | Event ID, View (list/grid/timeline), Group By, Filters |
| `ems/registration-form` | Event registration | Event ID (auto-detect) |
| `ems/abstract-submission` | Abstract submission form | Event ID (optional) |
| `ems/my-schedule` | User's registered sessions | Event ID (optional) |
| `ems/my-registrations` | User's event registrations | - |
| `ems/presenter-dashboard` | Abstract management | - |
| `ems/upcoming-events` | Compact upcoming events | Limit (1-10) |

### Theme Integration CSS
Additional styling in `/css/ems-integration.css` provides:
- Enhanced single event headers with background images
- Session card color coding by type
- Registration/abstract form styling
- Speaker grid integration
- Sponsor display effects
- Dashboard styling
- Countdown timer widget
- Print styles

### Body Classes
The theme automatically adds context-aware body classes:
- `ems-active` - Plugin is active
- `ems-single-event` - Viewing single event
- `ems-event-archive` - Viewing event archive
- `ems-schedule-page` - Page contains schedule shortcode
- `ems-registration-page` - Page contains registration shortcode

## File Structure

```
theme-conference/
├── css/
│   └── ems-integration.css    # EMS-specific enhancements
├── ems/
│   ├── single-event.php       # Single event template
│   └── archive-event.php      # Event archive template
├── inc/
│   └── ...                    # Theme includes
├── js/
│   ├── ems-blocks.js          # Gutenberg block definitions
│   ├── main.js                # Theme JavaScript
│   └── customizer.js          # Customizer preview JS
├── languages/
│   └── ...                    # Translation files
├── template-parts/
│   └── ...                    # Reusable template parts
├── functions.php              # Theme functions + EMS integration
├── style.css                  # Main stylesheet + design tokens
├── header.php
├── footer.php
├── index.php
├── single.php
├── page.php
├── archive.php
├── search.php
├── 404.php
├── sidebar.php
├── comments.php
└── searchform.php
```

## Installation

1. Upload the `theme-conference` folder to `/wp-content/themes/`
2. Activate the theme through **Appearance > Themes**
3. (Optional) Install and activate the Event Management System plugin for full functionality
4. Configure theme options in **Appearance > Customize**

## Requirements

- WordPress 5.9 or higher
- PHP 7.4 or higher
- For full functionality: Event Management System plugin v1.2.0+

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Customization

### Overriding Styles
Add custom CSS via **Appearance > Customize > Additional CSS** or create a child theme.

### Overriding EMS Templates
Copy templates from `/ems/` to your child theme's `/ems/` directory to customize.

### Modifying Design Tokens
Override CSS custom properties in your child theme's stylesheet:

```css
:root {
    --color-accent: #your-brand-color;
    --font-heading: 'Your Font', sans-serif;
}
```

## Changelog

### 1.1.0
- Added comprehensive EMS plugin integration
- Created EMS-specific templates (single-event, archive-event)
- Added 8 Gutenberg blocks for EMS functionality
- Enhanced styling with design token inheritance
- Added print styles for schedules
- Improved responsive design

### 1.0.0
- Initial release

## Credits

- **Fonts**: [Work Sans](https://fonts.google.com/specimen/Work+Sans), [Poppins](https://fonts.google.com/specimen/Poppins) (Google Fonts)
- **Icons**: WordPress Dashicons

## Support

For theme support, please contact the theme developer.

## License

This theme is licensed under the GPL v2 or later.
