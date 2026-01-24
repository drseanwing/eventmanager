<?php
/**
 * Template Name: Conference Homepage
 * Template Post Type: page
 *
 * A custom homepage template for conference/event websites.
 * Designed for the REdI Program at Royal Brisbane & Women's Hospital.
 *
 * @package Conference_Starter
 * @since 1.2.0
 */

get_header();

// Get customizer settings
$event_date     = get_theme_mod( 'conference_event_date', '' );
$event_venue    = get_theme_mod( 'conference_event_venue', 'Royal Brisbane & Women\'s Hospital' );
$event_location = get_theme_mod( 'conference_event_location', 'Herston, Brisbane' );
$cta_text       = get_theme_mod( 'conference_cta_text', 'Register Now' );
$cta_url        = get_theme_mod( 'conference_cta_url', '/events/' );

// Get page content for hero
$hero_title    = get_the_title();
$hero_subtitle = get_the_excerpt();
$hero_image    = get_the_post_thumbnail_url( get_the_ID(), 'full' );

// Fallback values
if ( empty( $hero_title ) ) {
    $hero_title = 'Resuscitation Education for Queensland Clinicians';
}
if ( empty( $hero_subtitle ) ) {
    $hero_subtitle = 'Evidence-based training in resuscitation, patient deterioration recognition, and clinical simulation. Delivered by RBWH\'s REdI Program.';
}
?>

