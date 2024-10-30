<?php
/**
 * Kolakube Email Forms API
 *
 * @package     KolakubeEmailForms
 * @copyright   Copyright (c) 2014, Alex Mangini
 * @license     GPL-2.0+
 * @link        https://kolakube.com/
 * @since       1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) )
	exit;


/**
 * This API is used to setup the email connection process.
 *
 * @since 1.0
 */

class kol_email_api {

	public $auth = array(
		'aweber'         => 'https://auth.aweber.com/1.0/oauth/authorize_app/e5957609',
		'mailchimp'      => 'http://admin.mailchimp.com/account/api-key-popup/',
		'activecampaign' => 'https://kolakube.com/guides/connect-email-activecampaign/',
		'convertkit'     => 'https://app.convertkit.com/account/edit'
	);


	/**
	 * Psuedo constructor, sets up admin actions and fires off
	 * scripts/AJAX.
	 *
	 * @since 1.0
	 */

	public function __construct() {
		$this->_id         = 'kol_email';
		$this->_get_option = get_option( $this->_id );

		add_action( 'admin_print_footer_scripts', array( $this, 'admin_footer_scripts' ) );

		add_action( 'wp_ajax_connect', array( $this, 'connect' ) );
		add_action( 'wp_ajax_disconnect', array( $this, 'disconnect' ) );
	}


	/**
	 * Loads required scripts into admin footer.
	 *
	 * @since 1.0
	 */

	public function admin_footer_scripts() {
		global $kol_email;

		$screen = get_current_screen();

		if ( $screen->base != $kol_email->admin->_add_page )
			return;

		$data = get_option( 'kol_email_data' );

		if ( ! empty( $data ) )
			$this->connect_scripts();
		else
			$this->disconnect_scripts();
	}


	/**
	 * Scripts called on the connected screen.
	 *
	 * @since 1.0
	 */

	public function connect_scripts() {
		global $kol_email;
	?>

		<script>

			jQuery( document ).ready( function( $ ) {

				$( '#kol-email-disconnect' ).on( 'click', function( e ) {

					e.preventDefault();

					if ( ! confirm( '<?php echo $kol_email->strings['disconnect_notice']; ?>' ) )
						return;

					$( '#kol-email-disconnect' ).prop( 'disabled', true );

					$.post( ajaxurl, { action: 'disconnect', form: $( '#kol-email-form' ).serialize() }, function( disconnected ) {
						$( '#kol-email-list' ).html( disconnected );
					});

				});

			});

		</script>

	<?php }


	/**
	 * Scripts called on the disconnect screen.
	 *
	 * @since 1.0
	 */

	public function disconnect_scripts() { ?>

		<script>

			jQuery( document ).ready( function( $ ) {

				$( '#kol-email-connect' ).on( 'click', function( e ) {

					e.preventDefault();

					$( '#kol-email-connect' ).prop( 'disabled', true );

					$.post( ajaxurl, { action: 'connect', form: $( '#kol-email-form' ).serialize() }, function( connected ) {
						$( '#kol-email-list' ).html( connected );
					});

				});

			});

			// Show API key input

			( function() {
				document.getElementById( 'kol_email_service' ).onchange = function( e ) {
					document.getElementById( 'kol-email-steps' ).style.display = this.value !== '' ? 'block' : 'none';
					document.getElementById( 'kol-email-auth' ).style.display  = this.value !== 'custom_code' ? 'block' : 'none';
					document.getElementById( 'kol-email-auth-btn' ).href       = this.value == 'aweber' ? '<?php echo $this->auth['aweber']; ?>' : this.value == 'mailchimp' ? '<?php echo $this->auth['mailchimp']; ?>' : this.value == 'activecampaign' ? '<?php echo $this->auth['activecampaign']; ?>' : '<?php echo $this->auth['convertkit']; ?>';
					document.getElementById( 'kol-email-url' ).style.display   = this.value == 'activecampaign' ? 'table-row' : 'none';
				}
			})();

		</script>

	<?php }


	/**
	 * This method loads and connects to the specified email service
	 * API and also builds out a carefully constructed data array
	 * for use in email forms.
	 *
	 * use data array: get_option( 'kol_email_data' );
	 *
	 * @since 1.0
	 */

	public function connect() {
		global $kol_email;

		$data = array();

		parse_str( stripslashes( $_POST['form'] ), $form );

		$api_key = $form[$this->_id]['api_key']; // api key is NOT saved to database, only used on AJAX connection. save in future versions.
		$api_url = $form[$this->_id]['api_url']; // api url is NOT saved to database, only used on AJAX connection. save in future versions.

		if ( ! wp_verify_nonce( $form['_wpnonce'], "{$this->_id}-options" ) ) // use native nonce
			die ( $kol_email->strings['nonce_error'] );

		$service         = $form[$this->_id]['service'];
		$data['service'] = $service;

		if ( ! empty( $api_key ) )
			if ( $service == 'aweber' )
				$this->aweber_connect( $api_key, $data );
			elseif ( $service == 'mailchimp' )
				$this->mailchimp_connect( $api_key, $data );
			elseif ( $service == 'activecampaign' )
				$this->activecampaign_connect( $api_key, $api_url, $data );
			elseif ( $service == 'convertkit' )
				$this->convertkit_connect( $api_key, $data );

		if ( $service == 'custom_code' )
			$this->custom_code_connect( $data );

		wp_cache_flush();

		$this->connect_scripts();
		$kol_email->admin->admin_fields();

		exit;
	}


