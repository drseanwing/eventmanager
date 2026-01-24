# Conference Starter Theme - EMS Integration

**Document Purpose:** Event Management System integration files  
**Production Path:** `conference-starter/`  
**Integration Version:** 1.1.0

---

## Table of Contents

1. [Single Event Template](#single-event-template) - `single-event.php`
2. [Event Archive Template](#event-archive-template) - `archive-event.php`
3. [Integration CSS](#integration-css) - `ems-integration.css`
4. [Gutenberg Blocks](#gutenberg-blocks) - `ems-blocks.js`
5. [Theme JavaScript](#theme-javascript) - `main.js`

---

## Single Event Template

**File:** `single-event.php`  
**Production Path:** `conference-starter/single-event.php`

```php
<?php
/**
 * Template for displaying single event posts
 *
 * @package ConferenceStarter
 */

get_header();

while ( have_posts() ) :
    the_post();
    
    // Get event meta
    $event_start = get_post_meta( get_the_ID(), '_ems_event_start_date', true );
    $event_end   = get_post_meta( get_the_ID(), '_ems_event_end_date', true );
    $venue       = get_post_meta( get_the_ID(), '_ems_event_venue', true );
    $address     = get_post_meta( get_the_ID(), '_ems_event_address', true );
    $capacity    = get_post_meta( get_the_ID(), '_ems_event_capacity', true );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-event' ); ?>>
    
    <!-- Event Header with Featured Image -->
    <header class="event-header">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="event-featured-image">
                <?php the_post_thumbnail( 'event-featured' ); ?>
                <div class="event-header-overlay"></div>
            </div>
        <?php endif; ?>
        
        <div class="container event-header-content">
            <div class="event-meta-badges">
                <?php
                $event_types = get_the_terms( get_the_ID(), 'ems_event_type' );
                if ( $event_types && ! is_wp_error( $event_types ) ) :
                    foreach ( $event_types as $type ) :
                ?>
                    <span class="event-type-badge"><?php echo esc_html( $type->name ); ?></span>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
            
            <h1 class="event-title"><?php the_title(); ?></h1>
            
            <div class="event-quick-info">
                <?php if ( $event_start ) : ?>
                    <div class="event-info-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span class="event-date">
                            <?php 
                            echo esc_html( date_i18n( 'l, F j, Y', strtotime( $event_start ) ) );
                            if ( $event_end && date( 'Y-m-d', strtotime( $event_start ) ) !== date( 'Y-m-d', strtotime( $event_end ) ) ) {
                                echo ' - ' . esc_html( date_i18n( 'l, F j, Y', strtotime( $event_end ) ) );
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ( $venue ) : ?>
                    <div class="event-info-item">
                        <span class="dashicons dashicons-location"></span>
                        <span class="event-venue"><?php echo esc_html( $venue ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Event Content -->
    <div class="container event-content-wrapper">
        <div class="event-main-content">
            
            <!-- About Section -->
            <section class="event-section event-about">
                <h2><?php esc_html_e( 'About This Event', 'conference-starter' ); ?></h2>
                <div class="event-description">
                    <?php the_content(); ?>
                </div>
            </section>

            <!-- Schedule Section (if published) -->
            <?php 
            $schedule_published = get_post_meta( get_the_ID(), '_ems_schedule_published', true );
            if ( $schedule_published ) :
            ?>
            <section class="event-section event-schedule">
                <h2><?php esc_html_e( 'Schedule', 'conference-starter' ); ?></h2>
                <?php echo do_shortcode( '[ems_event_schedule event_id="' . get_the_ID() . '" view="list"]' ); ?>
            </section>
            <?php endif; ?>

            <!-- Registration Section -->
            <section class="event-section event-registration">
                <h2><?php esc_html_e( 'Registration', 'conference-starter' ); ?></h2>
                <?php echo do_shortcode( '[ems_registration_form event_id="' . get_the_ID() . '"]' ); ?>
            </section>

        </div>

        <!-- Event Sidebar -->
        <aside class="event-sidebar">
            <div class="event-details-card">
                <h3><?php esc_html_e( 'Event Details', 'conference-starter' ); ?></h3>
                
                <dl class="event-details-list">
                    <?php if ( $event_start ) : ?>
                        <dt><?php esc_html_e( 'Start', 'conference-starter' ); ?></dt>
                        <dd><?php echo esc_html( date_i18n( 'F j, Y \a\t g:i a', strtotime( $event_start ) ) ); ?></dd>
                    <?php endif; ?>
                    
                    <?php if ( $event_end ) : ?>
                        <dt><?php esc_html_e( 'End', 'conference-starter' ); ?></dt>
                        <dd><?php echo esc_html( date_i18n( 'F j, Y \a\t g:i a', strtotime( $event_end ) ) ); ?></dd>
                    <?php endif; ?>
                    
                    <?php if ( $venue ) : ?>
                        <dt><?php esc_html_e( 'Venue', 'conference-starter' ); ?></dt>
                        <dd><?php echo esc_html( $venue ); ?></dd>
                    <?php endif; ?>
                    
                    <?php if ( $address ) : ?>
                        <dt><?php esc_html_e( 'Address', 'conference-starter' ); ?></dt>
                        <dd><?php echo nl2br( esc_html( $address ) ); ?></dd>
                    <?php endif; ?>
                    
                    <?php if ( $capacity ) : ?>
                        <dt><?php esc_html_e( 'Capacity', 'conference-starter' ); ?></dt>
                        <dd><?php echo esc_html( $capacity ); ?> <?php esc_html_e( 'attendees', 'conference-starter' ); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            
            <!-- Sponsors Widget Area -->
            <?php 
            $sponsors = get_posts( array(
                'post_type'      => 'ems_sponsor',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'   => '_ems_sponsor_event_id',
                        'value' => get_the_ID(),
                    ),
                ),
            ) );
            
            if ( $sponsors ) :
            ?>
            <div class="event-sponsors-card">
                <h3><?php esc_html_e( 'Sponsors', 'conference-starter' ); ?></h3>
                <div class="sponsor-logos">
                    <?php foreach ( $sponsors as $sponsor ) : ?>
                        <div class="sponsor-logo">
                            <?php echo get_the_post_thumbnail( $sponsor->ID, 'thumbnail' ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>

</article>

<?php
endwhile;

get_footer();
```

---

## Event Archive Template

**File:** `archive-event.php`  
**Production Path:** `conference-starter/archive-event.php`

```php
<?php
/**
 * Template for displaying event archive
 *
 * @package ConferenceStarter
 */

get_header();
?>

<div class="container">
    <header class="archive-header">
        <h1 class="archive-title"><?php esc_html_e( 'Events', 'conference-starter' ); ?></h1>
        <p class="archive-description">
            <?php esc_html_e( 'Browse our upcoming conferences, workshops, and educational events.', 'conference-starter' ); ?>
        </p>
    </header>

    <!-- Event Filters -->
    <div class="event-filters">
        <form method="get" class="event-filter-form">
            <div class="filter-group">
                <label for="event-type-filter"><?php esc_html_e( 'Type', 'conference-starter' ); ?></label>
                <?php
                wp_dropdown_categories( array(
                    'taxonomy'        => 'ems_event_type',
                    'name'            => 'event_type',
                    'id'              => 'event-type-filter',
                    'show_option_all' => __( 'All Types', 'conference-starter' ),
                    'selected'        => get_query_var( 'event_type' ),
                    'value_field'     => 'slug',
                ) );
                ?>
            </div>
            
            <div class="filter-group">
                <label for="event-date-filter"><?php esc_html_e( 'When', 'conference-starter' ); ?></label>
                <select name="event_time" id="event-date-filter">
                    <option value=""><?php esc_html_e( 'All Events', 'conference-starter' ); ?></option>
                    <option value="upcoming" <?php selected( get_query_var( 'event_time' ), 'upcoming' ); ?>>
                        <?php esc_html_e( 'Upcoming', 'conference-starter' ); ?>
                    </option>
                    <option value="past" <?php selected( get_query_var( 'event_time' ), 'past' ); ?>>
                        <?php esc_html_e( 'Past Events', 'conference-starter' ); ?>
                    </option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary">
                <?php esc_html_e( 'Filter', 'conference-starter' ); ?>
            </button>
        </form>
    </div>

    <!-- Events Grid -->
    <?php if ( have_posts() ) : ?>
        <div class="events-grid">
            <?php while ( have_posts() ) : the_post(); 
                $event_start = get_post_meta( get_the_ID(), '_ems_event_start_date', true );
                $venue       = get_post_meta( get_the_ID(), '_ems_event_venue', true );
            ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'event-card' ); ?>>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <a href="<?php the_permalink(); ?>" class="event-card-image">
                            <?php the_post_thumbnail( 'event-thumbnail' ); ?>
                            <?php if ( $event_start ) : ?>
                                <div class="event-date-badge">
                                    <span class="date-day"><?php echo esc_html( date( 'd', strtotime( $event_start ) ) ); ?></span>
                                    <span class="date-month"><?php echo esc_html( date( 'M', strtotime( $event_start ) ) ); ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                    
                    <div class="event-card-content">
                        <?php
                        $event_types = get_the_terms( get_the_ID(), 'ems_event_type' );
                        if ( $event_types && ! is_wp_error( $event_types ) ) :
                        ?>
                            <span class="event-type"><?php echo esc_html( $event_types[0]->name ); ?></span>
                        <?php endif; ?>
                        
                        <h2 class="event-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
                        <div class="event-card-meta">
                            <?php if ( $event_start ) : ?>
                                <span class="event-card-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo esc_html( date_i18n( 'F j, Y', strtotime( $event_start ) ) ); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ( $venue ) : ?>
                                <span class="event-card-venue">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html( $venue ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-card-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                            <?php esc_html_e( 'Learn More', 'conference-starter' ); ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination( array(
            'prev_text' => '&laquo; ' . __( 'Previous', 'conference-starter' ),
            'next_text' => __( 'Next', 'conference-starter' ) . ' &raquo;',
        ) ); ?>

    <?php else : ?>
        <div class="no-events">
            <p><?php esc_html_e( 'No events found.', 'conference-starter' ); ?></p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

---

## Integration CSS

**File:** `ems-integration.css`  
**Production Path:** `conference-starter/css/ems-integration.css`

### Key Sections

```css
/**
 * EMS Integration Styles
 *
 * Extends theme design tokens to EMS plugin elements
 */

/* ================================
   Single Event Page
   ================================ */

.single-event .event-header {
    position: relative;
    min-height: 400px;
    display: flex;
    align-items: flex-end;
    margin-bottom: var(--spacing-3xl);
}

.single-event .event-featured-image {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
}

.single-event .event-featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.single-event .event-header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.8) 0%,
        rgba(0, 0, 0, 0.4) 50%,
        rgba(0, 0, 0, 0.2) 100%
    );
}

.single-event .event-header-content {
    position: relative;
    z-index: 1;
    color: var(--color-white);
    padding: var(--spacing-xxl) 0;
}

.single-event .event-title {
    font-size: var(--text-4xl);
    margin-bottom: var(--spacing-md);
    color: var(--color-white);
}

/* ================================
   Event Cards (Archive)
   ================================ */

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-xl);
}

