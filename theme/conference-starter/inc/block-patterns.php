<?php
/**
 * Block Patterns for Conference Starter Theme
 *
 * Registers Gutenberg block patterns for homepage and content pages.
 * These patterns are fully editable in the block editor.
 *
 * @package Conference_Starter
 * @since 2.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register block pattern category.
 */
function conference_starter_register_pattern_category() {
    register_block_pattern_category(
        'conference-starter',
        array( 'label' => __( 'Conference Starter', 'conference-starter' ) )
    );
    
    register_block_pattern_category(
        'conference-homepage',
        array( 'label' => __( 'Homepage Sections', 'conference-starter' ) )
    );
    
    register_block_pattern_category(
        'conference-pages',
        array( 'label' => __( 'Page Layouts', 'conference-starter' ) )
    );
}
add_action( 'init', 'conference_starter_register_pattern_category' );

/**
 * Register block patterns.
 */
function conference_starter_register_block_patterns() {
    
    // ==========================================================================
    // HOMEPAGE HERO SECTION
    // ==========================================================================
    register_block_pattern(
        'conference-starter/homepage-hero',
        array(
            'title'       => __( 'Homepage Hero', 'conference-starter' ),
            'description' => __( 'Full-width hero section with title, subtitle, and call-to-action buttons.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'hero', 'banner', 'header', 'homepage' ),
            'content'     => '<!-- wp:cover {"url":"","dimRatio":80,"overlayColor":"primary","minHeight":90,"minHeightUnit":"vh","align":"full","className":"homepage-hero"} -->
<div class="wp-block-cover alignfull homepage-hero" style="min-height:90vh"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-80 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"blockGap":"24px"}},"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"center","className":"hero-date-badge","fontSize":"small"} -->
<p class="has-text-align-center hero-date-badge has-small-font-size">📅 15 March 2026</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"className":"hero-title","fontSize":"huge"} -->
<h1 class="wp-block-heading has-text-align-center hero-title has-huge-font-size">Resuscitation Education for Queensland Clinicians</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"hero-subtitle","fontSize":"large"} -->
<p class="has-text-align-center hero-subtitle has-large-font-size">Evidence-based training in resuscitation, patient deterioration recognition, and clinical simulation. Delivered by RBWH\'s REdI Program.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","className":"hero-location","fontSize":"medium"} -->
<p class="has-text-align-center hero-location has-medium-font-size">📍 Royal Brisbane &amp; Women\'s Hospital • Herston, Brisbane</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"16px"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","className":"is-style-fill"} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-accent-background-color has-background wp-element-button">Register Now</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">View Schedule</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->',
        )
    );

    // ==========================================================================
    // VALUE PROPOSITIONS SECTION (Why Train With Us)
    // ==========================================================================
    register_block_pattern(
        'conference-starter/value-propositions',
        array(
            'title'       => __( 'Value Propositions (3 Cards)', 'conference-starter' ),
            'description' => __( 'Three-column grid showcasing key benefits or features.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'features', 'benefits', 'cards', 'grid' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"backgroundColor":"light-gray","className":"section section-values","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section-values has-light-gray-background-color has-background" style="padding-top:96px;padding-bottom:96px"><!-- wp:heading {"textAlign":"center","className":"section-title"} -->
<h2 class="wp-block-heading has-text-align-center section-title">Why Train with REdI?</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}}},"backgroundColor":"white","className":"value-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group value-card has-white-background-color has-background" style="padding-top:40px;padding-right:32px;padding-bottom:40px;padding-left:32px"><!-- wp:paragraph {"align":"center","className":"value-card-icon","fontSize":"huge"} -->
<p class="has-text-align-center value-card-icon has-huge-font-size">📚</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3,"className":"value-card-title"} -->
<h3 class="wp-block-heading has-text-align-center value-card-title">Evidence-Based Curriculum</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"value-card-text"} -->
<p class="has-text-align-center value-card-text">Our programs are built on the latest resuscitation science and aligned with Australian Resuscitation Council guidelines.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}}},"backgroundColor":"white","className":"value-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group value-card has-white-background-color has-background" style="padding-top:40px;padding-right:32px;padding-bottom:40px;padding-left:32px"><!-- wp:paragraph {"align":"center","className":"value-card-icon","fontSize":"huge"} -->
<p class="has-text-align-center value-card-icon has-huge-font-size">👥</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3,"className":"value-card-title"} -->
<h3 class="wp-block-heading has-text-align-center value-card-title">Expert Faculty</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"value-card-text"} -->
<p class="has-text-align-center value-card-text">Learn from experienced clinicians, simulation educators, and resuscitation specialists from one of Australia\'s largest teaching hospitals.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"32px","right":"32px"}}},"backgroundColor":"white","className":"value-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group value-card has-white-background-color has-background" style="padding-top:40px;padding-right:32px;padding-bottom:40px;padding-left:32px"><!-- wp:paragraph {"align":"center","className":"value-card-icon","fontSize":"huge"} -->
<p class="has-text-align-center value-card-icon has-huge-font-size">🔧</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3,"className":"value-card-title"} -->
<h3 class="wp-block-heading has-text-align-center value-card-title">Hands-On Simulation</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"value-card-text"} -->
<p class="has-text-align-center value-card-text">Practice critical skills in our state-of-the-art simulation facilities with realistic scenarios and immediate feedback.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // UPCOMING EVENTS SECTION (Dynamic from EMS)
    // ==========================================================================
    register_block_pattern(
        'conference-starter/upcoming-events',
        array(
            'title'       => __( 'Upcoming Events (Dynamic)', 'conference-starter' ),
            'description' => __( 'Displays upcoming events from the Event Management System.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'events', 'calendar', 'upcoming', 'schedule' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"className":"section section-events","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section-events" style="padding-top:96px;padding-bottom:96px"><!-- wp:heading {"textAlign":"center","className":"section-title"} -->
<h2 class="wp-block-heading has-text-align-center section-title">Upcoming Events</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"section-subtitle"} -->
<p class="has-text-align-center section-subtitle">Secure your place in our next program</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[ems_upcoming_events count="3" show_past="no"]
<!-- /wp:shortcode -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"48px"}}}} -->
<div class="wp-block-buttons" style="margin-top:48px"><!-- wp:button {"backgroundColor":"secondary"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-secondary-background-color has-background wp-element-button" href="/events/">View All Events →</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // STATISTICS BAR
    // ==========================================================================
    register_block_pattern(
        'conference-starter/statistics-bar',
        array(
            'title'       => __( 'Statistics Bar', 'conference-starter' ),
            'description' => __( 'Four-column statistics display with large numbers.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'stats', 'numbers', 'metrics', 'counter' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"64px","bottom":"64px"}}},"backgroundColor":"primary","className":"section section-stats","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section-stats has-primary-background-color has-background" style="padding-top:64px;padding-bottom:64px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-column" style="padding-top:0;padding-bottom:0"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"3rem","fontWeight":"700"}},"textColor":"white","className":"stat-value"} -->
<p class="has-text-align-center stat-value has-white-color has-text-color" style="font-size:3rem;font-weight:700">2,500+</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.5px"}},"textColor":"light-gray","fontSize":"small","className":"stat-label"} -->
<p class="has-text-align-center stat-label has-light-gray-color has-text-color has-small-font-size" style="letter-spacing:0.5px;text-transform:uppercase">Trained Annually</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"3rem","fontWeight":"700"}},"textColor":"white","className":"stat-value"} -->
<p class="has-text-align-center stat-value has-white-color has-text-color" style="font-size:3rem;font-weight:700">15+</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.5px"}},"textColor":"light-gray","fontSize":"small","className":"stat-label"} -->
<p class="has-text-align-center stat-label has-light-gray-color has-text-color has-small-font-size" style="letter-spacing:0.5px;text-transform:uppercase">Years of Excellence</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"3rem","fontWeight":"700"}},"textColor":"white","className":"stat-value"} -->
<p class="has-text-align-center stat-value has-white-color has-text-color" style="font-size:3rem;font-weight:700">50+</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.5px"}},"textColor":"light-gray","fontSize":"small","className":"stat-label"} -->
<p class="has-text-align-center stat-label has-light-gray-color has-text-color has-small-font-size" style="letter-spacing:0.5px;text-transform:uppercase">Expert Faculty</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"3rem","fontWeight":"700"}},"textColor":"white","className":"stat-value"} -->
<p class="has-text-align-center stat-value has-white-color has-text-color" style="font-size:3rem;font-weight:700">98%</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.5px"}},"textColor":"light-gray","fontSize":"small","className":"stat-label"} -->
<p class="has-text-align-center stat-label has-light-gray-color has-text-color has-small-font-size" style="letter-spacing:0.5px;text-transform:uppercase">Satisfaction Rate</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // PROGRAM TYPES (4 Cards)
    // ==========================================================================
    register_block_pattern(
        'conference-starter/program-types',
        array(
            'title'       => __( 'Program Types (4 Cards)', 'conference-starter' ),
            'description' => __( 'Four program cards in a 2x2 grid layout.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'programs', 'courses', 'services', 'grid' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"className":"section section-programs","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section-programs" style="padding-top:96px;padding-bottom:96px"><!-- wp:heading {"textAlign":"center","className":"section-title"} -->
<h2 class="wp-block-heading has-text-align-center section-title">Our Programs</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px","top":"24px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"32px","right":"32px"}},"border":{"left":{"color":"var(--color-accent)","width":"4px"}}},"backgroundColor":"white","className":"program-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group program-card has-white-background-color has-background" style="border-left-color:var(--color-accent);border-left-width:4px;padding-top:28px;padding-right:32px;padding-bottom:28px;padding-left:32px"><!-- wp:paragraph {"className":"program-icon","fontSize":"x-large"} -->
<p class="program-icon has-x-large-font-size">❤️</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Resuscitation Courses</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">BLS, ALS, and specialty resuscitation training for all clinical levels.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"32px","right":"32px"}},"border":{"left":{"color":"var(--color-secondary)","width":"4px"}}},"backgroundColor":"white","className":"program-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group program-card has-white-background-color has-background" style="border-left-color:var(--color-secondary);border-left-width:4px;padding-top:28px;padding-right:32px;padding-bottom:28px;padding-left:32px"><!-- wp:paragraph {"className":"program-icon","fontSize":"x-large"} -->
<p class="program-icon has-x-large-font-size">⚠️</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Deterioration Recognition</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">DETECT and rapid response training to identify the deteriorating patient.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"24px","top":"24px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"32px","right":"32px"}},"border":{"left":{"color":"var(--color-primary)","width":"4px"}}},"backgroundColor":"white","className":"program-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group program-card has-white-background-color has-background" style="border-left-color:var(--color-primary);border-left-width:4px;padding-top:28px;padding-right:32px;padding-bottom:28px;padding-left:32px"><!-- wp:paragraph {"className":"program-icon","fontSize":"x-large"} -->
<p class="program-icon has-x-large-font-size">⚙️</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Simulation Training</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Immersive scenario-based training in our dedicated simulation centre.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"28px","bottom":"28px","left":"32px","right":"32px"}},"border":{"left":{"color":"#8b5cf6","width":"4px"}}},"backgroundColor":"white","className":"program-card","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group program-card has-white-background-color has-background" style="border-left-color:#8b5cf6;border-left-width:4px;padding-top:28px;padding-right:32px;padding-bottom:28px;padding-left:32px"><!-- wp:paragraph {"className":"program-icon","fontSize":"x-large"} -->
<p class="program-icon has-x-large-font-size">📅</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"4px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size">Conferences &amp; Symposia</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Annual conferences and specialty symposia featuring local and international speakers.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // TESTIMONIALS SECTION
    // ==========================================================================
    register_block_pattern(
        'conference-starter/testimonials',
        array(
            'title'       => __( 'Testimonials (2 Quotes)', 'conference-starter' ),
            'description' => __( 'Two testimonial cards with quotes and author attribution.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'testimonials', 'quotes', 'reviews', 'feedback' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"backgroundColor":"light-gray","className":"section section-testimonials","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section-testimonials has-light-gray-background-color has-background" style="padding-top:96px;padding-bottom:96px"><!-- wp:heading {"textAlign":"center","className":"section-title"} -->
<h2 class="wp-block-heading has-text-align-center section-title">What Participants Say</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}}},"backgroundColor":"white","className":"testimonial-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group testimonial-card has-white-background-color has-background" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"4rem","lineHeight":"1"}},"textColor":"secondary","className":"quote-mark"} -->
<p class="quote-mark has-secondary-color has-text-color" style="font-size:4rem;line-height:1">"</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"italic"}},"className":"testimonial-quote"} -->
<p class="testimonial-quote" style="font-style:italic">The ALS course was the most practical and well-delivered resuscitation training I\'ve attended. The faculty created a safe learning environment while pushing us to perform at our best.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"12px","margin":{"top":"24px"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="margin-top:24px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem"}},"className":"testimonial-avatar"} -->
<p class="testimonial-avatar" style="font-size:2rem">👩‍⚕️</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-weight:600">Dr. Sarah Chen</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"medium-gray","fontSize":"x-small"} -->
<p class="has-medium-gray-color has-text-color has-x-small-font-size">Emergency Medicine Registrar, Princess Alexandra Hospital</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}}},"backgroundColor":"white","className":"testimonial-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group testimonial-card has-white-background-color has-background" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"4rem","lineHeight":"1"}},"textColor":"secondary","className":"quote-mark"} -->
<p class="quote-mark has-secondary-color has-text-color" style="font-size:4rem;line-height:1">"</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"italic"}},"className":"testimonial-quote"} -->
<p class="testimonial-quote" style="font-style:italic">REdI\'s simulation program transformed how our ward responds to deteriorating patients. We\'ve seen real improvements in our escalation times and team communication.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"12px","margin":{"top":"24px"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="margin-top:24px"><!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem"}},"className":"testimonial-avatar"} -->
<p class="testimonial-avatar" style="font-size:2rem">👩‍⚕️</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-weight:600">Amanda Torres</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"medium-gray","fontSize":"x-small"} -->
<p class="has-medium-gray-color has-text-color has-x-small-font-size">Nurse Unit Manager, RBWH Medical Ward 7A</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // CALL TO ACTION SECTION
    // ==========================================================================
    register_block_pattern(
        'conference-starter/call-to-action',
        array(
            'title'       => __( 'Call to Action', 'conference-starter' ),
            'description' => __( 'Full-width CTA section with headline and buttons.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'cta', 'action', 'banner', 'signup' ),
            'content'     => '<!-- wp:cover {"overlayColor":"primary-dark","minHeight":300,"align":"full","className":"section section-cta"} -->
<div class="wp-block-cover alignfull section section-cta" style="min-height:300px"><span aria-hidden="true" class="wp-block-cover__background has-primary-dark-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"700px"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"2.5rem"}},"textColor":"white"} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:2.5rem">Ready to Advance Your Skills?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"light-gray"} -->
<p class="has-text-align-center has-light-gray-color has-text-color">Browse our upcoming events and register today. Early registration discounts available for most programs.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"32px"}}}} -->
<div class="wp-block-buttons" style="margin-top:32px"><!-- wp:button {"backgroundColor":"accent","style":{"typography":{"fontWeight":"600"}}} -->
<div class="wp-block-button" style="font-weight:600"><a class="wp-block-button__link has-accent-background-color has-background wp-element-button" href="/events/">View All Events</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->',
        )
    );

    // ==========================================================================
    // ABOUT PAGE - HERO
    // ==========================================================================
    register_block_pattern(
        'conference-starter/about-hero',
        array(
            'title'       => __( 'About Page Hero', 'conference-starter' ),
            'description' => __( 'Hero section for About pages with title and intro text.', 'conference-starter' ),
            'categories'  => array( 'conference-pages', 'conference-starter' ),
            'keywords'    => array( 'about', 'hero', 'intro' ),
            'content'     => '<!-- wp:cover {"overlayColor":"primary","minHeight":400,"align":"full","className":"about-hero"} -->
<div class="wp-block-cover alignfull about-hero" style="min-height:400px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":1,"textColor":"white","fontSize":"huge"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color has-huge-font-size">About the REdI Program</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"light-gray","fontSize":"large"} -->
<p class="has-text-align-center has-light-gray-color has-text-color has-large-font-size">The Resuscitation Education Initiative – building clinical confidence in Queensland healthcare professionals since 2008.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->',
        )
    );

    // ==========================================================================
    // ABOUT PAGE - MISSION & VISION
    // ==========================================================================
    register_block_pattern(
        'conference-starter/about-mission',
        array(
            'title'       => __( 'Mission & Vision (2 Column)', 'conference-starter' ),
            'description' => __( 'Two-column layout for mission and vision statements.', 'conference-starter' ),
            'categories'  => array( 'conference-pages', 'conference-starter' ),
            'keywords'    => array( 'mission', 'vision', 'about', 'purpose' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"className":"section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section" style="padding-top:96px;padding-bottom:96px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"64px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"textColor":"primary"} -->
<h3 class="wp-block-heading has-primary-color has-text-color">Our Mission</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The Resuscitation Education Initiative (REdI) is dedicated to improving patient outcomes through excellence in resuscitation and acute care education. We empower healthcare professionals with the knowledge, skills, and confidence to respond effectively in critical situations.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Based at the Royal Brisbane and Women\'s Hospital, we deliver evidence-based education programs that meet the diverse needs of Queensland\'s healthcare workforce.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"textColor":"primary"} -->
<h3 class="wp-block-heading has-primary-color has-text-color">Our Vision</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We envision a healthcare system where every clinician has the training and confidence to deliver optimal resuscitation care. Through innovation in simulation education and a commitment to continuous improvement, we strive to be leaders in acute care education.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Our goal is that no matter where a patient deteriorates in Queensland, a REdI-trained clinician is ready to respond.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // ABOUT PAGE - CONTENT WITH IMAGE
    // ==========================================================================
    register_block_pattern(
        'conference-starter/about-content-image',
        array(
            'title'       => __( 'Content with Image (About)', 'conference-starter' ),
            'description' => __( 'Text content with accompanying image for About pages.', 'conference-starter' ),
            'categories'  => array( 'conference-pages', 'conference-starter' ),
            'keywords'    => array( 'about', 'content', 'image', 'media' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"backgroundColor":"light-gray","className":"section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section has-light-gray-background-color has-background" style="padding-top:96px;padding-bottom:96px"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"64px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"textColor":"primary"} -->
<h2 class="wp-block-heading has-primary-color has-text-color">Our History</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Since 2008, REdI has trained thousands of healthcare professionals across Queensland.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>What started as a small initiative to improve cardiac arrest outcomes at RBWH has grown into one of Australia\'s most comprehensive resuscitation education programs. Today, we deliver over 200 courses annually, from basic life support to advanced simulation scenarios.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Our faculty includes emergency physicians, intensivists, anaesthetists, nurses, and paramedics – all united by a passion for education and patient safety.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-rounded"} -->
<figure class="wp-block-image size-large is-style-rounded"><img src="https://images.unsplash.com/photo-1584982751601-97dcc096659c?w=600&amp;h=400&amp;fit=crop" alt="Medical training in progress"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // ABOUT PAGE - TEAM GRID
    // ==========================================================================
    register_block_pattern(
        'conference-starter/team-grid',
        array(
            'title'       => __( 'Team Members Grid', 'conference-starter' ),
            'description' => __( 'Grid display of team members with photos and titles.', 'conference-starter' ),
            'categories'  => array( 'conference-pages', 'conference-starter' ),
            'keywords'    => array( 'team', 'staff', 'people', 'faculty' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"96px","bottom":"96px"}}},"className":"section section-team","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull section section-team" style="padding-top:96px;padding-bottom:96px"><!-- wp:heading {"textAlign":"center","className":"section-title"} -->
<h2 class="wp-block-heading has-text-align-center section-title">Our Faculty</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"section-subtitle"} -->
<p class="has-text-align-center section-subtitle">Meet some of our expert educators</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"24px","right":"24px"}}},"backgroundColor":"white","className":"team-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group team-card has-white-background-color has-background" style="padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"4rem"}}} -->
<p class="has-text-align-center" style="font-size:4rem">👨‍⚕️</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-center has-medium-font-size">Dr. Michael Roberts</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"secondary","fontSize":"small"} -->
<p class="has-text-align-center has-secondary-color has-text-color has-small-font-size">Program Director</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size">Emergency Physician &amp; Simulation Lead</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"24px","right":"24px"}}},"backgroundColor":"white","className":"team-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group team-card has-white-background-color has-background" style="padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"4rem"}}} -->
<p class="has-text-align-center" style="font-size:4rem">👩‍⚕️</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-center has-medium-font-size">Dr. Jennifer Wu</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"secondary","fontSize":"small"} -->
<p class="has-text-align-center has-secondary-color has-text-color has-small-font-size">Education Lead</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size">Intensivist &amp; ALS Course Director</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","bottom":"32px","left":"24px","right":"24px"}}},"backgroundColor":"white","className":"team-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group team-card has-white-background-color has-background" style="padding-top:32px;padding-right:24px;padding-bottom:32px;padding-left:24px"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"4rem"}}} -->
<p class="has-text-align-center" style="font-size:4rem">👩‍⚕️</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-center has-medium-font-size">Sarah Mitchell</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"secondary","fontSize":"small"} -->
<p class="has-text-align-center has-secondary-color has-text-color has-small-font-size">Simulation Coordinator</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size">Clinical Nurse Educator</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // CUSTOM FOOTER (In-Page Footer)
    // ==========================================================================
    register_block_pattern(
        'conference-starter/custom-footer',
        array(
            'title'       => __( 'Custom Footer', 'conference-starter' ),
            'description' => __( 'Editable footer section you can place at the bottom of your page content. Use with "Hide footer" page option.', 'conference-starter' ),
            'categories'  => array( 'conference-pages', 'conference-starter' ),
            'keywords'    => array( 'footer', 'bottom', 'contact', 'links' ),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"64px","bottom":"32px"}}},"backgroundColor":"primary","className":"custom-footer","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull custom-footer has-primary-background-color has-background" style="padding-top:64px;padding-bottom:32px"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"48px"}}}} -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"style":{"spacing":{"blockGap":"16px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:site-title {"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white","fontSize":"large"} /-->

<!-- wp:paragraph {"textColor":"light-gray","fontSize":"small"} -->
<p class="has-light-gray-color has-text-color has-small-font-size">Resuscitation education for Queensland healthcare professionals</p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"iconColor":"white","iconColorValue":"#ffffff","className":"is-style-logos-only"} -->
<ul class="wp-block-social-links has-icon-color is-style-logos-only"><!-- wp:social-link {"url":"https://linkedin.com","service":"linkedin"} /-->

<!-- wp:social-link {"url":"https://twitter.com","service":"x"} /-->

<!-- wp:social-link {"url":"https://youtube.com","service":"youtube"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4,"textColor":"white","fontSize":"small"} -->
<h4 class="wp-block-heading has-white-color has-text-color has-small-font-size">Quick Links</h4>
<!-- /wp:heading -->

<!-- wp:list {"style":{"spacing":{"blockGap":"8px"}},"textColor":"light-gray","fontSize":"small"} -->
<ul class="has-light-gray-color has-text-color has-small-font-size"><!-- wp:list-item -->
<li><a href="/events/">Upcoming Events</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/about/">About REdI</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/submit-abstract/">Submit Abstract</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/contact/">Contact Us</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":4,"textColor":"white","fontSize":"small"} -->
<h4 class="wp-block-heading has-white-color has-text-color has-small-font-size">Contact</h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"light-gray","fontSize":"small"} -->
<p class="has-light-gray-color has-text-color has-small-font-size">REdI Program<br>Royal Brisbane &amp; Women\'s Hospital<br>Butterfield Street<br>Herston QLD 4029</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"light-gray","fontSize":"small"} -->
<p class="has-light-gray-color has-text-color has-small-font-size">📧 redi@health.qld.gov.au</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:separator {"style":{"spacing":{"margin":{"top":"32px","bottom":"24px"}}},"backgroundColor":"primary-dark"} -->
<hr class="wp-block-separator has-text-color has-primary-dark-color has-alpha-channel-opacity has-primary-dark-background-color has-background" style="margin-top:32px;margin-bottom:24px"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"align":"center","textColor":"medium-gray","fontSize":"x-small"} -->
<p class="has-text-align-center has-medium-gray-color has-text-color has-x-small-font-size">© 2026 MNResus / REdI Program. All rights reserved.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->',
        )
    );

    // ==========================================================================
    // FULL HOMEPAGE LAYOUT (All Sections Combined)
    // ==========================================================================
    register_block_pattern(
        'conference-starter/full-homepage',
        array(
            'title'       => __( 'Complete Homepage Layout', 'conference-starter' ),
            'description' => __( 'Full homepage with all sections: Hero, Values, Events, Stats, Programs, Testimonials, CTA.', 'conference-starter' ),
            'categories'  => array( 'conference-homepage', 'conference-starter' ),
            'keywords'    => array( 'homepage', 'landing', 'full', 'complete' ),
            'blockTypes'  => array( 'core/post-content' ),
            'content'     => conference_starter_get_full_homepage_pattern(),
        )
    );

    // ==========================================================================
    // FULL ABOUT PAGE LAYOUT
    // ==========================================================================
    register_block_pattern(
        'conference-starter/full-about-page',
        array(
            'title'       => __( 'Complete About Page Layout', 'conference-starter' ),
            'description' => __( 'Full About page with hero, mission, history, team, and CTA.', 'conference-starter' ),
            'categories'  => array( 'conference-pages', 'conference-starter' ),
            'keywords'    => array( 'about', 'full', 'complete', 'page' ),
            'blockTypes'  => array( 'core/post-content' ),
            'content'     => conference_starter_get_full_about_pattern(),
        )
    );
}
add_action( 'init', 'conference_starter_register_block_patterns' );

/**
 * Get full homepage pattern content.
 * 
 * Separated into function to keep code manageable.
 */
function conference_starter_get_full_homepage_pattern() {
    // Combine all homepage sections
    $patterns = array(
        'conference-starter/homepage-hero',
        'conference-starter/value-propositions', 
        'conference-starter/upcoming-events',
        'conference-starter/statistics-bar',
        'conference-starter/program-types',
        'conference-starter/testimonials',
        'conference-starter/call-to-action',
    );
    
    $content = '';
    foreach ( $patterns as $pattern_name ) {
        $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $pattern_name );
        if ( $pattern ) {
            $content .= $pattern['content'] . "\n\n";
        }
    }
    
    return $content;
}

/**
 * Get full about page pattern content.
 */
function conference_starter_get_full_about_pattern() {
    $patterns = array(
        'conference-starter/about-hero',
        'conference-starter/about-mission',
        'conference-starter/statistics-bar',
        'conference-starter/about-content-image',
        'conference-starter/team-grid',
        'conference-starter/call-to-action',
    );
    
    $content = '';
    foreach ( $patterns as $pattern_name ) {
        $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $pattern_name );
        if ( $pattern ) {
            $content .= $pattern['content'] . "\n\n";
        }
    }
    
    return $content;
}
