<?php
/**
 * Plugin Name: Custom Post Type Video
 * Description: Custom Post Type Video is a plugin
 * Version: 1.0
 */

/**
 * Add video custom post type
 */
function videos_custom_post() {
	$labels = [
		'name'               => __( 'Videos', DIENTU ),
		'singular_name'      => __( 'Video', DIENTU ),
		'add_new'            => __( 'Add Item', DIENTU ),
		'all_items'          => __( 'All Items', DIENTU ),
		'add_new_item'       => __( 'Add Item', DIENTU ),
		'edit_item'          => __( 'Edit Item', DIENTU ),
		'new_item'           => __( 'New Item', DIENTU ),
		'view_item'          => __( 'View Item', DIENTU ),
		'search_item'        => __( 'Search Item', DIENTU ),
		'not_found'          => __( 'No items found', DIENTU ),
		'not_found_in_trash' => __( 'No items found in trash', DIENTU ),
		'parent_item_colon'  => __( 'Parent Item', DIENTU )
	];
	$args   = [
		'labels'              => $labels,
		'public'              => true,
		'has_archive'         => true,
		'publicly_queryable'  => true,
		'query_var'           => true,
		'rewrite'             => true,
		'capability_type'     => 'post',
		'hierarchical'        => false,
		'supports'            => [
			'title',
			'editor',
			'excerpt',
			'thumbnail',
			'revisions',
			'page-attributes',
			'custom-fields'
		],
		'menu_position'       => 7,
		'menu_icon'           => 'dashicons-welcome-write-blog',
		'exclude_from_search' => false
	];
	register_post_type( 'video_nha_nha', $args );
}

add_action( 'init', 'videos_custom_post' );

/**
 * Add checkbox in publish post
 */
function add_checkbox_in_publish_post() {
	$post_id = get_the_ID();

	$post_meta    = get_post_meta( $post_id, 'share' );
	$share_status = $post_meta[0];
	?>
    <div class="misc-pub-section">
			<?php if ( $share_status == 1 ): ?>
          <label><input type="checkbox" name="post-share" value="1" checked> Share Me </label>
			<?php else: ?>
          <label><input type="checkbox" name="post-share" value="1"> Share Me </label>
			<?php endif; ?>
    </div>
	<?php
}

add_action( 'post_submitbox_misc_actions', 'add_checkbox_in_publish_post' );

/**
 * Update meta share post
 */
function update_meta_share_post() {

	$check   = $_POST['post-share'];
	$post_id = get_the_ID();

	if ( $check === "1" ) {
		update_post_meta( $post_id, 'share', 1 );
	} else {
		update_post_meta( $post_id, 'share', '' );
	}
}

add_action( 'post_updated', 'update_meta_share_post', 10, 3 );

/**
 *  Add share column
 */
function add_share_column( $columns ) {
	return array_merge( $columns,
		[ 'share' => 'Share' ]
	);
}

add_filter( 'manage_posts_columns', 'add_share_column' );

/**
 * Display posts share column
 */
function display_posts_share_column( $column, $post_id ) {
	if ( $column == 'share' ) {
		echo get_post_field( 'share', $post_id );;
	}
}

add_action( 'manage_posts_custom_column', 'display_posts_share_column', 10, 2 );