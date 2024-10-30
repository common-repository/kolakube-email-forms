<?php
/**
 * Kolakube Email Forms Widget
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
 * Build out the Widget admin interface, save/sanitization methods,
 * and load HTML for the Email Form.
 *
 * @since 1.0
 */

class kol_email_form extends WP_Widget {

	public $_allowed_html = array(
		'p' => array(
			'class' => array(),
			'id'    => array()
		),
		'span' => array(
			'class'  => array(),
			'id'     => array()
		),
		'a' => array(
			'href'   => array(),
			'class'  => array(),
			'id'     => array()
		),
		'img' => array(
			'src'    => array(),
			'alt'    => array(),
			'height' => array(),
			'width'  => array(),
			'class'  => array(),
			'id'     => array()
		),
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'br'     => array(),
		's'      => array()
	);


	/**
	 * Setup the Widget.
	 *
	 * @since 1.0
	 */

	public function __construct() {
		global $kol_email;

		parent::__construct( 'kol_email', $kol_email->strings['widget_name'], array(
			'description' => $kol_email->strings['widget_description'],
		) );

		$this->email_data    = get_option( 'kol_email_data' );
		$this->email_service = $this->email_data['service'];
		$this->email_lists   = '';

		if ( ! empty( $this->email_data['lists'] ) )
			foreach ( $this->email_data['lists'] as $id => $fields )
				$this->email_lists[] = $id;

		if ( $this->email_service == 'convertkit' )
			add_action( 'wp_enqueue_scripts', array( $this, 'footer_scripts' ) );
	}


	/**
	 * Load any needed scripts to the the footer.
	 *
	 * @since 1.1
	 */

	public function footer_scripts() {
		wp_enqueue_script( 'kol-email-forms-convertkit', '//app.convertkit.com/assets/CKJS4.js?v=18', array(), false, true );
	}


	/**
	 * The widget() method loads the email form HTML to the frontend
	 * and also hooks the widget options up. In this method, we set the
	 * required variables based on 1) widget options 2) email data and
	 * include() the email form template. from the /templates/ folder.
	 *
	 * To edit the widget HTML, copy the contents of /templates/email-form.php
	 * to your child theme in a matching template path.
	 *
	 * @since 1.0
	 */

	public function widget( $args, $val ) {
		$email_data    = $this->email_data;
		$email_service = $this->email_service;
		$email_lists   = $this->email_lists;

		$list    = $val['list'];
		$display = ! empty( $val['display'] ) ? $val['display'] : '';

		// prevent template from loading if any of the following conditions are met
		if (
			empty( $email_data )                                                 || // not connected to email service
			$email_service != 'custom_code' && ! in_array( $list, $email_lists ) || // in case of service switch, ensure list ID matches new email data
			( $display == 'blog' && ! is_home() )                                || // if set to show on blog only, don't show elsewhere
			( $display == 'posts' && ! is_single() )   // if set to show on posts only, don't show elsewhere
		)
			return;

		global $kol_email;

		$title           = $val['title'];
		$desc            = $val['desc'];
		$after_form_text = ! empty( $val['after_form_text'] ) ? $val['after_form_text'] : '';
		$field_name      = $val['form_fields_name'];

		$label_name  = ! empty( $val['name_label'] ) ? esc_attr( $val['name_label'] ) : $kol_email->strings['name_label'];
		$label_email = ! empty( $val['email_label'] ) ? esc_attr( $val['email_label'] ) : $kol_email->strings['email_label'];
		$button_text = ! empty( $val['button_text'] ) ? esc_attr( $val['button_text'] ) : $kol_email->strings['button_text'];

		$classes = ! empty( $val['classes'] ) ? esc_attr( $val['classes'] ) : '';


		if ( $email_service != 'custom_code' ) {

			$lists = $email_data['lists'];

			if ( $email_service == 'aweber' ) {
				$action    = '//www.aweber.com/scripts/addlead.pl';
				$att_name  = 'name';
				$att_email = 'email';

				$image        = $val['tracking_image'];
				$form_id      = $val['form_id'];
				$thank_you    = $val['thank_you'];
				$already_subs = $val['already_subscribed'];
				$ad_tracking  = $val['ad_tracking'];
			}
			elseif ( $email_service == 'mailchimp' ) {
				$lists_data = parse_url( $lists[$list]['url'] ); // convert URL params to array
				parse_str( $lists_data['query'] ); // convert query params to string vars (creates variables $u and $id)

				$action    = esc_url_raw( '//' . $lists_data['host'] . '/subscribe/post/' );
				$att_name  = 'MERGE1';
				$att_email = 'MERGE0';
			}
			elseif ( $email_service == 'activecampaign' ) {
				$list_id = $lists[$list];

				$action    = $list_id['url'];
				$att_name  = 'fullname';
				$att_email = 'email';
				$nlbox     = $list_id['lists'];
			}
			elseif ( $email_service == 'convertkit' ) {
				$list_id = $lists[$list];

				$action    = $list_id['url'] . '/subscribe/';
				$att_name  = 'first_name';
				$att_email = 'email';
			}

		}

		// load template

		$path = 'templates/email-form.php';

		if ( $template = locate_template( $path ) )
			include( $template );
		else
			include( KOL_EMAIL_DIR . $path );
	}


