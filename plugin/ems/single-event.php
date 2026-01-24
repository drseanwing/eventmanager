<?php
/**
 * Template for displaying single Event posts
 *
 * This template provides enhanced styling and layout for EMS event posts,
 * including automatic integration with schedule, registration, and abstract sections.
 *
 * @package    Conference_Starter
 * @subpackage Templates/EMS
 * @since      1.1.0
 * @version    1.1.0
 */

get_header();

// Get event metadata
$event_id       = get_the_ID();
$start_date     = get_post_meta( $event_id, 'event_start_date', true );
$end_date       = get_post_meta( $event_id, 'event_end_date', true );
$location       = get_post_meta( $event_id, 'event_location', true );
$venue_name     = get_post_meta( $event_id, 'event_venue_name', true );
$event_timezone = get_post_meta( $event_id, 'event_timezone', true );

// Format dates
$date_format = get_option( 'date_format' );
$start_formatted = $start_date ? date_i18n( $date_format, strtotime( $start_date ) ) : '';
$end_formatted = $end_date ? date_i18n( $date_format, strtotime( $end_date ) ) : '';

// Check for featured image for header background
$has_featured = has_post_thumbnail();
$header_class = $has_featured ? 'page-header has-background ems-event-header' : 'page-header ems-event-header';
$header_style = '';

if ( $has_featured ) {
    $image_url = get_the_post_thumbnail_url( $event_id, 'conference-featured' );
    $header_style = 'style="background-image: url(' . esc_url( $image_url ) . ');"';
}
?>

<div class="<?php echo esc_attr( $header_class ); ?>" <?php echo $header_style; ?>>
    <div class="page-header-inner">
        <div class="container">
            <h1 class="page-title"><?php the_title(); ?></h1>
            
            <?php if ( $start_formatted || $location ) : ?>
                <div class="page-tagline ems-event-quick-info">
                    <?php if ( $start_formatted ) : ?>
                        <span class="ems-event-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php 
                            echo esc_html( $start_formatted );
                            if ( $end_formatted && $end_formatted !== $start_formatted ) {
                                echo ' - ' . esc_html( $end_formatted );
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ( $location || $venue_name ) : ?>
                        <span class="ems-event-location">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html( $venue_name ? $venue_name : $location ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="inner full-width">
            <main class="main-content">
                
                <?php while ( have_posts() ) : the_post(); ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'ems-single-event-content' ); ?>>
                        
                        <div class="entry-content">
                            <?php 
                            // The content already includes event details, schedule, registration, 
                            // and abstract sections via EMS_Public::filter_single_event_content()
                            the_content(); 
                            ?>
                        </div>
                        
                    </article>
                    
                <?php endwhile; ?>
                
            </main>
        </div>
    </div>
</div>

<style>
/* Single Event Header Enhancements */
.ems-event-header {
    min-height: 300px;
    display: flex;
    align-items: flex-end;
    padding-bottom: var(--spacing-xxl);
}

.ems-event-header.has-background {
    position: relative;
    background-size: cover;
    background-position: center;
}

.ems-event-header.has-background::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.1) 100%);
}

.ems-event-header .page-header-inner {
    position: relative;
    z-index: 1;
    width: 100%;
}

.ems-event-header .page-title {
    color: var(--color-text-white);
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.ems-event-quick-info {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-lg);
    margin-top: var(--spacing-md);
}

.ems-event-quick-info span {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    color: rgba(255,255,255,0.9);
    font-size: var(--font-size-base);
}

.ems-event-quick-info .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: var(--color-accent);
}

/* Event Content Area */
.ems-single-event-content {
    margin-bottom: var(--spacing-section);
}

.ems-single-event-content .entry-content > div:first-child {
    margin-top: 0;
}

/* Ensure EMS sections look good */
.ems-single-event-content .ems-event-schedule-section,
.ems-single-event-content .ems-event-registration-section,
.ems-single-event-content .ems-event-abstract-section {
    margin-top: var(--spacing-xxl);
    padding-top: var(--spacing-xxl);
    border-top: 1px solid var(--color-border);
}

.ems-single-event-content .ems-event-schedule-section:first-child,
.ems-single-event-content .ems-event-registration-section:first-child {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}

@media (max-width: 768px) {
    .ems-event-header {
        min-height: 250px;
    }
    
    .ems-event-quick-info {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
}
</style>

<?php get_footer(); ?>
