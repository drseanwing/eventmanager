<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both
 * the current comments and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Conference_Starter
 * @since 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password,
 * return early without loading the comments.
 */
if ( post_password_required() ) {
    return;
}
?>

<section id="comments" class="comments-area" aria-label="<?php esc_attr_e( 'Comments', 'conference-starter' ); ?>">
    <?php if ( have_comments() ) : ?>
        <header class="comments-header">
            <h2 class="comments-title">
                <?php
                $comment_count = get_comments_number();
                if ( '1' === $comment_count ) {
                    printf(
                        /* translators: 1: title. */
                        esc_html__( 'One comment on &ldquo;%1$s&rdquo;', 'conference-starter' ),
                        '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                    );
                } else {
                    printf(
                        /* translators: 1: comment count number, 2: title. */
                        esc_html( _nx( '%1$s comment on &ldquo;%2$s&rdquo;', '%1$s comments on &ldquo;%2$s&rdquo;', $comment_count, 'comments title', 'conference-starter' ) ),
                        number_format_i18n( $comment_count ),
                        '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                    );
                }
                ?>
            </h2>
        </header>

        <?php the_comments_navigation(); ?>

        <ol class="comment-list">
            <?php
            wp_list_comments(
                array(
                    'style'       => 'ol',
                    'short_ping'  => true,
                    'avatar_size' => 60,
                    'callback'    => 'conference_starter_comment',
                )
            );
            ?>
        </ol>

        <?php
        the_comments_navigation();

        // If comments are closed and there are comments, let's leave a little note, shall we?
        if ( ! comments_open() ) :
            ?>
            <p class="comments-closed">
                <?php esc_html_e( 'Comments are closed.', 'conference-starter' ); ?>
            </p>
        <?php endif; ?>

    <?php endif; // Check for have_comments(). ?>

    <?php
    // Custom comment form
    $commenter = wp_get_current_commenter();
    $req       = get_option( 'require_name_email' );
    $aria_req  = $req ? ' aria-required="true" required' : '';

    $fields = array(
        'author' => sprintf(
            '<p class="comment-form-author"><label for="author">%1$s%2$s</label><input id="author" name="author" type="text" value="%3$s" size="30" maxlength="245" placeholder="%4$s"%5$s /></p>',
            esc_html__( 'Name', 'conference-starter' ),
            $req ? ' <span class="required">*</span>' : '',
            esc_attr( $commenter['comment_author'] ),
            esc_attr__( 'Your name', 'conference-starter' ),
            $aria_req
        ),
        'email'  => sprintf(
            '<p class="comment-form-email"><label for="email">%1$s%2$s</label><input id="email" name="email" type="email" value="%3$s" size="30" maxlength="100" aria-describedby="email-notes" placeholder="%4$s"%5$s /></p>',
            esc_html__( 'Email', 'conference-starter' ),
            $req ? ' <span class="required">*</span>' : '',
            esc_attr( $commenter['comment_author_email'] ),
            esc_attr__( 'your@email.com', 'conference-starter' ),
            $aria_req
        ),
        'url'    => sprintf(
            '<p class="comment-form-url"><label for="url">%1$s</label><input id="url" name="url" type="url" value="%2$s" size="30" maxlength="200" placeholder="%3$s" /></p>',
            esc_html__( 'Website', 'conference-starter' ),
            esc_attr( $commenter['comment_author_url'] ),
            esc_attr__( 'https://example.com', 'conference-starter' )
        ),
    );

    if ( has_action( 'set_comment_cookies', 'wp_set_comment_cookies' ) && get_option( 'show_comments_cookies_opt_in' ) ) {
        $fields['cookies'] = sprintf(
            '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%1$s /><label for="wp-comment-cookies-consent">%2$s</label></p>',
            empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"',
            esc_html__( 'Save my name, email, and website in this browser for the next time I comment.', 'conference-starter' )
        );
    }

    comment_form(
        array(
            'fields'               => $fields,
            'comment_field'        => sprintf(
                '<p class="comment-form-comment"><label for="comment">%1$s <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" maxlength="65525" aria-required="true" required placeholder="%2$s"></textarea></p>',
                esc_html__( 'Comment', 'conference-starter' ),
                esc_attr__( 'Enter your comment here...', 'conference-starter' )
            ),
            'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title">',
            'title_reply_after'    => '</h3>',
            'cancel_reply_before'  => ' <small>',
            'cancel_reply_after'   => '</small>',
            'class_form'           => 'comment-form',
            'class_submit'         => 'btn btn--primary',
            'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
            'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
            'format'               => 'html5',
        )
    );
    ?>
</section>

<?php
/**
 * Custom comment callback function.
 *
 * @param WP_Comment $comment The comment object.
 * @param array      $args    Comment display arguments.
 * @param int        $depth   Depth of the comment.
 */
function conference_starter_comment( $comment, $args, $depth ) {
    $tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
    ?>
    <<?php echo esc_attr( $tag ); ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent', $comment ); ?>>
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <header class="comment-meta">
                <div class="comment-author vcard">
                    <?php
                    if ( 0 != $args['avatar_size'] ) {
                        echo get_avatar( $comment, $args['avatar_size'] );
                    }
                    ?>
                    <span class="fn">
                        <?php
                        $comment_author_url = get_comment_author_url( $comment );
                        if ( $comment_author_url ) {
                            printf( '<a href="%1$s" rel="external nofollow ugc">%2$s</a>', esc_url( $comment_author_url ), get_comment_author( $comment ) );
                        } else {
                            echo esc_html( get_comment_author( $comment ) );
                        }
                        ?>
                    </span>
                </div>

                <div class="comment-metadata">
                    <a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
                        <time datetime="<?php comment_time( 'c' ); ?>">
                            <?php
                            /* translators: 1: comment date, 2: comment time */
                            printf( esc_html__( '%1$s at %2$s', 'conference-starter' ), get_comment_date( '', $comment ), get_comment_time() );
                            ?>
                        </time>
                    </a>
                    <?php edit_comment_link( esc_html__( 'Edit', 'conference-starter' ), '<span class="edit-link">', '</span>' ); ?>
                </div>

                <?php if ( '0' == $comment->comment_approved ) : ?>
                    <p class="comment-awaiting-moderation">
                        <?php esc_html_e( 'Your comment is awaiting moderation.', 'conference-starter' ); ?>
                    </p>
                <?php endif; ?>
            </header>

            <div class="comment-content">
                <?php comment_text(); ?>
            </div>

            <footer class="comment-footer">
                <?php
                comment_reply_link(
                    array_merge(
                        $args,
                        array(
                            'add_below' => 'div-comment',
                            'depth'     => $depth,
                            'max_depth' => $args['max_depth'],
                            'before'    => '<div class="reply">',
                            'after'     => '</div>',
                        )
                    )
                );
                ?>
            </footer>
        </article>
    <?php
}