	/**
	 * Run validation methods on save options to ensure we
	 * only save safe data.
	 *
	 * @since 1.0
	 */

	public function update( $new, $val ) {
		// text fields
		foreach ( array( 'title', 'desc', 'after_form_text' ) as $text_field )
			$val[$text_field] = wp_kses( $new[$text_field], $this->_allowed_html );

		$val['list'] = in_array( $new['list'], $this->email_lists ) ? $new['list'] : '';

		// checkboxes
		foreach ( array( 'form_fields_name' ) as $check_field )
			$val[$check_field] = $new[$check_field] ? 1 : 0;

		// texts
		foreach ( array( 'name_label', 'email_label', 'button_text', 'form_id', 'ad_tracking', 'tracking_image' ) as $texts_field )
			$val[$texts_field] = sanitize_text_field( $new[$texts_field] );

		// pages
		foreach ( get_pages() as $p )
			$pages[] = $p->ID;

		foreach ( array( 'thank_you', 'already_subscribed' ) as $pages_field )
			$val[$pages_field] = in_array( $new[$pages_field], $pages ) ? $new[$pages_field] : '';

		// display
		$val['display'] = in_array( $new['display'], array( 'blog', 'posts' ) ) ? $new['display'] : '';

		// classes
		$val['classes'] = strip_tags( $new['classes'] );

		// form code
		$val['custom_code'] = $new['custom_code'];

		return $val;
	}


	/**
	 * Build the Widget options settings.
	 *
	 * @since 1.0
	 */

