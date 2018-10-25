<?php
/**
 * Template Name: Show Share Post
 */

get_header();

$array_share = array(
	'post_type'  => 'post',
	'fields'     => 'ids',
	'meta_query' => array(
		array(
			'key'   => 'share',
			'value' => 1,
		)
	)
);

$array_video = array(
	'post_type'      => 'video_nha_nha',
	'fields'         => 'ids',
	'posts_per_page' => - 1
);

$post_share = get_posts( $array_share );
$post_video = get_posts( $array_video );
$post_ids   = array_merge( $post_share, $post_video );

if ( !empty($post_ids )) {

	$final_args = [
		'post_type' => ['post', 'video_nha_nha'],
		'post__in'  => $post_ids,
		'order'     => 'ASC'
	];
	$loop = new WP_Query( $final_args );

	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) {
			$loop->the_post();
			echo the_title().'<br/>';
		}
	}
	wp_reset_postdata();
}

get_footer();