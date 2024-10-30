<?php
/**
 * Plugin Name: Kolakube Email Forms
 * Plugin URI: https://kolakube.com/email-forms-wordpress/
 * Description: Easily connect to an email service (or use custom HTML) to display email signup forms throughout your website with a simple widget.
 * Version: 1.1.1
 * Author: Alex Mangini
 * Author URI: https://kolakube.com/about/
 * Author email: alex@kolakube.com
 * License: GPL-2.0+
 * Requires at least: 3.8
 * Tested up to: 4.5.3
 * Text Domain: kol-email-forms
 * Domain Path: /languages/
 *
 * This plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see http://www.gnu.org/licenses/.
 *
 * @package     KolakubeEmailForms
 * @copyright   Copyright (c) 2014, Alex Mangini
 * @license     GPL-2.0+
 * @link        https://kolakube.com/
 * @since       1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class kol_email_forms {

	public $strings;
	public $api;
	public $admin;

	/**
	 * To load plugin, call this method.
	 *
	 * @since 1.0
	 */

	public function init() {
		$this->constants();
		$this->strings = $this->strings();

		load_plugin_textdomain( 'kol-email-forms', false, KOL_EMAIL_DIR . 'languages/' );

		require_once( KOL_EMAIL_DIR . 'api/api.php' );
		require_once( KOL_EMAIL_DIR . 'api/updater.php' );
		require_once( KOL_EMAIL_DIR . 'admin/admin-page.php' );
		require_once( KOL_EMAIL_DIR . 'widget.php' );

		$this->api   = new kol_email_api;
		$this->admin = new kol_email_admin;

		$email_data = get_option( 'kol_email_data' );

		// run updater if old data structure is found
		if ( ! empty( $email_data ) && array_key_exists( 'save', $email_data ) ) {
			$this->updater = new kol_email_updater;
			$this->updater->updater_11();
		}

		add_action( 'widgets_init', array( $this, 'widgets' ) );
	}


	/**
	 * Set constants for useful data used throughout plugin.
	 *
	 * @since 1.0
	 */

	private function constants() {
		define( 'KOL_EMAIL_VERSION', '1.1.1' );
		define( 'KOL_EMAIL_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'KOL_EMAIL_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
	}


	/**
	 * Register the Email Form Widget.
	 *
	 * @since 1.0
	 */

	public function widgets() {
		register_widget( 'kol_email_form' );
	}


	/**
	 * All strings used throughout the interface.
	 *
	 * @since 1.0
	 */

	public function strings() {
		return array(
			'name'                         => __( 'Kolakube Email Forms', 'kol-email-forms' ),
			'menu_title'                   => __( 'Email Forms', 'kol-email-forms' ),
			'before_use'                   => sprintf( __( 'To use the Kolakube Email Form widget, first <a href="%s">connect to an email service</a>.', 'kol-email-forms' ), admin_url( 'tools.php?page=kol-email-forms' ) ),
			'select_list'                  => __( 'Select a List&hellip;', 'kol-email-forms' ),
			'select_page'                  => __( 'Select a Page&hellip;', 'kol-email-forms' ),
			'step1'                        => __( 'Step 1: Connect to Your Email Service', 'kol-email-forms' ),
			'step2'                        => __( 'Step 2: Get Your Authorization Code', 'kol-email-forms' ),
			'connect_notice'               => __( 'To insert email forms on your site, choose your email service provider below:', 'kol-email-forms' ),
			'connected_widget'             => sprintf( __( ', drag the <a href="%s">Email Signup Form</a> widget to display an email form on your site.', 'kol-email-forms' ), admin_url( 'widgets.php' ) ),
			'select_service'               => __( 'Select an email service&hellip;', 'kol-email-forms' ),
			'mailchimp'                    => __( 'MailChimp', 'kol-email-forms' ),
			'aweber'                       => __( 'AWeber', 'kol-email-forms' ),
			'activecampaign'               => __( 'ActiveCampaign', 'kol-email-forms' ),
			'convertkit'                   => __( 'ConvertKit', 'kol-email-forms' ),
			'custom_code'                  => __( 'Custom HTML Form Code', 'kol-email-forms' ),
			'auth_code_error'              => __( 'Could not connect to your email service. Your Authorization code may be incorrect. Reload the page and try again.<br /><br /><b>A few things to check for:</b><br /><br />1) Make sure you have at least one email list created in your email service provider <em>before</em> starting the connection process.<br /> 2) Make sure you have an Internet connection<br />3) Make sure you didn\'t accidentally paste a blank space or blank line at the end of your authorization code.', 'kol-email-forms' ),
			'get_auth_code'                => __( 'Get Authorization Code', 'kol-email-forms' ),
			'get_auth_code_notice'         => __( 'Click the "Get Authorization Code" button above and login to your account and get your authorization code. Once you have it, copy it and paste it into the text field below and click "Connect."', 'kol-email-forms' ),
			'connect'                      => __( 'Connect', 'kol-email-forms' ),
			'disconnect'                   => __( 'Disconnect', 'kol-email-forms' ),
			'disconnect_notice'            => __( 'Disconnecting your email service will set your form settings back to default. All email forms on your website will be deleted until you reconnect to an email service. Are you sure you want to continue?', 'kol-email-forms' ),
			'connected_to'                 => __( 'You are connected to', 'kol-email-forms' ),
			'nonce_error'                  => __( 'Sorry, your nonce did not verify.', 'kol-email-forms' ),
			'select_display'               => __( 'Show on active widget area', 'kol-email-forms' ),
			'submission_error'             => __( 'There was an error submitting your subscription. Please try again.', 'kol-email-forms' ),
			'submission_success'           => __( 'Success! Now check your email to confirm your subscription.', 'kol-email-forms' ),
			'widget_name'                  => __( 'Email Signup Form', 'kol-email-forms' ),
			'widget_description'           => __( 'Display a simple email form connected to your email service provider (Tools > Email Forms).', 'kol-email-forms' ),
			'widget_after_form_text'       => __( 'After Form Text', 'kol-email-forms' ),
			'widget_title'                 => __( 'Title', 'kol-email-forms' ),
			'widget_desc'                  => __( 'Description', 'kol-email-forms' ),
			'widget_display'               => __( 'Display', 'kol-email-forms' ),
			'widget_custom_code'           => __( 'Custom HTML Code', 'kol-email-forms' ),
			'widget_list'                  => __( 'Email List', 'kol-email-forms' ),
			'widget_field_name'            => __( 'Ask for subscribers name in signup form', 'kol-email-forms' ),
			'widget_name_label'            => __( 'Name Field Label', 'kol-email-forms' ),
			'widget_email_label'           => __( 'Email Field Label', 'kol-email-forms' ),
			'widget_button_text'           => __( 'Submit Button Text', 'kol-email-forms' ),
			'widget_form_id'               => __( 'Form ID', 'kol-email-forms' ),
			'widget_ad_tracking'           => __( 'Ad Tracking', 'kol-email-forms' ),
			'widget_image_id'              => __( 'Image ID', 'kol-email-forms' ),
			'widget_classes'               => __( 'HTML Classes', 'kol-email-forms' ),
			'thank_you_page'               => __( 'Thank You Page', 'kol-email-forms' ),
			'already_subs_page'            => __( 'Already Subscribed Page', 'kol-email-forms' ),
			'name_label'                   => __( 'Enter your name&hellip;', 'kol-email-forms' ),
			'email_label'                  => __( 'Enter your email&hellip;', 'kol-email-forms' ),
			'button_text'                  => __( 'Get Instant Access', 'kol-email-forms' ),
			'input_fields'                 => __( 'Input Fields', 'kol-email-forms' ),
			'tracking_management'          => __( 'Tracking &amp; Management', 'kol-email-forms' ),
			'advanced'                     => __( 'Advanced', 'kol-email-forms' ),
			'blog_only'                    => __( 'Show on blog homepage only', 'kol-email-forms' ),
			'posts_only'                   => __( 'Show on single posts only', 'kol-email-forms' ),
			'review'                       => sprintf( __( 'Loving Kolakube Email Forms? Please support the plugin and take a moment to <a href="%s" target="_blank">leave a 5 star review</a>!', 'kol-email-forms' ), 'https://wordpress.org/support/view/plugin-reviews/kolakube-email-forms?rate=5#postform' ),
			'md'                           => sprintf( __( 'Unlock more email form power&mdash;including design options and Page Leads&mdash;with <a href="%s" target="_blank">Marketers Delight</a>.', 'kol-email-forms' ), 'https://marketersdelight.net/' )
		);
	}

}

$kol_email = new kol_email_forms;
$kol_email->init();