<?php
/**
 * Template Name: Subscribe Page
 */

get_header();

if (is_user_logged_in()) :
    global $current_user;
    $role = $current_user->roles[0];

    if ( ROLE_SUBSCRIBER === $role ): ?>
        <form method="post">
            <input type="submit" name="btn-subscribe">
        </form>
    <?php
    endif;
endif;

$btn_subscribe = $_POST['btn-subscribe'];
if ( $btn_subscribe ) {

    $email = wp_get_current_user()->data->user_email;
    $user_id = wp_get_current_user()->ID;
    $list_id = LIST_ID;
    if ( $list_id ) {
        add_user_in_list($email, $list_id);
        save_extra_user_profile_fields( $user_id, '1' );
    }
}

get_footer(); ?>