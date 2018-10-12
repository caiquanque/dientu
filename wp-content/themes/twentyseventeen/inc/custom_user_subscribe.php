<?php

/**
 * Add new column table user
 */
function new_modify_user_table( $column ) {
    $column['subscribeColumn'] = 'Subscribe';
    return $column;
}
add_filter( 'manage_users_columns', 'new_modify_user_table' );

function new_modify_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'subscribeColumn' :
            return get_the_author_meta( 'subscribeColumn', $user_id );
            break;
        default:
    }
    return $val;
}
add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );

/**
 * Edit value subscribe
 */
function save_extra_user_profile_fields( $user_id, $value_subscribe ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'subscribeColumn', $value_subscribe );
}
add_action( 'personal_options_update', 'save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );


/**
 * Add a user in list
 */
function add_user_in_list( $email, $list_id ) {

    $url    = URL_MAILCHIMP.'/lists/' . $list_id . '/members';
    $mch     = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('user:' . API_KEY),
    );

    $data_email = array(
        "email_address" => $email,
        "status"        => "subscribed",
    );
    curl_setopt( $mch, CURLOPT_URL, $url );
    curl_setopt( $mch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $mch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );
    curl_setopt( $mch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $mch, CURLOPT_CUSTOMREQUEST, 'POST' );
    curl_setopt( $mch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $mch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $mch, CURLOPT_POST, true );
    curl_setopt( $mch, CURLOPT_POSTFIELDS, json_encode( $data_email ) );
    $user_list = curl_exec( $mch );
    curl_close( $mch );

    return $user_list;
}

/**
 * Send check list
 */
function send_check_list( $campaign_id ) {

    $url = URL_MAILCHIMP.'/campaigns/' . $campaign_id .'/send-checklist';
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic '.base64_encode( 'user:'. API_KEY)
    );
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_POST, true );
    $list_check = curl_exec( $ch );
    curl_close( $ch );

    return $list_check;
}

/**
 * Sent mail by campaign
 *
 * @return string A list be send mail
 */
function sent_mail_by_campaign( $campaign_id ) {

    $url = URL_MAILCHIMP.'/campaigns/' . $campaign_id .'/actions/send';
    $ch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic '.base64_encode( 'user:'. API_KEY)
    );
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch, CURLOPT_POST, true );
    $mail_sent = curl_exec( $ch );
    curl_close( $ch );

    return $mail_sent;
}

/**
 * My custom dashboard widgets
 */
function my_custom_dashboard_widgets() {

    wp_add_dashboard_widget('custom_help_widget', 'Send Mail For Campaign', 'custom_dashboard');
}
add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');

/**
 * Custom Dashboard
 */
function custom_dashboard() {

    global $current_user;
    $role = $current_user->roles[0];

    $args = [
        'meta_key' => 'subscribeColumn',
        'meta_value' => 1
    ];

    $users = get_users( $args );



    if ( ROLE_ADMINISTRATOR == $role ) {
        echo '<table border="1">
              <tr>
                <th>List Email Subscriber In List MailChimp</th>
              </tr>';
        foreach ($users as $user) {
            echo '<tr>
                <td>' . $user->user_email . '</td>
              </tr>';
        }
        echo '</table>';

        echo '  
        <form method="post"> 
        <input type="submit" name="btn-send-campaign" value="SendMail" >
        </form> ';

    }
    $campaign_id = CAMPAIGN_ID;
    $btn_send_mail = $_POST['btn-send-campaign'];
    if ( $btn_send_mail && $campaign_id ) {

        send_check_list( $campaign_id );
        sent_mail_by_campaign( $campaign_id );
    }
}