<main id="main-content" class="homepage-content">

    <!-- ============================================
         HERO SECTION
         ============================================ -->
    <section class="hero hero--homepage" <?php if ( $hero_image ) : ?>style="background-image: url('<?php echo esc_url( $hero_image ); ?>');"<?php endif; ?>>
        <div class="hero__overlay"></div>
        <div class="hero__content container">
            
            <?php if ( $event_date ) : ?>
            <div class="hero__date-badge">
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span><?php echo esc_html( date_i18n( 'j F Y', strtotime( $event_date ) ) ); ?></span>
            </div>
            <?php endif; ?>
            
            <h1 class="hero__title"><?php echo esc_html( $hero_title ); ?></h1>
            
            <p class="hero__subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
            
            <?php if ( $event_venue || $event_location ) : ?>
            <div class="hero__location">
                <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span>
                    <?php 
                    if ( $event_venue && $event_location ) {
                        echo esc_html( $event_venue . ' • ' . $event_location );
                    } else {
                        echo esc_html( $event_venue . $event_location );
                    }
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="hero__actions">
                <a href="<?php echo esc_url( $cta_url ); ?>" class="button button--primary button--large">
                    <?php echo esc_html( $cta_text ); ?>
                </a>
                <a href="#upcoming-events" class="button button--outline button--large">
                    View Schedule
                </a>
            </div>
            
            <?php if ( $event_date ) : ?>
            <div class="hero__countdown" data-countdown="<?php echo esc_attr( $event_date ); ?>">
                <div class="countdown__item">
                    <span class="countdown__value" data-days>--</span>
                    <span class="countdown__label">Days</span>
                </div>
                <div class="countdown__item">
                    <span class="countdown__value" data-hours>--</span>
                    <span class="countdown__label">Hours</span>
                </div>
                <div class="countdown__item">
                    <span class="countdown__value" data-minutes>--</span>
                    <span class="countdown__label">Minutes</span>
                </div>
                <div class="countdown__item">
                    <span class="countdown__value" data-seconds>--</span>
                    <span class="countdown__label">Seconds</span>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </section>

    <!-- ============================================
         VALUE PROPOSITIONS SECTION
         ============================================ -->
    <section class="section section--values">
        <div class="container">
            <header class="section__header">
                <h2 class="section__title">Why Train with REdI?</h2>
            </header>
            
            <div class="values-grid">
                
                <div class="value-card">
                    <div class="value-card__icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                    </div>
                    <h3 class="value-card__title">Evidence-Based Curriculum</h3>
                    <p class="value-card__text">Our programs are built on the latest resuscitation science and aligned with Australian Resuscitation Council guidelines.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-card__icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3 class="value-card__title">Expert Faculty</h3>
                    <p class="value-card__text">Learn from experienced clinicians, simulation educators, and resuscitation specialists from one of Australia's largest teaching hospitals.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-card__icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                    </div>
                    <h3 class="value-card__title">Hands-On Simulation</h3>
                    <p class="value-card__text">Practice critical skills in our state-of-the-art simulation facilities with realistic scenarios and immediate feedback.</p>
                </div>
                
            </div>
        </div>
    </section>

    <!-- ============================================
         UPCOMING EVENTS SECTION
         ============================================ -->
    <section class="section section--events" id="upcoming-events">
        <div class="container">
            <header class="section__header">
                <h2 class="section__title">Upcoming Events</h2>
                <p class="section__subtitle">Secure your place in our next program</p>
            </header>
            
            <div class="events-grid">
                <?php
                // Check if EMS plugin is active
                if ( shortcode_exists( 'ems_event_list' ) ) {
                    echo do_shortcode( '[ems_event_list limit="3" columns="3" show_past="false"]' );
                } else {
                    // Fallback for when EMS plugin is not active
                    ?>
                    <div class="events-empty">
                        <p>No upcoming events scheduled. Check back soon or subscribe to our newsletter for announcements.</p>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <div class="section__footer">
                <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="button button--secondary">
                    View All Events
                    <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- ============================================
         ABOUT / PROGRAM OVERVIEW SECTION
         ============================================ -->
    <section class="section section--about">
        <div class="container">
            <div class="about-grid">
                
                <div class="about__content">
                    <h2 class="section__title">The REdI Program</h2>
                    
                    <p class="lead">The Resuscitation Education Initiative (REdI) is a multidisciplinary education program based at the Royal Brisbane and Women's Hospital.</p>
                    
                    <p>Since 2008, we have trained thousands of healthcare professionals in life-saving skills. Our programs span the full spectrum of acute care education—from foundational BLS courses for new graduates to advanced simulation scenarios for senior clinicians and rapid response teams.</p>
                    
                    <p>We believe that excellent resuscitation outcomes start with excellent education. Every course we deliver is designed to build competence, confidence, and the ability to perform under pressure.</p>
                    
                    <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="button button--outline">
                        Learn More About Us
                    </a>
                </div>
                
                <div class="about__image">
                    <?php
                    $about_image = get_theme_mod( 'conference_about_image', '' );
                    if ( $about_image ) :
                    ?>
                    <img src="<?php echo esc_url( $about_image ); ?>" alt="REdI Program training session" loading="lazy">
                    <?php else : ?>
                    <div class="about__image-placeholder">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </section>

    <!-- ============================================
         STATISTICS BAR
         ============================================ -->
    <section class="section section--stats">
        <div class="container">
            <div class="stats-grid">
                
                <div class="stat-item">
                    <span class="stat-item__value"><?php echo esc_html( get_theme_mod( 'conference_stat_1_value', '2,500+' ) ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( get_theme_mod( 'conference_stat_1_label', 'Healthcare professionals trained annually' ) ); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-item__value"><?php echo esc_html( get_theme_mod( 'conference_stat_2_value', '15+' ) ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( get_theme_mod( 'conference_stat_2_label', 'Years of education excellence' ) ); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-item__value"><?php echo esc_html( get_theme_mod( 'conference_stat_3_value', '50+' ) ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( get_theme_mod( 'conference_stat_3_label', 'Expert faculty members' ) ); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-item__value"><?php echo esc_html( get_theme_mod( 'conference_stat_4_value', '98%' ) ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( get_theme_mod( 'conference_stat_4_label', 'Participant satisfaction rate' ) ); ?></span>
                </div>
                
            </div>
        </div>
    </section>

    <!-- ============================================
         PROGRAM TYPES SECTION
         ============================================ -->
    <section class="section section--programs">
        <div class="container">
            <header class="section__header">
                <h2 class="section__title">Our Programs</h2>
            </header>
            
            <div class="programs-grid">
                
                <a href="<?php echo esc_url( home_url( '/programs/resuscitation-courses/' ) ); ?>" class="program-card program-card--1">
                    <div class="program-card__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    <div class="program-card__content">
                        <h3 class="program-card__title">Resuscitation Courses</h3>
                        <p class="program-card__text">BLS, ALS, and specialty resuscitation training for all clinical levels.</p>
                    </div>
                    <span class="program-card__arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </span>
                </a>
                
                <a href="<?php echo esc_url( home_url( '/programs/deterioration-training/' ) ); ?>" class="program-card program-card--2">
                    <div class="program-card__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="program-card__content">
                        <h3 class="program-card__title">Deterioration Recognition</h3>
                        <p class="program-card__text">DETECT and rapid response training to identify and escalate the deteriorating patient.</p>
                    </div>
                    <span class="program-card__arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </span>
                </a>
                
                <a href="<?php echo esc_url( home_url( '/programs/simulation/' ) ); ?>" class="program-card program-card--3">
                    <div class="program-card__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </div>
                    <div class="program-card__content">
                        <h3 class="program-card__title">Simulation Training</h3>
                        <p class="program-card__text">Immersive scenario-based training in our dedicated simulation centre.</p>
                    </div>
                    <span class="program-card__arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </span>
                </a>
                
                <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="program-card program-card--4">
                    <div class="program-card__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div class="program-card__content">
                        <h3 class="program-card__title">Conferences & Symposia</h3>
                        <p class="program-card__text">Annual conferences and specialty symposia featuring local and international speakers.</p>
                    </div>
                    <span class="program-card__arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </span>
                </a>
                
            </div>
        </div>
    </section>

    <!-- ============================================
         TESTIMONIALS SECTION
         ============================================ -->
    <?php
    $testimonial_1_quote  = get_theme_mod( 'conference_testimonial_1_quote', 'The ALS course was the most practical and well-delivered resuscitation training I\'ve attended. The faculty created a safe learning environment while pushing us to perform at our best.' );
    $testimonial_1_author = get_theme_mod( 'conference_testimonial_1_author', 'Dr. Sarah Chen' );
    $testimonial_1_role   = get_theme_mod( 'conference_testimonial_1_role', 'Emergency Medicine Registrar, Princess Alexandra Hospital' );
    
    $testimonial_2_quote  = get_theme_mod( 'conference_testimonial_2_quote', 'REdI\'s simulation program transformed how our ward responds to deteriorating patients. We\'ve seen real improvements in our escalation times and team communication.' );
    $testimonial_2_author = get_theme_mod( 'conference_testimonial_2_author', 'Amanda Torres' );
    $testimonial_2_role   = get_theme_mod( 'conference_testimonial_2_role', 'Nurse Unit Manager, RBWH Medical Ward 7A' );
    
    if ( $testimonial_1_quote || $testimonial_2_quote ) :
    ?>
    <section class="section section--testimonials">
        <div class="container">
            <header class="section__header">
                <h2 class="section__title">What Participants Say</h2>
            </header>
            
            <div class="testimonials-grid">
                
                <?php if ( $testimonial_1_quote ) : ?>
                <div class="testimonial-card">
                    <div class="testimonial-card__quote">
                        <svg class="quote-mark" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                        <p><?php echo esc_html( $testimonial_1_quote ); ?></p>
                    </div>
                    <div class="testimonial-card__author">
                        <div class="testimonial-card__avatar">
                            <?php echo esc_html( substr( $testimonial_1_author, 0, 1 ) ); ?>
                        </div>
                        <div class="testimonial-card__info">
                            <span class="testimonial-card__name"><?php echo esc_html( $testimonial_1_author ); ?></span>
                            <span class="testimonial-card__role"><?php echo esc_html( $testimonial_1_role ); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ( $testimonial_2_quote ) : ?>
                <div class="testimonial-card">
                    <div class="testimonial-card__quote">
                        <svg class="quote-mark" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                        </svg>
                        <p><?php echo esc_html( $testimonial_2_quote ); ?></p>
                    </div>
                    <div class="testimonial-card__author">
                        <div class="testimonial-card__avatar">
                            <?php echo esc_html( substr( $testimonial_2_author, 0, 1 ) ); ?>
                        </div>
                        <div class="testimonial-card__info">
                            <span class="testimonial-card__name"><?php echo esc_html( $testimonial_2_author ); ?></span>
                            <span class="testimonial-card__role"><?php echo esc_html( $testimonial_2_role ); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============================================
         CALL TO ACTION SECTION
         ============================================ -->
    <section class="section section--cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta__title"><?php echo esc_html( get_theme_mod( 'conference_cta_headline', 'Ready to Advance Your Skills?' ) ); ?></h2>
                <p class="cta__text"><?php echo esc_html( get_theme_mod( 'conference_cta_subheadline', 'Browse our upcoming events and register today. Early registration discounts available for most programs.' ) ); ?></p>
                <div class="cta__actions">
                    <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="button button--accent button--large">
                        View All Events
                    </a>
                    <?php
                    $newsletter_url = get_theme_mod( 'conference_newsletter_url', '' );
                    if ( $newsletter_url ) :
                    ?>
                    <a href="<?php echo esc_url( $newsletter_url ); ?>" class="cta__link">
                        Subscribe to Updates
                        <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