.event-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: transform var(--transition-base), box-shadow var(--transition-base);
}

.event-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--box-shadow-lg);
}

.event-date-badge {
    position: absolute;
    top: var(--spacing-md);
    left: var(--spacing-md);
    background: var(--color-accent);
    color: var(--color-white);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius);
    text-align: center;
    line-height: 1.2;
}

/* ================================
   Session Cards (Schedule)
   ================================ */

.ems-session-card {
    background: var(--color-white);
    border-left: 4px solid var(--color-gray-300);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-sm);
    border-radius: 0 var(--border-radius) var(--border-radius) 0;
    transition: all var(--transition-base);
}

.ems-session-card:hover {
    box-shadow: var(--box-shadow);
}

/* Session Type Colors */
.ems-session-card[data-type="keynote"] {
    border-left-color: var(--color-accent);
}

.ems-session-card[data-type="lecture"] {
    border-left-color: var(--color-accent-blue);
}

.ems-session-card[data-type="workshop"] {
    border-left-color: var(--color-success);
}

.ems-session-card[data-type="break"] {
    border-left-color: var(--color-gray-400);
    background: var(--color-gray-50);
}

/* ================================
   Registration Form
   ================================ */

.ems-registration-form {
    max-width: 600px;
}

.ems-registration-form .form-group {
    margin-bottom: var(--spacing-lg);
}

