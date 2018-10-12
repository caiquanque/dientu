<?php
/**
 * Plugin Name: Mailchimp Send Mail
 * Description: Mailchimp Send Mail is a plugin
 * Version: 1.0
 */

const API_KEY = '1c4b102a3d2eca5735d5db7c294ab4c7-us19';
const URL_MAILCHIMP = 'https://us19.api.mailchimp.com/3.0';

/**
 * Create list in mailchimp
 *
 * @return string A new list be return
 */
function create_list_mailchimp() {

    $dc = URL_MAILCHIMP.'/lists/';
    $data = array(
        'name'                  => 'List 2k9',
        'contact'               => array(
            'company'  => 'abc',
            'address1' => '675 Ponce De Leon Ave NE',
            'address2' => 'Suite 5000',
            'city'     => 'Atlanta',
            'state'    => 'GA',
            'zip'      => '30308',
            'country'  => 'US',
            'phone'    => '098782123',
        ),
        'permission_reminder'   => 'abc',
        'use_archive_bar'       => true,
        'campaign_defaults'     => array(
            'from_name'  => 'abc',
            'from_email' => 'dtttthong@gmail.com',
            'subject'    => 'DEDE',
            'language'   => 'en',
        ),
        'notify_on_subscribe'   => '',
        'notify_on_unsubscribe' => '',
        'email_type_option'     => true,
        'visibility'            => 'pub',
    );
    $data = json_encode( $data );
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $dc );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
    curl_setopt( $ch, CURLOPT_USERPWD, 'user:'.API_KEY );
    $list = (array) json_decode(curl_exec( $ch ));
    $list_id = $list['id'];
    curl_close( $ch );

    return $list_id;
}

/**
 * Function: Add a user in list
 *
 * @param $email
 * @param $list_id
 * @return string A user be created in list
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
 * Create template
 */
function create_template() {

    $url     = URL_MAILCHIMP . '/templates';
    $mch     = curl_init();
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode( 'user:' . API_KEY ),
    );

    $data_email = array(
        "name" => 'Temp',
        "html" => "
                   <h1>Header</h1>
                   <p>Body</p>
                   <h2>Footer</h2>
                  ",
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
    $template    = (array) json_decode(curl_exec( $mch ));
    $template_id = $template['id'];
    curl_close( $mch );

    return $template_id;
}

/**
 * Create campaign
 *
 * @return string A campaign be created
 */
function create_campaign( $list_id, $template_id ) {

    $url = URL_MAILCHIMP.'/campaigns';
    $data = array(
        'recipients' => array( 'list_id' => $list_id ),
        'type'       => 'regular',
        'settings'   => array(
            'subject_line' => 'Subject',
            'title'        => 'Title 2k9',
            'reply_to'     => 'dtttthong@gmail.com',
            'from_name'    => 'Test',
            'template_id'  => $template_id,
        ),
    );
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('user:' . API_KEY),
    );
    $mch = curl_init();
    curl_setopt( $mch, CURLOPT_URL, $url );
    curl_setopt( $mch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $mch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );
    curl_setopt( $mch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $mch, CURLOPT_CUSTOMREQUEST, 'POST' );
    curl_setopt( $mch, CURLOPT_TIMEOUT, 10 );
    curl_setopt( $mch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $mch, CURLOPT_POST, true );
    curl_setopt( $mch, CURLOPT_POSTFIELDS, json_encode( $data ) );
    $campaign = (array) json_decode(curl_exec( $mch ));
    $campaign_id = $campaign['id'];
    curl_close( $mch );

    return $campaign_id;
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
 * Submit when forgot password
 */
function submit_when_forgot_password () {

    $list_id         = create_list_mailchimp();
    $submit_add_user = $_POST['wp-submit'];
    $email           = $_POST['user_login'];
    $template_id     = create_template();
    $campaign_id     = create_campaign( $list_id, $template_id );

    if ( $submit_add_user ) {

        if (filter_var( $email, FILTER_VALIDATE_EMAIL )) {
            add_user_in_list( $email, $list_id );
            send_check_list( $campaign_id );
            sent_mail_by_campaign( $campaign_id );
        } else {
            echo "Invalid email";
        }
    }
}
add_action('retrieve_password_message', 'submit_when_forgot_password');