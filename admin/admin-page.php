<?php
/**
 * Kolakube Email Forms Admin Page
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
 * This class builds the admin page, which is added to
 * Dashboard > Tools > Email Forms and holds the connection
 * process to any email service.
 *
 * @since 1.0
 */

class kol_email_admin {

	public $_id;
	public $_add_page;
	public $_name;

	/**
	 * Fire admin hooks and setup important properties.
	 *
	 * @since 1.0
	 */

	public function __construct() {
		$this->_id         = 'kol_email';
		$this->_get_option = get_option( $this->_id );

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_print_styles', array( $this, 'admin_css' ) );
	}


	/**
	 * Add admin page to WP-Admin.
	 *
	 * @since 1.0
	 */

	public function add_menu() {
		global $kol_email;

		$this->_add_page = add_submenu_page( 'tools.php', $kol_email->strings['name'], $kol_email->strings['menu_title'], 'manage_options', 'kol-email-forms', array( $this, 'admin_page' ) );
	}


	/**
	 * Print admin CSS to our page. I just couldn't justify
	 * another HTTP request for such minimal CSS.
	 *
	 * @since 1.1
	 */

	public function admin_css() {
		$screen = get_current_screen();

		if ( $screen->base != $this->_add_page )
			return;
	?>

		<style type="text/css">
			.kol-hr {
				margin-bottom: 20px;
				margin-top: 20px;
			}
			.kol-icon-yes {
				background-color: #30a146;
				border-radius: 50%;
				color: #fff;
				margin-right: 10px;
			}
			.kol-footer-text {
				color: #777;
				font-size: 12px;
				font-style: italic;
			}
		</style>

	<?php }


	/**
	 * The HTML frame for the Email Forms admin page.
	 *
	 * @since 1.0
	 */

	public function admin_page() {
		global $kol_email;
	?>

		<div class="wrap kol">

			<h2><?php echo $kol_email->strings['name']; ?> <span class="title-count theme-count"><?php echo KOL_EMAIL_VERSION; ?></span></h2>

			<hr class="kol-hr" />

			<form id="kol-email-form" method="post" action="options.php">

				<?php settings_fields( $this->_id ); ?>

				<?php $this->admin_fields(); ?>

			</form>

		</div>

	<?php }


	/**
	 * The email forms connection/disconnection process.
	 *
	 * @since 1.0
	 */

	public function admin_fields() {
		global $kol_email;

		$data    = get_option( 'kol_email_data' );
		$service = $data['service'];

		if ( $service == 'aweber' )
			$connected = $kol_email->strings['aweber'];
		elseif ( $service == 'mailchimp' )
			$connected = $kol_email->strings['mailchimp'];
		elseif ( $service == 'activecampaign' )
			$connected = $kol_email->strings['activecampaign'];
		elseif ( $service == 'convertkit' )
			$connected = $kol_email->strings['convertkit'];
		else
			$connected = $kol_email->strings['custom_code'];
	?>

		<div id="kol-email-list">

			<?php if ( ! empty( $data ) ) : ?>

				<!-- Connection Success -->

				<p><i class="dashicons dashicons-yes kol-icon-yes"></i> <?php echo $kol_email->strings['connected_to'] . " <b>$connected</b>" . $kol_email->strings['connected_widget']; ?></p>

				<!-- Disconnect button -->

				<?php submit_button( $kol_email->strings['disconnect'], 'secondary', 'kol-email-disconnect' ); ?>

				<!-- Footer Text -->

				<hr class="kol-hr" />

				<div class="kol-footer-text">
					<p><?php echo $kol_email->strings['review']; ?></p>
					<p><?php echo $kol_email->strings['md']; ?></p>
				</div>

			<?php else :
				$custom      = $service == 'custom_code' ? true : false;
				$custom_icon = $custom ? 'yes' : 'no';

				$services = array(
					''               => $kol_email->strings['select_service'],
					'mailchimp'      => $kol_email->strings['mailchimp'],
					'aweber'         => $kol_email->strings['aweber'],
					'activecampaign' => $kol_email->strings['activecampaign'],
					'convertkit'     => $kol_email->strings['convertkit'],
					'aweber'         => $kol_email->strings['aweber'],
					'custom_code'    => $kol_email->strings['custom_code']
				);

				$api_url = isset( $this->_get_option['api_url'] ) ? $this->_get_option['api_url'] : '';
				$api_key = isset( $this->_get_option['api_key'] ) ? $this->_get_option['api_key'] : '';
			?>

				<!-- Setup -->

				<div class="kol-email-setup" style="display: <?php echo empty( $lists ) ? 'block' : 'none'; ?>; max-width: 700px;">

					<!-- Select Service -->

					<div id="kol-email-service">

						<h3><?php echo $kol_email->strings['step1']; ?></h3>

						<p><?php echo $kol_email->strings['connect_notice']; ?></p>

						<select name="kol_email[service]" id="kol_email_service">
							<?php foreach ( $services as $val => $label ) : ?>
								<option value="<?php esc_attr_e( $val ); ?>"><?php esc_html_e( $label ); ?></option>
							<?php endforeach; ?>
						</select>

					</div>

					<!-- API Authorization -->

					<div id="kol-email-steps" style="display: none;">

						<div id="kol-email-auth" style="display: none;">

							<hr class="kol-hr" />

							<!-- Step 2 -->

							<h3><?php echo $kol_email->strings['step2']; ?></h3>

							<p><a href="#" id="kol-email-auth-btn" class="button" target="_blank"><?php echo $kol_email->strings['get_auth_code']; ?></a></p>

							<p class="description"><?php echo $kol_email->strings['get_auth_code_notice']; ?></p>

							<!-- API URL -->

							<table id="kol-email-url" class="form-table" style="display: none;">
								<tbody>

									<tr>
										<th scope="row"><label for="kol_email_api_url"><?php _e( 'API URL', 'kol-email-fors' ); ?></label></th>

										<td>
											<input type="text" name="kol_email[api_url]" id="kol_email_api_url" class="regular-text" value="<?php echo sanitize_text_field( $api_url ); ?>" />
										</td>
									</tr>

								</tbody>
							</table>

							<!-- API Key -->

							<table id="kol-email-key" class="form-table">
								<tbody>

									<tr>
										<th scope="row"><label for="kol_email_api_key"><?php _e( 'API Key', 'kol-email-forms' ); ?></label></th>

										<td>
											<textarea name="kol_email[api_key]" id="kol_email_api_key" class="large-text" rows="6"><?php echo esc_textarea( $api_key ); ?></textarea>
										</td>
									</tr>

								</tbody>
							</table>

						</div>

						<!-- Connect button -->

						<?php submit_button( $kol_email->strings['connect'], 'primary', 'kol-email-connect' ); ?>

					</div>

				</div>

			<?php endif; ?>

		</div>

	<?php }
}