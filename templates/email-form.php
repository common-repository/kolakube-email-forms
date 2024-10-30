<?php
/**
 * To edit the form HTML, copy this entire template file into
 * your child theme. Be sure to match the template path of this plugin,
 * making the file path: /themes/my-child-theme/templates/email-form.php.
 *
 * The variables used in this template are passed from the widget() method
 * in widget.php and uses the options from the Email Forms Widget for output.
 */
?>

<?php echo $args['before_widget']; ?>

<?php do_action( 'kol_email_forms_top' ); ?>

	<!-- Intro -->

	<?php if ( $title || $desc ) : ?>

		<div class="kol-email-intro">

			<!-- Title -->

			<?php if ( $title ) : ?>
				<?php echo $args['before_title'] . $title . $args['after_title']; ?>
			<?php endif; ?>

			<!-- Description -->

			<?php if ( $desc ) : ?>
				<?php echo wpautop( $desc ); ?>
			<?php endif; ?>

		</div>

	<?php endif; ?>

	<!-- Email Form -->

	<?php if ( $email_service != 'custom_code' && ! empty( $list ) ) : ?>

		<div class="kol-email-form">

			<?php do_action( 'kol_email_forms_before_form' ); ?>

			<?php if ( $email_service == 'convertkit' ) : ?>
				<div id="ck_success_msg" style="display: none;">
					<p class="success"><?php echo $kol_email->strings['submission_success']; ?></p>
				</div>
			<?php endif; ?>

			<form<?php echo $email_service == 'convertkit' ? ' id="ck_subscribe_form"' : ''; ?> method="post" action="<?php esc_attr_e( $action ); ?>" class="<?php echo $classes; ?>">

				<?php do_action( 'kol_email_forms_form_top' ); ?>

				<?php if ( $email_service == 'aweber' ) : ?>

					<!-- AWeber -->

					<?php if ( ! empty( $form_id ) ) : ?>
						<input type="hidden" name="meta_web_form_id" value="<?php esc_attr_e( $form_id ); ?>" />
					<?php endif; ?>
					<input type="hidden" name="meta_split_id" value="" />
					<input type="hidden" name="listname" value="<?php esc_attr_e( $list ); ?>" />
					<?php if ( ! empty( $thank_you ) ) : ?>
						<input type="hidden" name="redirect" value="<?php echo get_permalink( $thank_you ); ?>" />
					<?php endif; ?>
					<?php if ( ! empty( $already_subs ) ) : ?>
						<input type="hidden" name="meta_redirect_onlist" value="<?php echo get_permalink( $already_subs ); ?>" />
					<?php endif; ?>
					<?php if ( ! empty( $ad_tracking ) ) : ?>
						<input type="hidden" name="meta_adtracking" value="<?php esc_attr_e( $ad_tracking ); ?>" />
					<?php endif; ?>
					<input type="hidden" name="meta_message" value="1" />
					<input type="hidden" name="meta_required" value="<?php echo $field_name ? "$att_name,$att_email" : $att_email; ?>" />
					<input type="hidden" name="meta_tooltip" value="" />

				<?php endif; ?>

				<?php if ( $email_service == 'activecampaign' ) : ?>

					<!-- ActiveCampaign -->

					<input type="hidden" name="f" value="<?php esc_attr_e( $list ); ?>">
					<input type="hidden" name="s" value="">
					<input type="hidden" name="c" value="0">
					<input type="hidden" name="m" value="0">
					<input type="hidden" name="act" value="sub">
					<input type="hidden" name="nlbox[]" value="<?php esc_attr_e( $nlbox ); ?>">

				<?php endif; ?>

				<?php if ( $email_service == 'convertkit' ) : ?>

					<!-- ConvertKit -->

					<input type="hidden" name="id" value="<?php esc_attr_e( $list ); ?>" id="landing_page_id" />

					<div id="ck_error_msg" style="display: none;">
						<p class="required"><?php echo $kol_email->strings['submission_error']; ?></p>
					</div>

				<?php endif; ?>

				<?php if ( $field_name ) : ?>
					<!-- Name Field -->
					<input type="text" name="<?php esc_attr_e( $att_name ); ?>" id="kol-email-field-name" class="kol-email-field-name form-input" placeholder="<?php echo $label_name; ?>" />
				<?php endif; ?>

				<!-- Email Field -->

				<input type="email" name="<?php esc_attr_e( $att_email ); ?>" id="kol-email-field-email" placeholder="<?php echo $label_email; ?>" class="kol-email-field-email form-input" />

				<?php if ( $email_service == 'aweber' && ! empty( $image ) ) : ?>
					<img src="http://forms.aweber.com/form/displays.htm?id=<?php esc_attr_e( $image ); ?>" style="display: none;" alt="" />
				<?php endif; ?>

				<?php if ( $email_service == 'mailchimp' ) : ?>
					<input type="hidden" name="u" value="<?php esc_attr_e( $u ); ?>">
					<input type="hidden" name="id" value="<?php esc_attr_e( $id ); ?>">
				<?php endif; ?>

				<!-- Submit Button -->

				<button class="kol-email-field-submit form-submit"><?php echo $button_text; ?></button>

				<?php do_action( 'kol_email_forms_after_submit' ); ?>

				<!-- After Form Text -->

				<?php if ( $after_form_text ) : ?>
					<?php echo wpautop( $after_form_text ); ?>
				<?php endif; ?>

			</form>

			<?php do_action( 'kol_email_forms_after_form' ); ?>

		</div>

	<?php else : ?>

		<!-- Custom Form Code -->

		<div class="kol-email-form">

			<?php do_action( 'kol_email_forms_before_form' ); ?>

			<?php echo $val['custom_code']; ?>

			<?php do_action( 'kol_email_forms_after_form' ); ?>

		</div>

	<?php endif; ?>

<?php do_action( 'kol_email_forms_bottom' ); ?>

<?php echo $args['after_widget']; ?>