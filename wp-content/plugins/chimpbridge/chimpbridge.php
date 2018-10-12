<?php
/*
Plugin Name: ChimpBridge for MailChimp
Plugin URI: https://wordpress.org/plugins/chimpbridge
Description: ChimpBridge lets you compose and send email campaigns to MailChimp without leaving WordPress.
Version: 1.1.4
Author: ChimpBridge
Author URI: https://chimpbridge.com/
License: GPLv2
Text Domain: chimpbridge
*/

define( 'CHIMPBRIDGE_SCRIPT_VERSION', '1.1.4' );

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2017 7184948 Canada Inc
*/

if ( ! class_exists( 'ChimpBridge' ) ) {
	class ChimpBridge {
		private static $_this;

		public $error_notices;
		private $post_to_unpublish;

		private $base;
		private $version;
		private $api_url;
		private $settings_url;

		private $update_checker;
		private $update_url;

		function __construct() {
			self::$_this        = $this;

			$this->error_notices = new ChimpBridge_Error_Notices();

			$this->base         = plugin_basename( __FILE__ );
			$this->version      = ( defined( 'CHIMPBRIDGE_VERSION' ) ? CHIMPBRIDGE_VERSION : CHIMPBRIDGE_SCRIPT_VERSION );
			$this->settings_url = 'edit.php?post_type=chimpbridge&page=chimpbridge-settings';

			// Setup variables
			define( 'CHIMPBRIDGE_DIR', dirname( __FILE__ ) );
			define( 'CHIMPBRIDGE_URL', plugins_url( '', __FILE__ ) );

			// Let's blast off
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'init', array( $this, 'init' ) );

		}

		static function this() {
			return self::$_this;
		}

		function debug_log( $message ) {
			if ( defined( 'CHIMPBRIDGE_DEBUG' ) and CHIMPBRIDGE_DEBUG ) {
				error_log( 'CHIMPBRIDGE: ' . $message );
			}
		}

		function register_post_type() {
			$args = array(
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'capability_type' => 'post',
				'query_var'       => false,
				'menu_icon'       => 'dashicons-email-alt',
				'supports'        => array(
					'title',
					'editor',
					'author',
					'revisions',
					'custom-fields',
				),
				'labels'          => array(
					'name'               => __('ChimpBridge', 'chimpbridge'),
					'all_items'          => __('All Campaigns', 'chimpbridge'),
					'add_new'            => __('New Campaign', 'chimpbridge'),
					'add_new_item'       => __('New Campaign', 'chimpbridge'),
					'edit_item'          => __('Edit Campaign', 'chimpbridge'),
					'new_item'           => __('New Campaign', 'chimpbridge'),
					'view_item'          => __('View Campaign', 'chimpbridge'),
					'search_item'        => __('Search Campaigns', 'chimpbridge'),
					'not_found'          => __('No Campaigns found', 'chimpbridge'),
					'not_found_in_trash' => __('No Campaigns found in Trash', 'chimpbridge'),
				),
			);

			register_post_type( 'chimpbridge', $args );
		}

		function init() {
			// General admin settings
			add_action( 'admin_enqueue_scripts',        array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_menu',                   array( $this, 'admin_menu' ) );
			add_action( 'admin_notices',                array( $this, 'custom_admin_notices' ) );
			add_action( 'plugin_action_links_' . $this->base, array( $this, 'settings_link' ) );
			add_action( 'plugins_loaded', array( $this, 'load_languages' ) );

			// ChimpBridge editor
			add_action( 'add_meta_boxes',              array( $this, 'add_meta_boxes' ) );
			add_action( 'add_meta_boxes',              array( $this, 'remove_meta_boxes' ) );
			add_filter( 'tiny_mce_before_init',        array( $this, 'readonly_tinymce' ) );
			add_filter( 'gettext',                     array( $this, 'rename_publish_button' ), 10, 2 );
			add_filter( 'post_submitbox_misc_actions', array( $this, 'verify_campaign_details' ) );
			add_action( 'in_admin_footer',             array( $this, 'set_status' ) );
			add_action( 'default_hidden_meta_boxes',   array( $this, 'default_hidden_meta_boxes' ), 10, 2 );

			// Status hooks
			add_action( 'save_post_chimpbridge', array( $this, 'save_chimpbridge_post' ) );
			add_action( 'untrashed_post',      array( $this, 'save_chimpbridge_post' ) );

			// Delete post meta after it is trashed or deleted
			add_action( 'trashed_post',       array( $this, 'delete_chimpbridge_email' ) );
			add_action( 'before_delete_post', array( $this, 'delete_chimpbridge_email' ) );

			// Publish or schedule
			add_action( 'admin_notices', array( $this, 'edit_screen_messages' ) );
			add_action( 'new_to_publish',     array( $this, 'publish_chimpbridge_email' ) );
			add_action( 'draft_to_publish',   array( $this, 'publish_chimpbridge_email' ) );
			add_action( 'pending_to_publish', array( $this, 'publish_chimpbridge_email' ) );

			// Settings
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Ajax
			add_action( 'wp_ajax_get_mailchimp_lists',        array( $this, 'ajax_get_mailchimp_lists' ) );
			add_action( 'wp_ajax_get_mailchimp_segments',     array( $this, 'ajax_get_mailchimp_segments' ) );
			add_action( 'wp_ajax_refresh_mailchimp_lists',    array( $this, 'ajax_refresh_mailchimp_lists' ) );
			add_action( 'wp_ajax_refresh_mailchimp_segments', array( $this, 'ajax_refresh_mailchimp_segments' ) );
			add_action( 'wp_ajax_chimpbridge_send_test', array( $this, 'ajax_mailchimp_send_test' ) );

			// Notices/Messages
			add_action( 'admin_head', array( $this, 'api_key_from_mc4wp' ) );
			add_action( 'admin_head',            array( $this, 'api_key_nag' ) );
			add_filter( 'post_updated_messages', array( $this, 'change_post_updated_messages' ) );
			add_filter( 'post_updated_messages', array( $this, 'suppress_post_published_notice' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'chimpbridge_add_action_links' ) );

			do_action( 'chimpbridge_init' );
		}

		function custom_admin_notices() {
			if ( ! isset( $_GET['chimpbridge_test_sent'] ) ) {
				return;
			}
			?>
			<div class="updated">
				<p><?php esc_html_e( 'Test message sent', 'text-domain' ); ?></p>
			</div>
			<?php
		}

		/**
		 * admin_enqueue_scripts
		 */
		function admin_enqueue_scripts() {
			wp_enqueue_style( 'chimpbridge-global', CHIMPBRIDGE_URL . '/assets/stylesheets/global.css' );

			$screen = get_current_screen();
			if ( 'chimpbridge' == $screen->post_type ) {
				wp_enqueue_style('thickbox');
				wp_enqueue_script('thickbox');

				wp_enqueue_style(
					'chimpbridge-styles',
					CHIMPBRIDGE_URL . '/assets/stylesheets/chimpbridge.css',
					array(),
					$this->version
				);

				wp_enqueue_script(
					'chimpbridge-general',
					CHIMPBRIDGE_URL . '/assets/javascripts/chimpbridge.js',
					array('jquery'),
					$this->version,
					true
				);

				wp_enqueue_script(
					'chimpbridge-ajax',
					CHIMPBRIDGE_URL . '/assets/javascripts/ajax.js',
					array( 'jquery' ),
					$this->version,
					true
				);

				// Use this localization call for any localized variable data. For localized strings, use one of the next two.
				wp_localize_script(
					'chimpbridge-ajax',
					'chimpbridgeAjax',
					array(
						'url'   => admin_url('admin-ajax.php'),
						'nonce' => wp_create_nonce('chimpbridge_ajax_nonce'),
					)
				);

				// For all string localizations in assets/javascripts/chimpbridge.js.
				wp_localize_script(
					'chimpbridge-general',
					'chimpbridge_general_localized_strings',
					array(
						'Select a list'         => esc_html__( 'Select a list', 'chimpbridge' ),
						'Please select a list.' => esc_html__( 'Please select a list.', 'chimpbridge' ),
						'Select a segment'      => esc_html__( 'Select a segment', 'chimpbridge' ),
						'Please select a list segment (or choose to send this campaign to the entire list).' => esc_html__( 'Please select a list segment (or choose to send this campaign to the entire list).', 'chimpbridge' ),
						'Send Campaign' => esc_html__( 'Send Campaign', 'chimpbridge' ),
						'Enter a subject' => esc_html__( 'Please enter a subject for the campaign', 'chimpbridge' ),
						'Enter test emails' => esc_html__( 'Please enter one or more email addresses', 'chimpbridge' ),
					)
				);

				// For all string localizations in assets/javascripts/ajax.js.
				wp_localize_script(
					'chimpbridge-ajax',
					'chimpbridge_ajax_localized_strings',
					array(
						'Loading...'          => esc_html__( 'Loading...', 'chimpbridge' ),
						'Select a list'       => esc_html__( 'Select a list', 'chimpbridge' ),
						'Select a segment'    => esc_html__( 'Select a segment', 'chimpbridge' ),
						'Send to entire list' => esc_html__( 'Send to entire list', 'chimpbridge' ),
						'No List Selected'    => esc_html__( 'No List Selected', 'chimpbridge' ),
						'segment of the'      => esc_html__( 'segment of the', 'chimpbridge' ),
						'This list has no segments' => apply_filters( 'chimpbridge_msg_no_segments', esc_html__( 'Segments not available', 'chimpbridge' ) ),
					)
				);
			}
		}

		/**
		 * admin_menu
		 */
		function admin_menu() {
			add_submenu_page(
				'edit.php?post_type=chimpbridge',
				__( 'ChimpBridge Settings', 'chimpbridge' ),
				__( 'Settings', 'chimpbridge' ),
				'edit_posts',
				'chimpbridge-settings',
				array( $this, 'page_settings' )
			);
		}

		/**
		 * language strings
		 */
		public function load_languages() {
			if ( class_exists( 'ChimpBridgePro' ) )
				return;
			load_plugin_textdomain( 'chimpbridge', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}


		/**
		 * page_settings
		 */
		function page_settings() {
			include CHIMPBRIDGE_DIR . '/templates/page_settings.php';
		}

		/**
		 * register_settings
		 */
		function register_settings() {
			register_setting(
				'chimpbridge_settings_fields',
				'chimpbridge_mailchimp_key',
				array( $this, 'settings_validate_mailchimp_key' )
			);

			/**
			 * General Settings
			 */

			add_settings_section(
				'chimpbridge-general',
				'',
				array( $this, 'settings_section_general' ),
				'chimpbridge-settings'
			);

			/**
			 * API Access
			 */

			add_settings_section(
				'chimpbridge-mailchimp',
				'MailChimp',
				array( $this, 'settings_section_api_access' ),
				'chimpbridge-settings'
			);

			add_settings_field(
				'chimpbridge_mailchimp_key',
				__( 'MailChimp API Key', 'chimpbridge' ),
				array( $this, 'settings_field_mailchimp_key' ),
				'chimpbridge-settings',
				'chimpbridge-mailchimp'
			);
		}

		/**
		 * settings_section_api_access
		 */
		function settings_section_api_access() {
			return;
		}

		/**
		 * settings_section_general
		 */
		function settings_section_general() {
			return;
		}

		/**
		 * settings_field_mailchimp_key
		 */
		function settings_field_mailchimp_key( $id ) {
			$setting = $this->get_mailchimp_key();

			$readonly = '';
			if ( defined( 'CHIMPBRIDGE_MAILCHIMP_KEY' ) )
				$readonly = ' readonly';

		    echo '<input type="text" name="chimpbridge_mailchimp_key" class="widefat" value="' . esc_attr( $this->obfuscate_string( $setting ) ) . '"' . $readonly . '>';
			echo '<p class="description"><a href="https://admin.mailchimp.com/account/api" target="_blank">' . esc_html__( 'Get your API key here', 'chimpbridge' ) . '</a></p>';
		}

		function settings_validate_mailchimp_key( $key ) {
			if ( false !== strpos( $key, '*' ) ) {
				// Don't use the obfuscated key, used the saved setting
				$key = $this->get_mailchimp_key();
			}
			return $key;
		}

		/**
		 * Replace the first half of the string with *
		 *
		 * @param $string
		 *
		 * @return string
		 */
		function obfuscate_string( $string ) {
			$length = strlen( $string );
			$obfuscated_length = ceil( $length / 2 );
			$string = str_repeat( '*', $obfuscated_length ) . substr( $string, $obfuscated_length );
			return $string;
		}

		/**
		 * meta_box
		 */
		function meta_box( $post, $metabox ) {
			$box = $metabox['args']['box'];
			include( CHIMPBRIDGE_DIR . "/templates/meta_box_$box.php" );
		}

		/**
		 * add_meta_boxes
		 */
		function add_meta_boxes() {
			if ( get_post_status() == 'publish' ) {
				add_meta_box(
					'chimpbridge_preview',
					__('View Your Campaign', 'chimpbridge'),
					array( $this, 'meta_box' ),
					'chimpbridge',
					'normal',
					'high',
					array(
						'box' => 'preview',
					)
				);
			}

			add_meta_box(
				'chimpbridge_options',
				__('Manage Campaign', 'chimpbridge'),
				array( $this, 'meta_box' ),
				'chimpbridge',
				'normal',
				'high',
				array(
					'box' => 'options',
				)
			);

			if ( get_post_status() != 'publish' ) {
				add_meta_box(
					'chimpbridge_save',
					__('Preview', 'chimpbridge'),
					array( $this, 'meta_box' ),
					'chimpbridge',
					'side',
					'high',
					array(
						'box' => 'test',
					)
				);

				add_meta_box(
					'chimpbridge_reference',
					__('MailChimp Reference', 'chimpbridge'),
					array( $this, 'meta_box' ),
					'chimpbridge',
					'side',
					'default',
					array(
						'box' => 'reference',
					)
				);
			}
		}

		/**
		 * remove_meta_boxes
		 */
		function remove_meta_boxes() {
			remove_meta_box( 'slugdiv', 'chimpbridge', 'normal' );
		}

		function edit_screen_messages() {
			if ( isset( $_GET['notice'], $_GET['post_type'] ) && 'chimpbridge' == $_GET['post_type'] ) {
				switch ( $_GET['notice'] ) {
					case 'max':
						?>
						<div class="notice notice-success is-dismissible">
							<p><?php echo sprintf( esc_html__( 'Do you want to create more than five emails or drafts? Our Pro version does just that!  %sHave a look at all the Pro benefits.%s', 'chimpbridge' ), '<a href="https://chimpbridge.com/features/?utm_campaign=max-emails&utm_source=plugin" target="_blank">', '</a>' ); ?></p>
						</div>
						<?php
						break;
				}
			}
		}

		/**
		 * save_campaign_meta
		 */
		function save_campaign_meta( $postID ) {
			$this->debug_log( 'starting save_campaign_meta' );

			$post   = get_post( intval( $postID ) );
			$postID = $post->ID;

			if ( empty( $_POST ) )
				return $postID;

			if ( ! isset( $_POST['post_type'] ) || 'chimpbridge' != $_POST['post_type'] )
				return $postID;

			//if( !isset( $_POST['chimpbridge_nonce'] ) || !wp_verify_nonce( $_POST['chimpbridge_nonce'], 'chimpbridge' ) )
			//	return $postID;

			// Get the post type
			$post_type = get_post_type_object( $post->post_type );

			// If the current user is not allowed to edit, stop here
			if ( ! current_user_can( $post_type->cap->edit_post, $postID ) )
				return $postID;

			// List the fields to save
			$fields = apply_filters( 'chimpbridge_settings_fields_to_save', array(
				'_chimpbridge_select_lists',
				'_chimpbridge_select_segments',
				'_chimpbridge_from_name',
				'_chimpbridge_from_email',
				'_chimpbridge_to_name',
			) );

			foreach ( $fields as $field ) {
				if ( empty( $_POST[ $field ] ) ) {
					$meta_value = '';
				} else {
					$meta_value = ( apply_filters( 'chimpbridge_strip_tags', true, $field ) ? apply_filters( 'chimpbridge_field_save', sanitize_text_field( $_POST[ $field ] ), $field, $postID ) : apply_filters( 'chimpbridge_field_save', wp_kses_post( $_POST[ $field ] ), $field, $postID ) );
				}

				$meta_key = $field;

				update_post_meta( $postID, $meta_key, $meta_value );
			}
		}

		/**
		 * rename_publish_button
		 */
		function rename_publish_button( $translation, $text ) {
			global $post_type;

			if ( 'Publish' == $text && 'chimpbridge' == $post_type )
				return __( 'Send Campaign', 'chimpbridge' );

			return $translation;
		}

		/**
		 * verify_campaign_details
		 */
		function verify_campaign_details() {
			global $post_type;

			if ( 'chimpbridge' == $post_type )
				include( CHIMPBRIDGE_DIR . '/templates/verify.php' );
		}

		/**
		 * settings_link
		 */
		function settings_link( $links ) {
			$settings_link = '<a href="' . admin_url( $this->settings_url ) . '">' . esc_html__( 'Settings', 'chimpbridge' ) . '</a>';

			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * get_mailchimp_key
		 */
		function get_mailchimp_key() {
			if ( defined( 'CHIMPBRIDGE_MAILCHIMP_KEY' ) )
				return CHIMPBRIDGE_MAILCHIMP_KEY;
			else
				return get_option( 'chimpbridge_mailchimp_key' );
		}

		/**
		 * get_mailchimp_api_base
		 *
		 * @return string;
		 */
		function get_mailchimp_api_base() {
			$dc = explode('-', $this->get_mailchimp_key(), 2 );

			if ( $dc['1'] )
				$dc = $dc['1'];
			else
				$dc = 'us1';

			return 'https://' . $dc . '.api.mailchimp.com/3.0';
		}

		/**
		 * Make a request to the MailChimp API.
		 *
		 * @param array $raw_settings
		 * 		string $resource  Path to the API resource.
		 * 		string $method  HTTP method for the request.
		 * 		array $data  Data to be JSON-encoded for the request body.
		 * 		string $query_params  Query string, without leading ?.
		 * 		bool $full  Whether to return the full HTTP response object or just the body.
		 * @return mixed
		 */
		function send_mailchimp_request( $raw_settings = array() ) {
			$this->debug_log( 'starting send_mailchimp_request with ' . print_r( $raw_settings, true ) );

			$defaults = array(
				'resource'     => '',
				'method'       => 'GET',
				'data'         => array(),
				'query_params' => array(),
				'full'         => false,
			);

			$settings = wp_parse_args( $raw_settings, $defaults );

			$url = add_query_arg( $settings['query_params'], $this->get_mailchimp_api_base() . '/' . $settings['resource'] );

			$this->debug_log( 'mailchimp_request data: ' . print_r( $settings['data'], true ) );

			$request = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'foo' . ':' . $this->get_mailchimp_key() )
				),
				'body'    => ( ( ! empty( $settings['data'] ) ? json_encode( $settings['data'] ) : array() ) ),
				'timeout' => 30,
				'method'  => strtoupper( $settings['method'] ),
			);

			// Make the request
			$this->debug_log( 'making request to mailchimp to ' . $url . ' and request ' . print_r( $request, true ) );
			$response = wp_remote_request( esc_url( $url ), $request );

			if ( $settings['full'] == true ) {
				return $response;
			}

			// wp_remote_post returned a WP_Error object, assume that it's because we couldn't hit the remote servers.
			if ( is_wp_error( $response ) ) {
				$this->error_notices->add_error_notice ( esc_html__( "ChimpBridge couldn't get in touch with the MailChimp servers. They might be down. Try again later!", 'chimpbridge' ) );
				return false;
			}

			// Grab the body
			$body = wp_remote_retrieve_body( $response );
			$this->debug_log( 'mailchimp response body: ' . print_r( $body, true ) );

			// Decode the response
			$decoded = json_decode( $body, true );

			// If there's an error...
			if ( '2' != substr($response[ 'response' ][ 'code' ], 0, 1) ) {
				$this->error_notices->add_error_notice( '<strong>' . esc_html__( 'MailChimp Error:', 'chimpbridge' ) . '</strong> <a href="' . $decoded[ 'type' ] . '">' . $decoded[ 'title' ] . '.</a> ' . $decoded[ 'detail' ] . ' ' . ( isset( $decoded['errors'] ) ? print_r( $decoded['errors'], true ) : '' ) );
				return false;
			}

			// For 204 responses, there's no body but the status is
			// good, so we return true.
			if ( '204' == $response[ 'response' ][ 'code' ] ) {
				return true;
			}

			return $decoded;
		}

		/**
		 * get_mailchimp_lists
		 *
		 * @return array|false
		 */
		function get_mailchimp_lists() {
			$this->debug_log( 'starting get_mailchimp_lists' );

			// If the transient is not defined...
			if ( false === ( $lists = get_transient( 'chimpbridge_mailchimp_lists' ) ) ) {
				// Make the request
				$response = $this->send_mailchimp_request( array(
					'resource'     => 'lists/',
				) );

				// Build a simpler version of the lists array
				$lists = array();
				foreach ( (array) $response['lists'] as $list ) {
					$lists[] = array(
						'id'   => $list['id'],
						'name' => $list['name'],
						'default_from_name' => $list['campaign_defaults']['from_name'],
						'default_from_email' => $list['campaign_defaults']['from_email'],
					);
				}

				// Set the transient, with a 24 hour expiration
				set_transient( 'chimpbridge_mailchimp_lists', $lists, 24 * HOUR_IN_SECONDS );
			}

			return $lists;
		}

		/**
		 * get_mailchimp_groups
		 *
		 * @param $listID string the mailchimp ID of the list
		 * @return array|false
		 */
		function get_mailchimp_groups( $listID ) {
			// If the transient is not defined...
			// TODO: Set a transient to cache here or on callee
			if ( false === ( $groups = get_transient( 'chimpbridge_email_mailchimp_groups_' . $this->get_mailchimp_listid() ) ) ) {
				/**
				 * Make the request to MailChimp, getting only the category IDs and titles.
				 */
				$interest_categories = $this->send_mailchimp_request( array(
					'resource'     => 'lists/' . $listID . '/interest-categories',
					'query_params' => array(
						'fields' => 'categories.id,categories.title',
					),
				) );

				/**
				 * Build a simpler version of the segment array
				 */
				$groups = array();

				foreach( $interest_categories['categories'] as $interest_category ) {
					$category_interest = $this->send_mailchimp_request( array(
						'resource' => 'lists/' . $listID . '/interest-categories/' . esc_attr( $interest_category['id'] ) . '/interests',
						'query_params' => array(
							'fields' => 'interests.id',
							'count'  => '1',
						),
					) );

					$groups[ $interest_category['title'] ] = array(
						'category_id' => $interest_category['id'],
						'interest_id' => $category_interest['interests'][0]['id'],
					);
				}
			}


			/**
			 * Return our $groups array
			 */
			return $groups;
		}

		/**
		 * get_mailchimp_segments
		 *
		 * @param $listID string mailchimp ID of the list
		 * @return array|false
		 * @throws Exception
		 */
		function get_mailchimp_segments( $listID ) {
			if ( ! trim( $listID ) )
				throw new Exception( 'Trying to get segment with no list ID' );

			$segments = apply_filters( 'chimpbridge_segments', array(), $listID );
			return $segments;
		}

		/**
		 * mailchimp_send_test
		 *
		 * @param int ID of the post
		 * @param string emails
		 * @return mixed
		 */
		function mailchimp_send_test( $postID, $emails ) {
			$this->debug_log( 'starting mailchimp_send_test' );
			if ( $cID = get_post_meta( intval( $postID ), 'chimpbridge_email_campaign_id', true ) ) {
				$data = array(
					'test_emails' => array_map( 'trim', explode( ',', $emails ) ),
					'send_type'   => 'html'
				);

				foreach ( $data['test_emails'] as $email )
					if ( ! is_email( $email ) )
						return false;

				$response = $this->send_mailchimp_request( array(
					'resource' => 'campaigns/' . $cID . '/actions/test',
					'method'   => 'POST',
					'data'     => $data,
				) );

				$this->debug_log( 'send_test response: ' . print_r( $response, true ) );

				// add a query param so we can show a message on redirect
				add_filter( 'redirect_post_location', array( $this, 'add_send_test_notice_query_var' ), 99 );
			} else {
				$response = false;
			}

			return $response;
		}

		function add_send_test_notice_query_var( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_send_test_notice_query_var' ), 99 );
			return add_query_arg( array( 'chimpbridge_test_sent' => '1' ), $location );
		}

		/**
		 * ajax_get_mailchimp_lists
		 */
		function ajax_get_mailchimp_lists() {
			check_ajax_referer( 'chimpbridge_ajax_nonce', 'nonce' );

			$output = $this->get_mailchimp_lists();

			if ( true && is_array( $output ) ) {
				wp_send_json_success( $output );
			} else {
				wp_send_json_error( "Couldn't retrieve lists." );
			}
		}

		/**
		 * ajax_get_mailchimp_segments
		 */
		function ajax_get_mailchimp_segments() {
			check_ajax_referer( 'chimpbridge_ajax_nonce', 'nonce' );

			$listID = sanitize_text_field( $_POST['listID'] );

			$output = $this->get_mailchimp_segments( $listID );

			if ( empty( $output ) ) {
				wp_send_json_error( apply_filters( 'chimpbridge_msg_no_segments', __( 'Segments not available', 'chimpbridge' ) ) );
			} else if ( true && is_array( $output ) ) {
				wp_send_json_success( $output );
			} else {
				wp_send_json_error( __( 'Sorry, we had trouble getting segments.', 'chimpbridge' ) );
			}
		}

		/**
		 * ajax_refresh_mailchimp_lists
		 */
		function ajax_refresh_mailchimp_lists() {
			delete_transient( 'chimpbridge_mailchimp_lists' );
			wp_send_json_success();
		}

		/**
		 * ajax_refresh_mailchimp_segments
		 */
		function ajax_refresh_mailchimp_segments() {
			$listID = $_POST['listID'];

			delete_transient( 'chimpbridge_mailchimp_segments_' . $listID );
			wp_send_json_success();
		}

		/**
		 * build_mailchimp_campaign_request
		 */
		function build_mailchimp_campaign_request( $postID ) {
			if ( ! is_numeric( $postID ) )
				throw new Exception( 'Invalid post ID sent to build_mailchimp_campaign_request' );

			$post = get_post( $postID );

			/**
			 * Make sure we have all the information we need
			 */
			if ( empty( $_POST ) )
				return $post->ID;

			if ( ! isset( $_POST['post_type'] ) )
				return $post->ID;

			if ( 'chimpbridge' != $_POST['post_type'] )
				return $post->ID;

			// Get the post type object
			$post_type = get_post_type_object( $post->post_type );

			// If the current user is not allowed to edit, stop here
			if ( ! current_user_can( $post_type->cap->edit_post, $post->ID ) )
				$this->error_notices->add_error_notice( __( 'Sorry, you are not allowed to edit this post.', 'chimpbridge' ) );

			/**
			 * Set the subject
			 */
			$subject = $post->post_title;

			/**
			 * Grab the post meta (list, segment, etc)
			 */
			if ( $_POST['_chimpbridge_select_lists'] )
				$listID = sanitize_text_field( $_POST['_chimpbridge_select_lists'] );
			else
				$listID = sanitize_text_field( get_post_meta( $postID, '_chimpbridge_select_lists', true ) );

			if ( $_POST['_chimpbridge_select_segments'] )
				$segment = sanitize_text_field( $_POST['_chimpbridge_select_segments'] );
			else
				$segment = sanitize_text_field( get_post_meta( $postID, '_chimpbridge_select_segments', true ) );

			if ( $_POST['_chimpbridge_from_name'] )
				$from_name = sanitize_text_field( $_POST['_chimpbridge_from_name'] );
			else
				$from_name = sanitize_text_field( get_post_meta( $postID, '_chimpbridge_from_name', true ) );
			if ( $_POST['_chimpbridge_from_email'] )
				$from_email = sanitize_email( $_POST['_chimpbridge_from_email'] );
			else
				$from_email = sanitize_email( get_post_meta( $postID, '_chimpbridge_from_email', true ) );

			if ( $_POST['_chimpbridge_to_name'] )
				$to_name = sanitize_text_field( $_POST['_chimpbridge_to_name'] );
			else
				$to_name = sanitize_text_field( get_post_meta( $postID, '_chimpbridge_to_name', true ) );

			/**
			 * Get the email content
			 */
			$content = apply_filters( 'chimpbridge_content', apply_filters( 'the_content', $post->post_content ) );

			$request_payload = array(
				'type'       => 'regular',
				'recipients' => array (
					'list_id'    => $listID,
				),
				'settings' => array(
					'subject_line'    => $subject,
					'reply_to'        => $from_email,
					'from_name'       => $from_name,
					'to_name'         => $to_name,
				),
			);

			$this->debug_log( 'segment: ' . $segment );

			if ( is_numeric( $segment ) ) {
				$request_payload['recipients']['segment_opts'] = array(
					'saved_segment_id' => intval( $segment ),
				);
			}

			$this->debug_log( 'request_payload for build campaign: ' . print_r( $request_payload, true ) );

			return array(
				'payload' => $request_payload,
				'content' => $content,
			);
		}

		/**
		 * publish_chimpbridge_email
		 *
		 * Triggered on publish; send at MailChimp and update accordingly
		 */
		function publish_chimpbridge_email( $post ) {
			$this->debug_log( 'starting publish' );

			// Grab the post ID
			$postID = $post->ID;

			/**
			 * Make sure we have all the information we need
			 */
			if ( empty( $_POST ) ) {
				$this->debug_log( 'no post data' );
				return $postID;
			}

			if ( ! isset( $_POST['post_type'] ) || 'chimpbridge' != $_POST['post_type'] ) {
				$this->debug_log( 'incorrect post type or not set: ' . print_r( $_POST, true ) );
				return $postID;
			}

			// Get the post type object
			$post_type = get_post_type_object( $post->post_type );

			// If the current user is not allowed to edit, stop here
			if ( ! current_user_can( $post_type->cap->edit_post, $postID ) )
				$this->error_notices->add_error_notice( __( 'Sorry, you are not allowed to edit this post.', 'chimpbridge' ) );

			/**
			 * Update or create the campaign
			 */
			$this->debug_log( 'saving in publish' );
			$this->save_chimpbridge_email( $postID );

			/**
			 * Verify that we have the campaign ID
			 */
			if ( $cID = get_post_meta( $postID, 'chimpbridge_email_campaign_id', true ) ) {
				$this->debug_log( 'campaign ID valid, sending' );
				/**
				 * Send the campaign
				 */
				$status = $this->send_mailchimp_request( array(
					'resource' => 'campaigns/' . $cID . '/actions/send',
					'method'   => 'POST',
				) );

				// If we can't send the campaign...
				if ( false == $status ) {
					// Unpublish the post
					$this->post_to_unpublish = $postID;

					add_action( 'shutdown', array( $this, 'unpublish_due_to_error' ) );
				} else {
					do_action( 'chimpbridge_email_sent', $postID );
				}
			} else {
				$this->debug_log( 'no campaign ID!' );
			}

			return $postID;
		}

		/**
		 * Unpublish a campaign which did not, in fact, send through to MailChimp.
		 */
		function unpublish_due_to_error() {
			wp_update_post( array(
				'ID'          => $this->post_to_unpublish,
				'post_status' => 'chimpbridge_draft'
			) );
		}

		/**
		 * delete_chimpbridge_email
		 *
		 * Run some actions when an lbi_email post is deleted
		 */
		function delete_chimpbridge_email( $postID ) {
			$post_type = get_post_type( $postID );

			if ( 'chimpbridge' == $post_type ) {
				/**
				 * Delete the MailChimp campaign
				 */
				if ( $cID = get_post_meta( $postID, 'chimpbridge_email_campaign_id', true ) ) {
					$this->mailchimp_delete_campaign( $cID );
					// Delete the post meta, so we can untrash the post
					delete_post_meta( $postID, 'chimpbridge_email_campaign_id' );
				}
			}

			return;
		}

		/**
		 * save_chimpbridge_email
		 *
		 * Triggered whenever an email/campaign post is saved (but not a revision or published)
		 */
		function save_chimpbridge_post( $postID ) {
			$this->debug_log( 'starting save_chimpbridge_post' );

			// If this is just a revision, don't send the email.
			if ( wp_is_post_revision( $postID ) )
				return $postID;

			if ( 'publish' == get_post_status( $postID ) )
				return $postID;

			$this->save_chimpbridge_email( intval( $postID ) );
			$this->debug_log( 'checking for test emails ' . print_r( $_POST, true ) );
			if ( isset( $_POST['chimpbridge_email_send'], $_POST['chimpbridge_test_emails'] ) && trim( $_POST['chimpbridge_test_emails'] ) && $_POST['chimpbridge_email_send'] ) {
				// Send a test email to the given email addresses
				$this->mailchimp_send_test( $postID, sanitize_text_field( $_POST['chimpbridge_test_emails'] ) );
			}

			return $postID;
		}

		/**
		 * Save the chimpbridge email data to the post meta
		 *
		 * @param $postID
		 */
		function save_chimpbridge_email( $postID ) {
			$this->debug_log( 'starting save_chimpbridge_email' );

			/**
			 * Update the email meta attributes
			 */
			$this->save_campaign_meta( $postID );

			/**
			 * Build the campaign content
			 */
			$payload_and_content = $this->build_mailchimp_campaign_request( $postID );

			if ( ! is_array( $payload_and_content ) )
				return;

			/**
			 * Update or create new, if necessary
			 */
			if ( $cID = get_post_meta( $postID, 'chimpbridge_email_campaign_id', true ) ) {
				$this->mailchimp_update_campaign( $payload_and_content, $postID, $cID );
			} else {
				$this->mailchimp_create_campaign( $payload_and_content, $postID );
			}
		}

		/**
		 * Checks the value of a MailChimp error message and outputs an error notice to the admin as appropriate.
		 *
		 * @param array $response_data The FULL error response data from MailChimp, decoded from its JSON form.
		 */
		function throw_mailchimp_related_error( $response_data ) {
			// If, inexplicably, the response data provided is not an error, don't display any notices.
			if ( 'error' != $response_data[ 'status' ] ) {
				return;
			}

			switch ( $response_data[ 'name' ] ) {
				case 'Invalid_ApiKey':
					$this->error_notices->add_error_notice( sprintf( __( "MailChimp is saying there's an issue with the API key you've provided. Check in the %sChimpBridge settings%s to make sure that everything is alright.", 'chimpbridge' ), '<a href="' . esc_url( admin_url( $this->settings_url ) ) . '">', '</a>' ) );
					break;

				default:
					if ( defined( 'CHIMPBRIDGE_DEBUG' ) and CHIMPBRIDGE_DEBUG )
						error_log( debug_backtrace( FALSE ) );
					$this->error_notices->add_error_notice( sprintf( __( 'An error occurred while processing your request (%s). Check your fields to make sure that everything is valid. Please try again after doing so or contact %sChimpBridge support%s if this persists!', 'chimpbridge' ), $response_data[ 'name' ], '<a href="https://chimpbridge.com/contact">', '</a>' ) );
					break;
			}
		}

		/**
		 * readonly_tinymce
		 */
		function readonly_tinymce( $args ) {
			if ( 'chimpbridge' == get_post_type() && 'publish' == get_post_status() )
				$args['readonly'] = 1;

			return $args;
		}

		/**
		 * set_status
		 */
		function set_status() {
			echo '<div id="chimpbridge-post-status" data-chimpbridge-post-status="' . esc_attr( get_post_status() ) . '"></div>';
		}

		/**
		 * default_hidden_meta_boxes
		 */
		function default_hidden_meta_boxes( $hidden, $screen ) {
			$post_type = $screen->post_type;

			if ( 'chimpbridge' == $post_type ) {
				$hide_these = array(
					'authordiv',
					'revisionsdiv',
				);

				$hidden = array_merge( $hidden, $hide_these );

				return $hidden;
			}

			return $hidden;
		}

		/**
		 * Check for meta value from database, providing a default value if not found.
		 *
		 * @param int $postID
		 * @param string $field  The meta key we're searching for.
		 * @param string $default  The default value to be used if no meta value found. Make sure this is localized!
		 */
		function get_meta_with_default( $postID, $field, $default = '' ) {
			$meta_value = get_post_meta( $postID, $field, true );

			if ( $meta_value ) {
				return $meta_value;
			} else {
				return $default;
			}
		}

		/**
		 * Fetch the API key from MailChimp for WordPress, if it exists and ours isn't entered
		 */
		function api_key_from_mc4wp() {
			if ( false == $this->get_mailchimp_key() || '' == $this->get_mailchimp_key() ) {
				$mc4wp = get_option( 'mc4wp' );
				if ( is_array( $mc4wp ) && isset( $mc4wp['api_key'] ) && strlen( trim( $mc4wp['api_key'] ) ) ) {
					update_option( 'chimpbridge_mailchimp_key', sanitize_text_field( $mc4wp['api_key'] ) );
					add_action( 'admin_notices', array( $this, 'show_api_key_from_mc4wp_message' ) );
				}
			}
		}

		function show_api_key_from_mc4wp_message() {
			?>
			<div class="updated">
				<p><?php echo sprintf( esc_html__( "ChimpBridge grabbed your MailChimp API key from MailChimp for WordPress. Want to use another API key?  Change it in the %sChimpBridge settings%s.", 'chimpbridge' ), '<a href="' . esc_url( admin_url( $this->settings_url ) ) . '">', '</a>' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Nag user if the MailChimp API key isn't set.
		 */
		function api_key_nag() {
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				// avoid the nag if we're on the screen where they put in the API key
				if ( 'chimpbridge_page_chimpbridge-settings' == $screen->base )
					return;
			}

			$mailchimp_api_key = $this->get_mailchimp_key();

			if ( false == $mailchimp_api_key || '' == $mailchimp_api_key ) {
				$this->error_notices->add_error_notice( sprintf( esc_html__( "Your MailChimp API key hasn't been set! ChimpBridge needs this to work. Please set your MailChimp API key in the %sChimpBridge settings%s.", 'chimpbridge' ), '<a href="' . esc_url( admin_url( $this->settings_url ) ) . '">', '</a>' ) );
			}
		}

		/**
		 * Change the post updated messages for the ChimpBridge CPT.
		 *
		 * @param array $messages
		 * @return array
		 */
		function change_post_updated_messages( $messages ) {
			$post = get_post();

			$messages['chimpbridge'] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => esc_html__( 'Campaign updated.', 'chimpbridge' ),
				2 => esc_html__( 'Custom field updated.', 'chimpbridge' ),
				3 => esc_html__( 'Custom field deleted.', 'chimpbridge' ),
				4 => esc_html__( 'Campaign updated.', 'chimpbridge' ),
				/* translators: %s: date and time of the revision */
				5 => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Campaign restored to revision from %s', 'chimpbridge' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6 => esc_html__( 'Campaign published.', 'chimpbridge' ),
				7 => esc_html__( 'Campaign saved.', 'chimpbridge' ),
				8 => esc_html__( 'Campaign submitted.', 'chimpbridge' ),
				9 => sprintf( esc_html__( 'Campaign scheduled for: <strong>%1$s</strong>.', 'chimpbridge' ),
				   /* translators: Publish box date format, see http://php.net/date */
				   date_i18n( esc_html__( 'M j, Y @ G:i', 'chimpbridge' ), strtotime( $post->post_date ) ) ),
				10 => esc_html__( 'Campaign draft updated.', 'chimpbridge' ),
			);

			return $messages;
		}

		function chimpbridge_add_action_links( $links ) {
			$mylinks = array(
				'<a target="_blank" style="color:#3db634; font-weight: bold;" href="https://chimpbridge.com/?utm_source=plugin-list&utm_medium=upgrade-link&utm_campaign=plugin-list&utm_content=action-link">Upgrade</a>',
			);
			return array_merge( $links, $mylinks );
		}

		/**
		 * If the current post is a draft, suppress notices like "Post published." This can happen in a situation where we unpublish a post after WordPress thinks it has been published.
		 *
		 * See wp-admin/edit-form-advanced.php for the array containing these strings.
		 *
		 * @param array $messages
		 * @return array
		 */
		function suppress_post_published_notice( $messages ) {
			global $post;

			if ( 'chimpbridge' == $post->post_type && 'draft' == $post->post_status ) {
				$messages['chimpbridge'][6] = '';
			}

			return $messages;
		}

		/**
		 * mailchimp_update_campaign
		 *
		 * Delete and recreate the newsletter at MailChimp
		 */
		function mailchimp_update_campaign( $payload_and_content, $postID, $cID ) {
			$this->mailchimp_delete_campaign( $cID );
			$this->mailchimp_create_campaign( $payload_and_content, $postID );
		}

		/**
		 * mailchimp_delete_campaign
		 *
		 * Delete the MailChimp campaign
		 */
		function mailchimp_delete_campaign( $cID ) {
			/**
			 * Verify that we have the campaign ID
			 */
			if ( ! empty( $cID ) ) {
				/**
				 * Send the campaign to MailChimp
				 */
				$response = $this->send_mailchimp_request( array(
					'resource' => 'campaigns/' . $cID,
					'method'   => 'DELETE',
				) );

				/**
				 * Check to see if we succeeded or not
				 */
				if ( false == $response ) {
					$this->error_notices->add_error_notice( esc_html__( 'Sorry, we had trouble cleaning this newsletter with MailChimp. Please contact technical support.', 'chimpbridge' ) );
				}
			}
		}

		/**
		 * mailchimp_create_campaign
		 *
		 * Create a new campaign at MailChimp
		 */
		function mailchimp_create_campaign( $payload_and_content, $postID ) {
			/**
			 * Send the campaign to MailChimp
			 */
			$response = $this->send_mailchimp_request( array(
				'resource' => 'campaigns',
				'method'   => 'POST',
				'data'     => $payload_and_content['payload'],
			) );

			/**
			 * Save some data about the campaign
			 */
			if ( false != $response ) {
				// Save the campaign ID
				update_post_meta( $postID, 'chimpbridge_email_campaign_id', sanitize_text_field( $response['id'] ) );

				// Save the campaign URLs
				update_post_meta( $postID, 'chimpbridge_email_archive_url', esc_url( $response['archive_url'] ) );

				// Set the campaign content
				$this->mailchimp_set_campaign_content( $response['id'], $payload_and_content['content'] );
				update_post_meta( $postID, 'chimpbridge_scheduled_email', false );
				do_action( 'chimpbridge_after_create_campaign', $postID, $response );
			} else if ( false == $response ) {
				$this->error_notices->add_error_notice( __( 'Sorry, we had trouble creating this newsletter with MailChimp. Please contact technical support, and include the MailChimp Error, if one is available.', 'chimpbridge' ) );
			}
		}

		/**
		 * Set the content for a given MailChimp campaign.
		 *
		 * @param $cID  MailChimp campaign ID.
		 * @param $content  Content for the campaign.
		 * @return bool
		 */
		function mailchimp_set_campaign_content( $cID, $content ) {
			/**
			 * Make the request to update the content.
			 */
			$response = $this->send_mailchimp_request( array(
				'resource' => 'campaigns/' . $cID . '/content',
				'method'   => 'PUT',
				'data'     => array(
					'html' => $content,
				)
			) );

			if ( false != $response ) {
				return true;
			} else if ( false == $response ) {
				$this->error_notices->add_error_notice( 'Sorry, we had trouble setting the newsletter’s content in MailChimp. Please contact technical support, and include the MailChimp Error, if one is available.' );
			}
			return false;
		}

	}

	include 'lib/chimpbridge-error-notices.php';
	$GLOBALS['chimpbridge_class'] = new ChimpBridge();
}
