<?php
/**
 * Custom template tags for this theme
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'conference_starter_posted_on' ) ) :
    /**
     * Prints HTML with meta information for the current post-date/time.
     *
     * @since 2.0.0
     */
    function conference_starter_posted_on() {
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        
        if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
            $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated screen-reader-text" datetime="%3$s">%4$s</time>';
        }

        $time_string = sprintf(
            $time_string,
            esc_attr( get_the_date( DATE_W3C ) ),
            esc_html( get_the_date() ),
            esc_attr( get_the_modified_date( DATE_W3C ) ),
            esc_html( get_the_modified_date() )
        );

        printf(
            '<span class="posted-on">%1$s<a href="%2$s" rel="bookmark">%3$s</a></span>',
            conference_starter_get_icon( 'calendar' ),
            esc_url( get_permalink() ),
            $time_string
        );
    }
endif;

if ( ! function_exists( 'conference_starter_posted_by' ) ) :
    /**
     * Prints HTML with meta information for the current author.
     *
     * @since 2.0.0
     */
    function conference_starter_posted_by() {
        printf(
            '<span class="byline">%1$s<span class="author vcard"><a class="url fn n" href="%2$s">%3$s</a></span></span>',
            conference_starter_get_icon( 'user' ),
            esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
            esc_html( get_the_author() )
        );
    }
endif;

if ( ! function_exists( 'conference_starter_comment_count' ) ) :
    /**
     * Prints HTML with the comment count for the current post.
     *
     * @since 2.0.0
     */
    function conference_starter_comment_count() {
        if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
            echo '<span class="comments-link">';
            echo conference_starter_get_icon( 'message-circle' );
            comments_popup_link(
                sprintf(
                    wp_kses(
                        /* translators: %s: post title */
                        __( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'conference-starter' ),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    wp_kses_post( get_the_title() )
                )
            );
            echo '</span>';
        }
    }
endif;

if ( ! function_exists( 'conference_starter_reading_time' ) ) :
    /**
     * Prints estimated reading time for the current post.
     *
     * @since 2.0.0
     * @param int $post_id Optional. Post ID. Default is current post.
     * @return string Reading time string.
     */
    function conference_starter_reading_time( $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        $content = get_post_field( 'post_content', $post_id );
        $word_count = str_word_count( strip_tags( $content ) );
        $reading_time = ceil( $word_count / 200 ); // Average reading speed

        if ( $reading_time < 1 ) {
            $reading_time = 1;
        }

        printf(
            '<span class="reading-time">%1$s<span>%2$s</span></span>',
            conference_starter_get_icon( 'clock' ),
            sprintf(
                /* translators: %d: number of minutes */
                esc_html( _n( '%d min read', '%d min read', $reading_time, 'conference-starter' ) ),
                $reading_time
            )
        );
    }
endif;

if ( ! function_exists( 'conference_starter_entry_footer' ) ) :
    /**
     * Prints HTML with meta information for the categories, tags and comments.
     *
     * @since 2.0.0
     */
    function conference_starter_entry_footer() {
        // Hide category and tag text for pages.
        if ( 'post' === get_post_type() ) {
            /* translators: used between list items, there is a space after the comma */
            $categories_list = get_the_category_list( esc_html__( ', ', 'conference-starter' ) );
            if ( $categories_list ) {
                printf(
                    '<span class="cat-links">%1$s%2$s</span>',
                    conference_starter_get_icon( 'folder' ),
                    $categories_list
                );
            }

            /* translators: used between list items, there is a space after the comma */
            $tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'conference-starter' ) );
            if ( $tags_list ) {
                printf(
                    '<span class="tags-links">%1$s%2$s</span>',
                    conference_starter_get_icon( 'tag' ),
                    $tags_list
                );
            }
        }

        if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
            echo '<span class="comments-link">';
            echo conference_starter_get_icon( 'message-circle' );
            comments_popup_link(
                sprintf(
                    wp_kses(
                        /* translators: %s: post title */
                        __( 'Leave a Comment<span class="screen-reader-text"> on %s</span>', 'conference-starter' ),
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                        )
                    ),
                    wp_kses_post( get_the_title() )
                )
            );
            echo '</span>';
        }

        edit_post_link(
            sprintf(
                wp_kses(
                    /* translators: %s: Name of current post. Only visible to screen readers */
                    __( 'Edit <span class="screen-reader-text">%s</span>', 'conference-starter' ),
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                    )
                ),
                wp_kses_post( get_the_title() )
            ),
            '<span class="edit-link">' . conference_starter_get_icon( 'edit' ),
            '</span>'
        );
    }
