<?php

/**
 * Add new column table user
 */
function new_modify_user_table( $column )
{
    $column[ 'subscribeColumn' ] = 'Subscribe';
    return $column;
}

add_filter( 'manage_users_columns', 'new_modify_user_table' );

function new_modify_user_table_row( $val, $column_name, $user_id )
{
    switch ( $column_name ) {
        case 'subscribeColumn' :
            return get_the_author_meta('subscribeColumn', $user_id );
            break;
        default:
    }
    return $val;
}

add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );

/**
 * Edit value subscribe
 */
function save_extra_user_profile_fields( $user_id, $value_subscribe )
{
    if ( !current_user_can('edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'subscribeColumn', $value_subscribe );
}

add_action( 'personal_options_update', 'save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );


/**
 * Add a user in list
 */
function add_user_in_list( $email, $list_id )
{

    $url = URL_MAILCHIMP . '/lists/' . $list_id . '/members';
    $mch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode( 'user:' . API_KEY ),
    );

    $data_email = array(
        "email_address" => $email,
        "status" => "subscribed",
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
function send_check_list( $campaign_id )
{

    $url = URL_MAILCHIMP . '/campaigns/' . $campaign_id . '/send-checklist';
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode( 'user:' . API_KEY )
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
    curl_close( $ch);

    return $list_check;
}

/**
 * Sent mail by campaign
 *
 * @return string A list be send mail
 */
function sent_mail_by_campaign($campaign_id) {

    $url = URL_MAILCHIMP . '/campaigns/' . $campaign_id . '/actions/send';
    $ch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode( 'user:' . API_KEY )
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
function my_custom_dashboard_widgets()
{

    wp_add_dashboard_widget('custom_help_widget', 'Send Mail For Campaign', 'custom_dashboard');
}

add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');

/**
 * Update a member in list
 *
 * @param $list_id
 * @param $email
 * @return String $member
 */
function update_a_member_in_list( $list_id, $email, $status ) {

    $subscriber_hash = MD5(strtolower( $email ));
    $url = URL_MAILCHIMP . '/lists/' . $list_id . '/members/' . $subscriber_hash;
    $mch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('user:' . API_KEY)
    );
    $data_email = array(
        "email_address" => $email,
        "status" => $status,
    );
    curl_setopt( $mch, CURLOPT_URL, $url );
    curl_setopt( $mch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $mch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );
    curl_setopt( $mch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $mch, CURLOPT_CUSTOMREQUEST, 'PATCH' );
    curl_setopt( $mch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $mch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $mch, CURLOPT_POST, true );
    curl_setopt( $mch, CURLOPT_POSTFIELDS, json_encode( $data_email ));
    $member = curl_exec( $mch );
    curl_close( $mch );

    return $member;
}

/**
 * Custom Dashboard
 */
function custom_dashboard() {

    $list_id = LIST_ID;

    $url = URL_MAILCHIMP . '/lists/' . $list_id . '/members';
    $mch = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('user:' . API_KEY )
    );

    curl_setopt( $mch, CURLOPT_URL, $url );
    curl_setopt( $mch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $mch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );
    curl_setopt( $mch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $mch, CURLOPT_CUSTOMREQUEST, 'GET' );
    curl_setopt( $mch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $mch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $mch, CURLOPT_POST, true );
    $list = (array) json_decode(curl_exec( $mch ));
    curl_close( $mch );

    $members = $list[ 'members' ];

    echo '
    <form method="post">';
    foreach ( $members as $key => $member ) {

        if ($member->status === "subscribed") {
            echo '<input type="checkbox" checked name="userSubscriber[]" value="' . $member->email_address . '" /> ' . $member->email_address . '<br>';
        }
        else {
            echo '<input type="checkbox" name="userUnSubscriber[]" value="' . $member->email_address . '" /> ' . $member->email_address . '<br>';
        }
    }
    echo '<input type="submit" name="btn-send-campaign" value="SendMail" />
    </form> ';
}

function update_a_member_to_subscribered_in_list() {

    $list_id = LIST_ID;
    $btn_send_mail = $_POST['btn-send-campaign'];
    $userSubscriber = $_POST['userSubscriber'];
    $status = "subscribed";

    if (isset( $btn_send_mail )) {

        if ( !empty( $userSubscriber ) ) {

            foreach ( $userSubscriber as $key => $email ) {
                update_a_member_in_list( $list_id, $email, $status );
            }
        }
    }
}
add_action( 'init', 'update_a_member_to_subscribered_in_list' );

function update_a_member_to_unsubscribered_in_list() {

    $list_id = LIST_ID;
    $btn_send_mail = $_POST['btn-send-campaign'];
    $userSubscriber = $_POST['userUnSubscriber'];
    $status = "unsubscribed";

    if (isset( $btn_send_mail )) {

        if ( !empty( $userSubscriber ) ) {

            foreach ( $userSubscriber as $key => $email ) {
                update_a_member_in_list( $list_id, $email, $status );
            }
        }
    }
}
add_action( 'init', 'update_a_member_to_unsubscribered_in_list' );

function sent_email() {

    $campaign_id = CAMPAIGN_ID;
    $btn_send_mail = $_POST['btn-send-campaign'];

    if (isset( $btn_send_mail )) {

        if (isset( $campaign_id )) {

            send_check_list( $campaign_id );
            sent_mail_by_campaign( $campaign_id );
        }
    }
}
add_action( 'init', 'sent_email' );