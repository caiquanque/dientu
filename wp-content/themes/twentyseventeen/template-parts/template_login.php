<?php
/**
 * Template Name: Login Page
 */
get_header();

?>

<?php
if (is_user_logged_in()) {
    echo '<div class="logout"> <p>Hello!<div class="logout_user"> You are logged in and can proceed to the <a href="http://example.com/seminar">Online Seminar</a>.</div></p><br /><p><a id="wp-submit" class="logout" href="', wp_logout_url(), '" title="Logout">Logout</a></p></div>';
} else {
    $args = array(
        'echo'           => true,
        'redirect'       => 'http://dientu.local/animals/',
        'label_log_in'   => __( 'Log in' ),
        'form_id'        => 'login-form',
        'label_username' => __( 'Username' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'id_username'    => 'user_login',
        'id_password'    => 'user_pass',
        'id_submit'      => 'wp-submit',
        'remember'       => true,
        'value_username' => NULL,
        'value_remember' => true
    );
    wp_login_form($args);
}

get_footer(); ?>