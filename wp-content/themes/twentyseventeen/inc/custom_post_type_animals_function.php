<?php

/**
 * Custom post type
 */
add_action('init', 'custom_post_type');
function custom_post_type ()
{
    $labels = [
        'name'               => __('Animals', DIENTU_THEME),
        'singular_name'      => __('Animal', DIENTU_THEME),
        'add_new'            => __('Add Item', DIENTU_THEME),
        'all_items'          => __('All Items', DIENTU_THEME),
        'add_new_item'       => __('Add Item', DIENTU_THEME),
        'edit_item'          => __('Edit Item', DIENTU_THEME),
        'new_item'           => __('New Item', DIENTU_THEME),
        'view_item'          => __('View Item', DIENTU_THEME),
        'search_item'        => __('Search Item', DIENTU_THEME),
        'not_found'          => __('No items found', DIENTU_THEME),
        'not_found_in_trash' => __('No items found in trash', DIENTU_THEME),
        'parent_item_colon'  => __('Parent Item', DIENTU_THEME),
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
        ],
        'menu_position'       => 7,
        'menu_icon'           => 'dashicons-welcome-write-blog',
        'exclude_from_search' => false,
    ];
    register_post_type('animal', $args);
}

/**
 * Animal info meta box
 */
add_action('add_meta_boxes', 'animal_info_meta_box');
function animal_info_meta_box ()
{
    add_meta_box('ms_meta_box', 'Info Meta Box', 'animal_info_meta_fields', 'animal');
}

/**
 * Animal info meta fields
 */
function animal_info_meta_fields ()
{
    global $post;
    $field = get_post_meta($post->ID, 'animal_info_meta_key', true); ?>
    <table>
        <tr>
            <td weidth="50%">
                Animal Info:
            </td>
            <td weidth="50%">
                <input type="text" name="first" value="<?php echo $field ?>">
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save posts
 */
add_action('save_post', 'save_posts');
function save_posts ()
{
    global $post;
    $ms_field = $_POST['first'];
    update_post_meta($post->ID, 'animal_info_meta_key', $ms_field);
}

function filter_title_ne($output){
    return 'ABC_'.$output;
}
add_filter('the_title','filter_title_ne');

/**
 * Send email when create new animal
 */
add_action('save_post', 'send_emails_create_new_animal');
function send_emails_create_new_animal ($post)
{
    if (get_post_type($post->ID) === 'animal')
    {
        $args    = array(
            'role' => 'administrator',
        );
        $title   = $_POST['post_title'];
        $users   = get_users($args);
        $subject = 'Create Animal success !';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $email   = $users[0]->data->user_email;
        wp_mail($email, $subject, $title . ': was create', $headers);
    }
}
