<?php
/**
 * Template for displaying Event archive
 *
 * Displays a grid of events with filtering and search capabilities.
 *
 * @package    Conference_Starter
 * @subpackage Templates/EMS
 * @since      1.1.0
 * @version    1.1.0
 */

get_header();

// Page header
conference_starter_page_header(
    __( 'Events', 'conference-starter' ),
    __( 'Discover upcoming conferences and events', 'conference-starter' )
);
?>

<div class="page-content">
    <div class="container">
        <div class="inner full-width">
            <main class="main-content">
                
                <?php if ( have_posts() ) : ?>
                    
                    <div class="ems-event-archive-filters">
                        <form method="get" class="ems-archive-filter-form">
                            <div class="ems-filter-group">
                                <label for="ems-event-time" class="screen-reader-text">
                                    <?php esc_html_e( 'Filter by time', 'conference-starter' ); ?>
                                </label>
                                <select name="event_time" id="ems-event-time" class="ems-filter-select">
                                    <option value=""><?php esc_html_e( 'All Events', 'conference-starter' ); ?></option>
                                    <option value="upcoming" <?php selected( isset( $_GET['event_time'] ) && $_GET['event_time'] === 'upcoming' ); ?>>
                                        <?php esc_html_e( 'Upcoming Events', 'conference-starter' ); ?>
                                    </option>
                                    <option value="past" <?php selected( isset( $_GET['event_time'] ) && $_GET['event_time'] === 'past' ); ?>>
                                        <?php esc_html_e( 'Past Events', 'conference-starter' ); ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="ems-filter-group ems-search-group">
                                <label for="ems-event-search" class="screen-reader-text">
                                    <?php esc_html_e( 'Search events', 'conference-starter' ); ?>
                                </label>
                                <input 
                                    type="search" 
                                    id="ems-event-search" 
                                    name="s" 
                                    placeholder="<?php esc_attr_e( 'Search events...', 'conference-starter' ); ?>"
                                    value="<?php echo esc_attr( get_search_query() ); ?>"
                                    class="ems-search-input"
                                >
                                <button type="submit" class="ems-search-submit">
                                    <span class="dashicons dashicons-search"></span>
                                    <span class="screen-reader-text"><?php esc_html_e( 'Search', 'conference-starter' ); ?></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="ems-event-archive-grid">
                        
                        <?php while ( have_posts() ) : the_post(); 
                            // Get event metadata
                            $event_id   = get_the_ID();
                            $start_date = get_post_meta( $event_id, 'event_start_date', true );
                            $end_date   = get_post_meta( $event_id, 'event_end_date', true );
                            $location   = get_post_meta( $event_id, 'event_location', true );
                            $venue_name = get_post_meta( $event_id, 'event_venue_name', true );
                            
                            // Determine if event is past
                            $is_past = false;
                            if ( $start_date ) {
                                $is_past = strtotime( $start_date ) < current_time( 'timestamp' );
                            }
                            
                            $card_class = 'ems-event-card';
                            if ( $is_past ) {
                                $card_class .= ' ems-event-past';
                            }
                        ?>
                            
                            <article id="post-<?php the_ID(); ?>" <?php post_class( $card_class ); ?>>
                                
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <div class="ems-event-card-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'conference-card' ); ?>
                                        </a>
                                        <?php if ( $is_past ) : ?>
                                            <span class="ems-event-badge ems-badge-past">
                                                <?php esc_html_e( 'Past Event', 'conference-starter' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ems-event-card-content">
                                    
                                    <?php if ( $start_date ) : ?>
                                        <div class="ems-event-card-date">
                                            <span class="ems-date-day"><?php echo esc_html( date_i18n( 'd', strtotime( $start_date ) ) ); ?></span>
                                            <span class="ems-date-month"><?php echo esc_html( date_i18n( 'M', strtotime( $start_date ) ) ); ?></span>
                                            <span class="ems-date-year"><?php echo esc_html( date_i18n( 'Y', strtotime( $start_date ) ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="ems-event-card-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <?php if ( $location || $venue_name ) : ?>
                                        <div class="ems-event-card-location">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html( $venue_name ? $venue_name : $location ); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( has_excerpt() || get_the_content() ) : ?>
                                        <div class="ems-event-card-excerpt">
                                            <?php the_excerpt(); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ems-event-card-actions">
                                        <a href="<?php the_permalink(); ?>" class="ems-btn ems-btn-primary ems-btn-small">
                                            <?php esc_html_e( 'View Event', 'conference-starter' ); ?>
                                        </a>
                                    </div>
                                    
                                </div>
                                
                            </article>
                            
                        <?php endwhile; ?>
                        
                    </div>
                    
                    <?php conference_starter_pagination(); ?>
                    
                <?php else : ?>
                    
                    <div class="ems-no-events">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h3><?php esc_html_e( 'No Events Found', 'conference-starter' ); ?></h3>
                        <p><?php esc_html_e( 'There are no events matching your criteria. Please check back soon for upcoming events.', 'conference-starter' ); ?></p>
                    </div>
                    
                <?php endif; ?>
                
            </main>
        </div>
    </div>
</div>

<style>
/* Archive Filters */
.ems-event-archive-filters {
    margin-bottom: var(--spacing-xxl);
}

.ems-archive-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    align-items: center;
}

.ems-filter-group {
    flex: 0 0 auto;
}

.ems-search-group {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.ems-filter-select {
    padding: var(--spacing-sm) var(--spacing-lg) var(--spacing-sm) var(--spacing-md);
    font-family: var(--font-heading);
    font-size: var(--font-size-base);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    background: var(--color-background);
    cursor: pointer;
}

.ems-filter-select:focus {
    border-color: var(--color-accent);
    outline: none;
}

.ems-search-input {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-xxl) var(--spacing-sm) var(--spacing-md);
    font-family: var(--font-heading);
    font-size: var(--font-size-base);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
}

.ems-search-submit {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    padding: 0 var(--spacing-md);
    background: var(--color-primary);
    border: none;
    border-radius: 0 var(--border-radius) var(--border-radius) 0;
    color: var(--color-text-white);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.ems-search-submit:hover {
    background: var(--color-accent);
}

/* Event Grid */
.ems-event-archive-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: var(--spacing-lg);
}

/* Event Card Styles */
.ems-event-card {
    background: var(--color-background);
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
    transition: transform var(--transition-base), box-shadow var(--transition-base);
}

.ems-event-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--box-shadow-hover);
}

