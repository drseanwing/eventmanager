# MNResus Homepage Implementation

A professional Conference Homepage template for the MNResus (Medical Response - Resuscitation) website, designed for the REdI Program at Royal Brisbane & Women's Hospital.

## Overview

This implementation adds a custom homepage template to the Conference Starter theme with:
- Full-screen hero section with countdown timer
- Value proposition cards (Why Train with REdI?)
- EMS plugin integration for upcoming events
- About section with program overview
- Statistics bar with customizable metrics
- Program types grid
- Testimonials section
- Call-to-action section
- Enhanced footer with social links

## Installation

### 1. Copy Files to Theme Directory

Copy the following files to your `conference-starter` theme:

```
conference-starter/
├── css/
│   └── homepage.css          # Homepage-specific styles
├── js/
│   └── homepage.js           # Countdown timer & animations
├── inc/
│   └── functions-homepage.php # Template functions
├── templates/
│   └── homepage.php          # Homepage template
├── customizer.php            # Replace existing (enhanced)
└── footer.php                # Replace existing (enhanced)
```

### 2. Update functions.php

Add this line to your theme's `functions.php` after existing includes:

```php
// Homepage template functions
require_once CONFERENCE_STARTER_DIR . '/inc/functions-homepage.php';
```

### 3. Create Required Directories

If they don't exist, create:
- `/css/` directory
- `/js/` directory  
- `/inc/` directory
- `/templates/` directory

## Configuration

### Setting Up the Homepage

1. **Create a new page** in WordPress
   - Title: "Home" (or any preferred name)
   - In Page Attributes, select **Template: Conference Homepage**
   - Add a featured image (this becomes the hero background)
   - Add an excerpt (this becomes the hero subtitle)

2. **Set as front page**
   - Go to Settings → Reading
   - Select "A static page"
   - Set "Homepage" to your new page

### Customizer Settings

Navigate to **Appearance → Customize → Conference Settings**

#### Event Details
| Setting | Description |
|---------|-------------|
| Event Date | Next event date (enables countdown timer) |
| Event Venue | Venue name (e.g., "RBWH") |
| Event Location | Location text (e.g., "Herston, Brisbane") |
| CTA Button Text | Main call-to-action text |
| CTA Button URL | Link for main CTA button |

#### Brand Colors
| Setting | Default | Purpose |
|---------|---------|---------|
| Primary (Navy) | `#1a365d` | Headers, text, authority |
| Secondary (Teal) | `#0d9488` | Health associations |
| Accent (Orange) | `#ea580c` | CTAs, urgency |

#### Statistics Bar
Configure 4 statistics (value + label pairs):
- Stat 1: "2,500+" / "Healthcare professionals trained annually"
- Stat 2: "15+" / "Years of education excellence"
- Stat 3: "50+" / "Expert faculty members"
- Stat 4: "98%" / "Participant satisfaction rate"

#### Testimonials
Two testimonial slots with:
- Quote text
- Author name
- Author role/title

#### Call to Action
- CTA Headline
- CTA Subheadline  
- Newsletter signup URL (optional)

#### Footer
- Footer tagline
- Social media URLs (LinkedIn, Twitter/X, YouTube)
- Copyright text

## EMS Plugin Integration

The homepage automatically displays events from the EMS plugin using:

```php
[ems_event_list limit="3" columns="3" show_past="false"]
```

If EMS is not installed, an empty state message appears.

## Page Structure

```
┌─────────────────────────────────────────────────────────────┐
│  HERO SECTION                                                │
│  - Date badge, Title, Subtitle, Location, CTAs, Countdown   │
├─────────────────────────────────────────────────────────────┤
│  VALUE PROPS (3 cards)                                       │
│  Evidence-Based | Expert Faculty | Hands-On Simulation      │
├─────────────────────────────────────────────────────────────┤
│  UPCOMING EVENTS                                             │
│  [EMS Event Cards Grid]                                      │
├─────────────────────────────────────────────────────────────┤
│  ABOUT THE PROGRAM (2-column: text + image)                  │
├─────────────────────────────────────────────────────────────┤
│  STATISTICS BAR (4 metrics)                                  │
├─────────────────────────────────────────────────────────────┤
│  PROGRAM TYPES (4 cards)                                     │
│  Resuscitation | Deterioration | Simulation | Conferences   │
├─────────────────────────────────────────────────────────────┤
│  TESTIMONIALS (2 quotes)                                     │
├─────────────────────────────────────────────────────────────┤
│  CALL TO ACTION                                              │
│  View All Events button                                      │
├─────────────────────────────────────────────────────────────┤
│  FOOTER                                                      │
│  Brand | Links | Contact | Social | Copyright               │
└─────────────────────────────────────────────────────────────┘
```

## Image Specifications

| Image | Dimensions | Format | Notes |
|-------|------------|--------|-------|
| Hero background | 1920×800px min | WebP/JPEG | <200KB, dramatic clinical/training photo |
| About section | 600×400px | WebP/JPEG | Team or training photo |
| Event cards | 600×400px | WebP/JPEG | 3:2 ratio |
| Logo | 300×100px max | SVG/PNG | SVG preferred |

## Accessibility Features

- WCAG AA color contrast compliance
- Semantic HTML structure
- Skip link support
- Keyboard navigable
- `prefers-reduced-motion` support
- Focus visible states
- ARIA labels on icons

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Android)

## Performance

Target metrics:
- Largest Contentful Paint: < 2.5s
- First Input Delay: < 100ms
- Cumulative Layout Shift: < 0.1

Optimizations included:
- Font display swap
- CSS custom properties
- Efficient scroll handlers (requestAnimationFrame)
- Intersection Observer for animations

## Customization

### Modifying Sections

Each section in `templates/homepage.php` is clearly marked with comments:
```php
<!-- ============================================
     HERO SECTION
     ============================================ -->
```

### Adding Custom Sections

1. Add HTML to `templates/homepage.php`
2. Add styles to `css/homepage.css`
3. Add any JavaScript to `js/homepage.js`

### Color Scheme

All colors use CSS custom properties. Override in your customizer or add to `style.css`:

```css
:root {
    --color-primary: #your-navy;
    --color-secondary: #your-teal;
    --color-accent: #your-orange;
}
```

## Troubleshooting

### Countdown not showing
- Ensure date is set in Customizer
- Date format must be YYYY-MM-DD
- Date must be in the future

### Events not displaying
- Verify EMS plugin is installed and activated
- Ensure at least one event exists and is published
- Check event dates aren't all in the past

### Styles not loading
- Clear browser cache
- Verify `homepage.css` is in `/css/` directory
- Check `functions-homepage.php` is included

## File Manifest

| File | Lines | Purpose |
|------|-------|---------|
| `templates/homepage.php` | ~450 | Main template |
| `css/homepage.css` | ~850 | All homepage styles |
| `js/homepage.js` | ~180 | Countdown & animations |
| `inc/functions-homepage.php` | ~130 | Asset loading |
| `customizer.php` | ~400 | All customizer settings |
| `footer.php` | ~150 | Enhanced footer |

## Credits

Designed for the REdI Program, Royal Brisbane & Women's Hospital.

Color palette based on medical education best practices:
- Navy: Trust, authority, professionalism
- Teal: Health, vitality, care
- Orange: Action, urgency, engagement

---

**Version:** 1.2.0  
**Requires:** WordPress 6.0+, PHP 7.4+  
**Compatible with:** EMS Plugin 1.0+
