<?php
/**
 * The Comments Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

// Prevent direct access and check for password protection
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if ( have_comments() ) : ?>
        
        <h2 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            if ( '1' === $comment_count ) {
                printf(
                    esc_html__( 'One comment on &ldquo;%s&rdquo;', 'conference-starter' ),
                    '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                );
            } else {
                printf(
                    esc_html( _n( '%1$s comment on &ldquo;%2$s&rdquo;', '%1$s comments on &ldquo;%2$s&rdquo;', $comment_count, 'conference-starter' ) ),
                    number_format_i18n( $comment_count ),
                    '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                );
            }
            ?>
        </h2>

        <?php the_comments_navigation(); ?>

        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 60,
            ) );
            ?>
        </ol>

        <?php
        the_comments_navigation();

        // If comments are closed and there are comments, leave a note.
        if ( ! comments_open() ) :
            ?>
            <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'conference-starter' ); ?></p>
            <?php
        endif;

    endif; // have_comments()
    ?>

    <?php
    comment_form( array(
        'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
        'title_reply_after'  => '</h3>',
    ) );
    ?>

</div>