.ems-event-card.ems-event-past {
    opacity: 0.8;
}

.ems-event-card-image {
    position: relative;
    overflow: hidden;
}

.ems-event-card-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform var(--transition-base);
}

.ems-event-card:hover .ems-event-card-image img {
    transform: scale(1.05);
}

.ems-event-badge {
    position: absolute;
    top: var(--spacing-sm);
    right: var(--spacing-sm);
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 15px;
}

.ems-badge-past {
    background: rgba(0,0,0,0.7);
    color: var(--color-text-white);
}

.ems-event-card-content {
    padding: var(--spacing-lg);
    position: relative;
}

.ems-event-card-date {
    position: absolute;
    top: -35px;
    left: var(--spacing-lg);
    background: var(--color-accent);
    color: var(--color-text-white);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--border-radius);
    text-align: center;
    line-height: 1.2;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
}

.ems-date-day {
    display: block;
    font-size: var(--font-size-h4);
    font-weight: 600;
    font-family: var(--font-heading);
}

.ems-date-month {
    display: block;
    font-size: var(--font-size-small);
    text-transform: uppercase;
}

.ems-date-year {
    display: block;
    font-size: 11px;
    opacity: 0.8;
}

.ems-event-card-title {
    font-family: var(--font-heading);
    font-size: var(--font-size-h5);
    font-weight: 500;
    margin: var(--spacing-lg) 0 var(--spacing-sm) 0;
}

.ems-event-card-title a {
    color: var(--color-text-dark);
    text-decoration: none;
}

.ems-event-card-title a:hover {
    color: var(--color-accent);
}

.ems-event-card-location {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    font-size: var(--font-size-small);
    color: var(--color-text-light);
    margin-bottom: var(--spacing-sm);
}

.ems-event-card-location .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    color: var(--color-accent-blue);
}

.ems-event-card-excerpt {
    font-size: var(--font-size-small);
    color: var(--color-text);
    margin-bottom: var(--spacing-md);
}

.ems-event-card-excerpt p {
    margin: 0;
}

.ems-event-card-actions {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-border);
}

/* No Events State */
.ems-no-events {
    text-align: center;
    padding: var(--spacing-section);
    background: var(--color-background-alt);
    border-radius: var(--border-radius);
}

.ems-no-events .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--color-accent);
    margin-bottom: var(--spacing-md);
}

.ems-no-events h3 {
    font-family: var(--font-heading);
    margin-bottom: var(--spacing-sm);
}

.ems-no-events p {
    color: var(--color-text-light);
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .ems-event-archive-grid {
        grid-template-columns: 1fr;
    }
    
    .ems-archive-filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ems-filter-group,
    .ems-search-group {
        width: 100%;
    }
    
    .ems-filter-select {
        width: 100%;
    }
}
</style>

<?php get_footer(); ?>