endif;

if ( ! function_exists( 'conference_starter_post_thumbnail' ) ) :
    /**
     * Displays an optional post thumbnail.
     *
     * Wraps the post thumbnail in an anchor element on index views, or a div
     * element when on single views.
     *
     * @since 2.0.0
     * @param string $size Thumbnail size. Default 'post-thumbnail'.
     */
    function conference_starter_post_thumbnail( $size = 'post-thumbnail' ) {
        if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
            return;
        }

        if ( is_singular() ) :
            ?>
            <div class="post-thumbnail">
                <?php the_post_thumbnail( $size ); ?>
            </div>
        <?php else : ?>
            <a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <?php
                the_post_thumbnail(
                    $size,
                    array(
                        'alt' => the_title_attribute(
                            array(
                                'echo' => false,
                            )
                        ),
                        'loading' => 'lazy',
                    )
                );
                ?>
            </a>
        <?php
        endif;
    }
endif;

if ( ! function_exists( 'conference_starter_author_bio' ) ) :
    /**
     * Displays the author bio box.
     *
     * @since 2.0.0
     */
    function conference_starter_author_bio() {
        if ( '' === get_the_author_meta( 'description' ) ) {
            return;
        }
        ?>
        <div class="author-bio">
            <div class="author-bio__avatar">
                <?php echo get_avatar( get_the_author_meta( 'ID' ), 96 ); ?>
            </div>
            <div class="author-bio__content">
                <h3 class="author-bio__name">
                    <?php
                    printf(
                        /* translators: %s: Author name */
                        esc_html__( 'About %s', 'conference-starter' ),
                        get_the_author()
                    );
                    ?>
                </h3>
                <p class="author-bio__description">
                    <?php echo wp_kses_post( get_the_author_meta( 'description' ) ); ?>
                </p>
                <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" class="author-bio__link">
                    <?php esc_html_e( 'View all posts', 'conference-starter' ); ?>
                    <?php echo conference_starter_get_icon( 'arrow-right' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
endif;

if ( ! function_exists( 'conference_starter_the_posts_navigation' ) ) :
    /**
     * Displays the posts navigation.
     *
     * @since 2.0.0
     */
    function conference_starter_the_posts_navigation() {
        the_posts_navigation(
            array(
                'prev_text'          => conference_starter_get_icon( 'chevron-left' ) . '<span>' . esc_html__( 'Older posts', 'conference-starter' ) . '</span>',
                'next_text'          => '<span>' . esc_html__( 'Newer posts', 'conference-starter' ) . '</span>' . conference_starter_get_icon( 'chevron-right' ),
                'screen_reader_text' => esc_html__( 'Posts navigation', 'conference-starter' ),
            )
        );
    }
endif;

if ( ! function_exists( 'conference_starter_the_post_navigation' ) ) :
    /**
     * Displays the single post navigation.
     *
     * @since 2.0.0
     */
    function conference_starter_the_post_navigation() {
        $prev_post = get_previous_post();
        $next_post = get_next_post();

        if ( ! $prev_post && ! $next_post ) {
            return;
        }
        ?>
        <nav class="post-navigation" aria-label="<?php esc_attr_e( 'Post Navigation', 'conference-starter' ); ?>">
            <div class="post-navigation__inner">
                <?php if ( $prev_post ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="post-navigation__link post-navigation__link--prev" rel="prev">
                        <span class="post-navigation__label">
                            <?php echo conference_starter_get_icon( 'chevron-left' ); ?>
                            <?php esc_html_e( 'Previous', 'conference-starter' ); ?>
                        </span>
                        <span class="post-navigation__title"><?php echo esc_html( get_the_title( $prev_post ) ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( $next_post ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="post-navigation__link post-navigation__link--next" rel="next">
                        <span class="post-navigation__label">
                            <?php esc_html_e( 'Next', 'conference-starter' ); ?>
                            <?php echo conference_starter_get_icon( 'chevron-right' ); ?>
                        </span>
                        <span class="post-navigation__title"><?php echo esc_html( get_the_title( $next_post ) ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
        <?php
    }
endif;

if ( ! function_exists( 'wp_body_open' ) ) :
    /**
     * Shim for sites older than 5.2.
     *
     * @link https://core.trac.wordpress.org/ticket/12563
     */
    function wp_body_open() {
        do_action( 'wp_body_open' );
    }
endif;