.ems-registration-form label {
    display: block;
    font-weight: 500;
    margin-bottom: var(--spacing-xs);
    color: var(--color-gray-700);
}

.ems-registration-form input,
.ems-registration-form select,
.ems-registration-form textarea {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--border-radius);
    font-family: var(--font-body);
    font-size: var(--text-base);
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.ems-registration-form input:focus,
.ems-registration-form select:focus,
.ems-registration-form textarea:focus {
    outline: none;
    border-color: var(--color-accent);
    box-shadow: 0 0 0 3px rgba(30, 198, 182, 0.15);
}

/* ================================
   Abstract Submission
   ================================ */

.ems-abstract-form .authors-list {
    border: 1px solid var(--color-gray-200);
    border-radius: var(--border-radius);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.ems-abstract-form .author-entry {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--color-gray-100);
}

/* ================================
   Presenter Dashboard
   ================================ */

.ems-presenter-dashboard {
    display: grid;
    gap: var(--spacing-lg);
}

.ems-abstract-card {
    background: var(--color-white);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--box-shadow);
}

.ems-abstract-status {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-full);
    font-size: var(--text-sm);
    font-weight: 500;
}

.ems-abstract-status.submitted { background: var(--color-info); color: white; }
.ems-abstract-status.under-review { background: var(--color-warning); color: white; }
.ems-abstract-status.approved { background: var(--color-success); color: white; }
.ems-abstract-status.rejected { background: var(--color-error); color: white; }