	/**
	 * Disconnect from email service by deleting all data.
	 *
	 * @since 1.0
	 */

	public function disconnect() {
		global $kol_email;

		// delete email data
		delete_option( 'kol_email_data' );

		// redirect to admin page after connection
		$this->disconnect_scripts();
		$kol_email->admin->admin_fields();

		exit;
	}


	/**
	 * Load MailChimp API and structure data array.
	 *
	 * @since 1.0
	 */

	public function mailchimp_connect( $api_key, $data ) {
		include_once( KOL_EMAIL_DIR . 'api/services/mailchimp.php' );

		$api       = new kol_email_mailchimp( $api_key );
		$get_lists = $api->lists();

		if ( ! is_array( $get_lists['data'] ) )
			$this->error();

		foreach ( $get_lists['data'] as $list ) {
			$id   = esc_attr( $list['id'] );
			$name = esc_attr( $list['name'] );

			$data['lists'][$id] = array(
				'name' => $name,
				'url'  => esc_url_raw( $list['subscribe_url_long'] ),
			);
		}

		update_option( 'kol_email_data', $data );
	}


	/**
	 * Load AWeber API and structure data array.
	 *
	 * @since 1.0
	 */

	public function aweber_connect( $api_key, $data ) {
		require_once( KOL_EMAIL_DIR . 'api/services/aweber/aweber.php' );

		$keys = array();

		try {
			list( $keys['consumer_key'], $keys['consumer_secret'], $keys['access_key'], $keys['access_secret'] ) = AWeberAPI::getDataFromAweberID( $api_key );
		}
		catch( AWeberAPIException $e ) {
			$this->error();
		}

		$aweber  = new AWeberAPI( $keys['consumer_key'], $keys['consumer_secret'] );
		$account = $aweber->getAccount( $keys['access_key'], $keys['access_secret'] );

		foreach ( $account->lists->data['entries'] as $list ) {
			$id   = esc_attr( $list['unique_list_id'] );
			$name = esc_attr( $list['name'] );

			$data['lists'][$id] = array(
				'name' => $name
			);
		}

		update_option( 'kol_email_data', $data );
	}


	/**
	 * Load ActiveCampaign API and structure data array.
	 *
	 * @since 1.1
	 */

	public function activecampaign_connect( $api_key, $api_url, $data ) {
		// load activecampaign API
		require_once( KOL_EMAIL_DIR . 'api/services/activecampaign/ActiveCampaign.class.php' );

		$ac      = new ActiveCampaign( $api_url, $api_key );
		$account = $ac->api( 'account/view' );
		$forms   = $ac->api( 'form/getforms' );

		if ( ! $account->result_code || ! $forms->result_code ) // if no connection, stop process and display error
			$this->error();

		// loop through API data and structure options array
		foreach ( $forms as $key => $list )
			if ( is_numeric( $key ) ) {
				$id   = esc_attr( $list->id );
				$name = esc_attr( $list->name );

				$data['lists'][$id] = array(
					'name'  => $name,
					'url'   => esc_url_raw( "{$account->account}/proc.php" ),
					'lists' => $list->lists[0]
				);
			}

		// save structured data to WP database
		update_option( 'kol_email_data', $data );
	}


	/**
	 * Load ConvertKit API and structure data array.
	 *
	 * @since 1.1
	 */

	public function convertkit_connect( $api_key, $data ) {
		$json = file_get_contents( "https://api.convertkit.com/v3/forms?api_key=$api_key" );

		if ( empty( $json ) )
			$this->error();

		$api_data = json_decode( $json );

		foreach ( $api_data->forms as $form ) {
			$id   = esc_attr( $form->id );
			$name = esc_attr( $form->name );

			$data['lists'][$id]  = array(
				'name' => $name,
				'url'  => esc_url_raw( $form->url )
			);
		}

		// save structured data to WP database
		update_option( 'kol_email_data', $data );
	}


	/**
	 * Structures data array form Custom Email Forms.
	 *
	 * @since 1.1
	 */

	public function custom_code_connect( $data ) {
		// just add service to this array, passed from $this->connect()
		update_option( 'kol_email_data', $data );
	}


 	/**
	 * Show error message if unable to connect to anything.
	 *
	 * @since 1.0
	 */

	public function error() {
		global $kol_email;

		wp_die( $kol_email->strings['auth_code_error'] );
	}

}
