<?php
/**
 * The Sidebar Template
 *
 * @package Conference_Starter
 * @since 1.0.0
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
    return;
}
?>

<aside class="sidebar" id="secondary" role="complementary">
    <?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
