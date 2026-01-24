# Conference Starter Theme

A professional WordPress theme designed for conferences, medical education events, and professional gatherings. Built with modern standards, accessibility in mind, and seamless integration with the Event Management System (EMS) plugin.

## Version

**2.0.0** - Complete rebuild with modular architecture

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Modern browser (Chrome, Firefox, Safari, Edge)

## Features

### Design System
- **CSS Custom Properties**: Comprehensive design tokens for colors, typography, spacing, and shadows
- **Responsive**: Mobile-first approach with breakpoints at 640px, 768px, 1024px, 1280px, and 1536px
- **Dark Mode Ready**: CSS custom properties make theme variations simple to implement

### Components
- Hero sections with countdown timers
- Speaker cards and grids
- Schedule displays with tabs
- Pricing tables
- Testimonials
- Call-to-action blocks
- Accordion/FAQ sections
- Registration forms (via EMS plugin)

### Accessibility
- Skip navigation links
- ARIA labels and roles
- Keyboard navigation support
- Focus state management
- Screen reader optimized
- Color contrast compliance

### Performance
- No jQuery dependency (vanilla JavaScript)
- Lazy loading for images
- RequestAnimationFrame for smooth animations
- IntersectionObserver for scroll effects
- Minimal CSS specificity

## Installation

1. Download the theme ZIP file
2. Go to WordPress Admin → Appearance → Themes
3. Click "Add New" → "Upload Theme"
4. Select the ZIP file and click "Install Now"
5. Activate the theme

## Configuration

### Customizer Settings

Access via **Appearance → Customize**:

#### Header
- **CTA Button Text**: Text displayed on the header call-to-action button
- **CTA Button URL**: Link destination for the CTA button

#### Event Details
- **Event Date**: Used for countdown timer (format: YYYY-MM-DD)
- **Venue Name**: Display name for the event venue
- **Location**: City/region for the event

#### Social Media
Links for: Twitter/X, LinkedIn, Facebook, Instagram, YouTube

#### Footer
- **Footer Description**: Brief description in the footer brand column
- **Copyright Text**: Copyright notice (use `{{year}}` for dynamic year)

#### Colors
- **Primary Color**: Main brand color (default: #1a365d)
- **Secondary Color**: Secondary accent (default: #0d9488)
- **Accent Color**: Highlight color (default: #ea580c)

### Menus

Register menus at **Appearance → Menus**:

- **Primary Menu**: Main navigation in the header
- **Footer Menu**: Links in the footer
- **Footer Legal Menu**: Legal/policy links
- **Social Menu**: Social media icon links

### Widget Areas

Configure at **Appearance → Widgets**:

- **Sidebar**: Standard blog sidebar
- **Footer Column 1-3**: Three-column footer layout
- **Homepage Before Content**: Above main homepage content
- **Homepage After Content**: Below main homepage content

## Page Templates

### Homepage Template
Full-featured homepage with:
- Hero section with countdown
- Features grid
- Speaker showcase
- Schedule preview
- Sponsors section
- Call-to-action

**Usage**: Create a page, select "Homepage" template, set as static front page in Settings → Reading.

### Full Width Template
Edge-to-edge content without sidebar.

**Usage**: Select "Full Width" template when editing any page.

## EMS Plugin Integration

This theme is designed to work seamlessly with the Event Management System (EMS) plugin:

### Supported Features
- Registration forms with styled inputs
- Schedule builder displays
- Abstract submission forms
- Session management
- Sponsor portals

### Styling
EMS-specific styles are in `assets/css/ems-integration.css` and loaded automatically when the plugin is active.

### Template Overrides
The theme respects EMS template hierarchy while providing consistent styling.

## Development

### File Structure

```
conference-starter/
├── assets/
│   ├── css/
│   │   ├── editor-style.css    # Gutenberg editor styles
│   │   └── ems-integration.css # EMS plugin styles
│   ├── js/
│   │   ├── main.js             # Frontend JavaScript
│   │   └── customizer.js       # Customizer preview
│   └── images/
├── inc/
│   ├── customizer.php          # Customizer settings
│   ├── template-tags.php       # Template helper functions
│   ├── template-functions.php  # Theme-specific functions
│   └── ems-integration.php     # EMS plugin integration
├── template-parts/
│   ├── content.php             # Standard post content
│   ├── content-card.php        # Card layout for archives
│   ├── content-none.php        # No results template
│   ├── content-page.php        # Page content
│   └── content-search.php      # Search result item
├── templates/
│   ├── homepage.php            # Homepage template
│   └── full-width.php          # Full-width template
├── functions.php               # Theme setup
├── header.php                  # Site header
├── footer.php                  # Site footer
├── index.php                   # Main template
├── single.php                  # Single post
├── page.php                    # Single page
├── archive.php                 # Archive pages
├── search.php                  # Search results
├── 404.php                     # Error page
├── sidebar.php                 # Sidebar
├── comments.php                # Comments template
├── searchform.php              # Search form
└── style.css                   # Theme info + main styles
```

### CSS Architecture

The theme uses a component-based CSS architecture:

1. **Design Tokens**: CSS custom properties in `:root`
2. **Base Styles**: Typography, links, forms
3. **Layout**: Container, grid, flex utilities
4. **Components**: Buttons, cards, forms, etc.
5. **Templates**: Page-specific styles
6. **Utilities**: Helper classes

### JavaScript Modules

`main.js` contains modular components:

- `MobileNav`: Mobile menu toggle and overlay
- `StickyHeader`: Scroll-aware sticky header
- `CountdownTimer`: Event countdown with i18n
- `SmoothScroll`: Smooth anchor scrolling
- `ScrollReveal`: Scroll-triggered animations
- `ScheduleTabs`: Tab navigation for schedules
- `Accordion`: Expandable content sections
- `FormValidation`: Client-side form validation
- `LazyLoad`: Image lazy loading

### Logging

Development logging is available:

```php
// PHP logging (to debug.log or custom file)
conference_starter_log('Message', 'info');

// JavaScript logging (console)
Logger.log('Message', data);
```

Set `WP_DEBUG` and `WP_DEBUG_LOG` to `true` in `wp-config.php` for PHP logging.

## Browser Support

- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)

## Credits

- **Design Inspiration**: Modern conference and event websites
- **Icons**: Custom SVG icon system
- **Fonts**: Inter, Plus Jakarta Sans, JetBrains Mono (Google Fonts)

## License

GNU General Public License v2 or later
https://www.gnu.org/licenses/gpl-2.0.html

## Changelog

### 2.0.0 (2026-01-20)
- Complete theme rebuild
- New design token system
- Mobile-first responsive approach
- Comprehensive EMS plugin integration
- Accessibility improvements
- Performance optimizations
- Modular JavaScript architecture
- Gutenberg editor support

### 1.0.0 (Initial)
- Original release

## Support

For issues and feature requests, please contact the theme developer.

---

Built for the Royal Brisbane & Women's Hospital Resuscitation Education Initiative (REdI) program.