/* ================================
   Print Styles
   ================================ */

@media print {
    .site-header,
    .site-footer,
    .event-sidebar,
    .ems-register-session,
    .ems-schedule-filters {
        display: none !important;
    }

    .ems-session-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .event-schedule {
        font-size: 10pt;
    }
}
```

---

## Gutenberg Blocks

**File:** `ems-blocks.js`  
**Production Path:** `conference-starter/js/ems-blocks.js`

### Block Registrations

```javascript
/**
 * EMS Gutenberg Blocks
 *
 * Registers custom blocks for EMS shortcodes
 */

(function(blocks, element, components, blockEditor) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var RangeControl = components.RangeControl;
    var ToggleControl = components.ToggleControl;
    var TextControl = components.TextControl;

    // Event List Block
    blocks.registerBlockType('ems/event-list', {
        title: 'Event List',
        icon: 'calendar-alt',
        category: 'ems-blocks',
        attributes: {
            limit: { type: 'number', default: 6 },
            columns: { type: 'number', default: 3 },
            showPast: { type: 'boolean', default: false }
        },
        edit: function(props) {
            return [
                el(InspectorControls, { key: 'controls' },
                    el(PanelBody, { title: 'Settings' },
                        el(RangeControl, {
                            label: 'Number of Events',
                            value: props.attributes.limit,
                            onChange: function(value) {
                                props.setAttributes({ limit: value });
                            },
                            min: 1,
                            max: 24
                        }),
                        el(RangeControl, {
                            label: 'Columns',
                            value: props.attributes.columns,
                            onChange: function(value) {
                                props.setAttributes({ columns: value });
                            },
                            min: 1,
                            max: 4
                        }),
                        el(ToggleControl, {
                            label: 'Show Past Events',
                            checked: props.attributes.showPast,
                            onChange: function(value) {
                                props.setAttributes({ showPast: value });
                            }
                        })
                    )
                ),
                el('div', { className: 'ems-block-preview' },
                    el('p', {}, '📅 Event List (' + props.attributes.limit + ' events)')
                )
            ];
        },
        save: function(props) {
            return null; // Rendered server-side
        }
    });

    // Event Schedule Block
    blocks.registerBlockType('ems/event-schedule', {
        title: 'Event Schedule',
        icon: 'schedule',
        category: 'ems-blocks',
        attributes: {
            eventId: { type: 'number', default: 0 },
            view: { type: 'string', default: 'list' },
            groupBy: { type: 'string', default: 'day' },
            showFilters: { type: 'boolean', default: true }
        },
        edit: function(props) {
            return [
                el(InspectorControls, { key: 'controls' },
                    el(PanelBody, { title: 'Settings' },
                        el(TextControl, {
                            label: 'Event ID (0 = auto-detect)',
                            value: props.attributes.eventId,
                            onChange: function(value) {
                                props.setAttributes({ eventId: parseInt(value) || 0 });
                            }
                        }),
                        el(SelectControl, {
                            label: 'View Style',
                            value: props.attributes.view,
                            options: [
                                { label: 'List', value: 'list' },
                                { label: 'Grid', value: 'grid' },
                                { label: 'Timeline', value: 'timeline' }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ view: value });
                            }
                        }),
                        el(SelectControl, {
                            label: 'Group By',
                            value: props.attributes.groupBy,
                            options: [
                                { label: 'Day', value: 'day' },
                                { label: 'Room', value: 'room' },
                                { label: 'Track', value: 'track' }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ groupBy: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Filters',
                            checked: props.attributes.showFilters,
                            onChange: function(value) {
                                props.setAttributes({ showFilters: value });
                            }
                        })
                    )
                ),
                el('div', { className: 'ems-block-preview' },
                    el('p', {}, '📋 Event Schedule (' + props.attributes.view + ' view)')
                )
            ];
        },
        save: function(props) {
            return null;
        }
    });

    // Registration Form Block
    blocks.registerBlockType('ems/registration-form', {
        title: 'Registration Form',
        icon: 'clipboard',
        category: 'ems-blocks',
        attributes: {
            eventId: { type: 'number', default: 0 }
        },
        edit: function(props) {
            return [
                el(InspectorControls, { key: 'controls' },
                    el(PanelBody, { title: 'Settings' },
                        el(TextControl, {
                            label: 'Event ID (0 = auto-detect)',
                            value: props.attributes.eventId,
                            onChange: function(value) {
                                props.setAttributes({ eventId: parseInt(value) || 0 });
                            }
                        })
                    )
                ),
                el('div', { className: 'ems-block-preview' },
                    el('p', {}, '📝 Registration Form')
                )
            ];
        },
        save: function(props) {
            return null;
        }
    });

    // Additional blocks: abstract-submission, my-schedule, my-registrations, 
    // presenter-dashboard, upcoming-events follow same pattern

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.blockEditor
);
```

---

## Block Registration in functions.php

```php
/**
 * Register Gutenberg blocks for EMS
 */
function conference_starter_register_ems_blocks() {
    if ( ! class_exists( 'EMS_Core' ) ) {
        return;
    }

    // Register block category
    add_filter( 'block_categories_all', function( $categories ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug'  => 'ems-blocks',
                    'title' => __( 'Event Management', 'conference-starter' ),
                    'icon'  => 'calendar-alt',
                ),
            )
        );
    } );

    // Enqueue block editor assets
    wp_enqueue_script(
        'conference-starter-ems-blocks',
        get_template_directory_uri() . '/js/ems-blocks.js',
        array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ),
        CONFERENCE_STARTER_VERSION,
        true
    );

    // Register server-side render callbacks
    register_block_type( 'ems/event-list', array(
        'render_callback' => function( $attributes ) {
            $atts = array(
                'limit'     => $attributes['limit'] ?? 6,
                'columns'   => $attributes['columns'] ?? 3,
                'show_past' => $attributes['showPast'] ? 'true' : 'false',
            );
            return do_shortcode( '[ems_event_list ' . http_build_query( $atts, '', ' ' ) . ']' );
        },
    ) );

    // Similar registrations for other blocks...
}
add_action( 'init', 'conference_starter_register_ems_blocks' );
```