	public function form( $val ) {
		global $kol_email;

		// set default values
		$val = wp_parse_args( (array) $val, array(
			'title'              => '',
			'desc'               => '',
			'after_form_text'    => '',
			'list'               => '',
			'form_fields_name'   => '',
			'name_label'         => '',
			'email_label'        => '',
			'button_text'        => '',
			'thank_you'          => '',
			'already_subscribed' => '',
			'form_id'            => '',
			'ad_tracking'        => '',
			'tracking_image'     => '',
			'custom_code'        => '',
			'classes'            => '',
			'display'            => ''
		) );
	?>

		<?php if ( ! empty( $this->email_data ) ) : ?>

			<?php if ( $this->email_service != 'custom_code' ) : ?>

				<!-- Select List -->

				<p>
					<label for="<?php echo $this->get_field_id( 'list' ); ?>"><?php echo $kol_email->strings['widget_list']; ?>:</label><br />

					<select id="<?php echo $this->get_field_id( 'list' ); ?>" name="<?php echo $this->get_field_name( 'list' ); ?>" style="max-width: 100%;">
						<option value=""><?php echo $kol_email->strings['select_list']; ?></option>

						<?php foreach ( $this->email_data['lists'] as $id => $fields ) : ?>
							<option value="<?php esc_attr_e( $id ); ?>" <?php selected( $val['list'], $id, true ); ?>><?php echo $fields['name']; ?></option>
						<?php endforeach; ?>
					</select>
				</p>

			<?php endif; ?>

			<!-- List Options -->

			<div id="<?php echo $this->get_field_id( 'kol-email' ); ?>-form" style="display: <?php echo ! empty( $val['list'] ) || $this->email_service == 'custom_code' ? 'block' : 'none'; ?>">

				<!-- Title -->

				<p>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php echo $kol_email->strings['widget_title']; ?>:</label>

					<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php esc_attr_e( $val['title'] ); ?>" class="widefat" />
				</p>

				<!-- Description -->

				<p>
					<label for="<?php echo $this->get_field_id( 'desc' ); ?>"><?php echo $kol_email->strings['widget_desc']; ?>:</label>

					<textarea id="<?php echo $this->get_field_id( 'desc' ); ?>" name="<?php echo $this->get_field_name( 'desc' ); ?>" class="widefat" rows="4"><?php printf( '%s', esc_textarea( $val['desc'] ) ); ?></textarea>
				</p>

				<!-- After Form Text -->

				<p>
					<label for="<?php echo $this->get_field_id( 'after_form_text' ); ?>"><?php echo $kol_email->strings['widget_after_form_text']; ?>:</label>

					<textarea id="<?php echo $this->get_field_id( 'after_form_text' ); ?>" name="<?php echo $this->get_field_name( 'after_form_text' ); ?>" class="widefat" rows="3"><?php printf( '%s', esc_textarea( $val['after_form_text'] ) ); ?></textarea>
				</p>

				<!-- Email Data Type -->

				<?php if ( $this->email_service != 'custom_code' ) : ?>

					<!-- AWeber -->

					<?php if ( $this->email_service == 'aweber' ) :
						$pages = array();

						foreach ( get_pages() as $p )
							$pages[$p->ID] = $p->post_title;

						$select = array(
							'thank_you' => array(
								'label' => $kol_email->strings['thank_you_page'],
							),
							'already_subscribed' => array(
								'label' => $kol_email->strings['already_subs_page'],
							)
						);
					?>

						<h4><?php echo $kol_email->strings['tracking_management']; ?></h4>

						<!-- Form ID, Ad Tracking, Image ID -->

						<?php $tracking = array(
							'form_id' => array(
								'label' => $kol_email->strings['widget_form_id'],
							),
							'ad_tracking' => array(
								'label' => $kol_email->strings['widget_ad_tracking'],
							),
							'tracking_image' => array(
								'label' => $kol_email->strings['widget_image_id'],
						) ); ?>

						<?php foreach ( $tracking as $tracking_id => $tracking_field ) : ?>

							<p>
								<label for="<?php echo $this->get_field_id( $tracking_id ); ?>"><?php echo $tracking_field['label']; ?>:</label>

								<input type="text" id="<?php echo $this->get_field_id( $tracking_id ); ?>" name="<?php echo $this->get_field_name( $tracking_id ); ?>" value="<?php esc_attr_e( $val[$tracking_id] ); ?>" class="widefat" />
							</p>

						<?php endforeach; ?>

						<!-- Thank You / Already Subscribed Pages -->

						<?php foreach ( $select as $select_id => $select_field ) : ?>

							<p>
								<label for="<?php echo $this->get_field_id( $select_id ); ?>"><?php echo $select_field['label']; ?>:</label><br />

								<select id="<?php echo $this->get_field_id( $select_id ); ?>" name="<?php echo $this->get_field_name( $select_id ); ?>" style="max-width: 100%;">
									<option value=""><?php echo $kol_email->strings['select_page']; ?></option>

									<?php foreach ( $pages as $page_id => $page_name ) : ?>
										<option value="<?php esc_attr_e( $page_id ); ?>" <?php selected( $val[$select_id], $page_id, true ); ?>><?php echo $page_name; ?></option>
									<?php endforeach; ?>
								</select>
							</p>

						<?php endforeach; ?>

					<?php endif; ?>

					<!-- Input Fields -->

					<h4><?php echo $kol_email->strings['input_fields']; ?></h4>

					<!-- Show Name Field -->

					<p>
						<input id="<?php echo $this->get_field_id( 'form_fields_name' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'form_fields_name' ); ?>" value="1" <?php checked( $val['form_fields_name'] ); ?> />

						<label for="<?php echo $this->get_field_id( 'form_fields_name' ); ?>"><?php echo $kol_email->strings['widget_field_name']; ?></label>
					</p>

					<!-- Name Label -->

					<div id="<?php echo $this->get_field_id( 'name_label' ); ?>-field" style="display: <?php echo $val['form_fields_name'] ? 'block' : 'none'; ?>">

						<label for="<?php echo $this->get_field_id( 'name_label' ); ?>"><?php echo $kol_email->strings['widget_name_label']; ?>:</label>

						<input type="text" id="<?php echo $this->get_field_id( 'name_label' ); ?>" name="<?php echo $this->get_field_name( 'name_label' ); ?>" value="<?php esc_attr_e( $val['name_label'] ); ?>" placeholder="<?php echo $kol_email->strings['name_label']; ?>" class="widefat" />

					</div>

					<!-- Email Label -->

					<p>
						<label for="<?php echo $this->get_field_id( 'email_label' ); ?>"><?php echo $kol_email->strings['widget_email_label']; ?>:</label>

						<input type="text" id="<?php echo $this->get_field_id( 'email_label' ); ?>" name="<?php echo $this->get_field_name( 'email_label' ); ?>" value="<?php esc_attr_e( $val['email_label'] ); ?>" placeholder="<?php echo $kol_email->strings['email_label']; ?>" class="widefat" />
					</p>

					<!-- Button Text -->

					<p>
						<label for="<?php echo $this->get_field_id( 'button_text' ); ?>"><?php echo $kol_email->strings['widget_button_text']; ?>:</label>

						<input type="text" id="<?php echo $this->get_field_id( 'button_text' ); ?>" name="<?php echo $this->get_field_name( 'button_text' ); ?>" value="<?php esc_attr_e( $val['button_text'] ); ?>" placeholder="<?php echo $kol_email->strings['button_text']; ?>" class="widefat" />
					</p>

				<?php else : ?>

					<!-- Custom Code -->

					<p>
						<label for="<?php echo $this->get_field_id( 'custom_code' ); ?>"><?php echo $kol_email->strings['widget_custom_code']; ?>:</label>

						<textarea id="<?php echo $this->get_field_id( 'custom_code' ); ?>" name="<?php echo $this->get_field_name( 'custom_code' ); ?>" class="widefat" rows="7"><?php printf( '%s', esc_textarea( $val['custom_code'] ) ); ?></textarea>
					</p>

				<?php endif; ?>

				<!-- Advanced Options -->

				<h4><?php echo $kol_email->strings['advanced']; ?></h4>

				<!-- Display -->

				<p>
					<label for="<?php echo $this->get_field_id( 'display' ); ?>"><?php echo $kol_email->strings['widget_display']; ?>:</label><br />

					<select id="<?php echo $this->get_field_id( 'display' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" style="max-width: 100%;">
						<option value=""><?php echo $kol_email->strings['select_display']; ?></option>
						<option value="blog" <?php selected( $val['display'], 'blog', true ); ?>><?php echo $kol_email->strings['blog_only']; ?></option>
						<option value="posts" <?php selected( $val['display'], 'posts', true ); ?>><?php echo $kol_email->strings['posts_only']; ?></option>
					</select>
				</p>

				<!-- Classes -->

				<p>
					<label for="<?php echo $this->get_field_id( 'classes' ); ?>"><?php echo $kol_email->strings['widget_classes']; ?>:</label>

					<input type="text" id="<?php echo $this->get_field_id( 'classes' ); ?>" name="<?php echo $this->get_field_name( 'classes' ); ?>" value="<?php esc_attr_e( $val['classes'] ); ?>" class="widefat" />
				</p>

			</div>

			<?php if ( $this->email_data['service'] != 'custom_code' ) : ?>

				<script>

					// Toggle conditional fields

					( function() {
						document.getElementById( '<?php echo $this->get_field_id( 'list' ); ?>' ).onchange = function() {
							document.getElementById( '<?php echo $this->get_field_id( 'kol-email' ); ?>-form' ).style.display = this.value != '' ? 'block' : 'none';
						}
						document.getElementById( '<?php echo $this->get_field_id( 'form_fields_name' ); ?>' ).onchange = function() {
							document.getElementById( '<?php echo $this->get_field_id( 'name_label' ); ?>-field' ).style.display = this.checked ? 'block' : 'none';
						}
					})();

				</script>

			<?php endif; ?>

		<?php else : ?>

			<p><?php echo $kol_email->strings['before_use']; ?></p>

		<?php endif; ?>

	<?php }
}