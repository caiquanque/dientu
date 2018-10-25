<?php
/**
 * Template Name: Sticky Post
 */

get_header();

$args = array(
    'posts_per_page' => -1,
    'post__in' => get_option( 'sticky_posts' ),
    'ignore_sticky_posts' => 1,
    'orderby' => 'title',
    'order' => 'ASC'
);
$query = new WP_Query( $args );

if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        echo the_title() . '<br/>';
    }
}

get_footer();