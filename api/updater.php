<?php

/**
 * Run update process on new versions of the plugin, if needed.
 * Process controller currently in kolakube-email-forms.php
 *
 * @since 1.1
 */

class kol_email_updater {

	/**
	 * Clean up the horrid data structures built from 1.0.
	 *
	 * @since 1.1
	 */

	public function updater_11() {
		$email = get_option( 'kol_email' );
		$data  = get_option( 'kol_email_data' );

		$data['service'] = $email['service'];

		if ( ! empty ( $data['lists_options'] ) )
			foreach ( $data['lists_options'] as $id => $name ) {
				$data['lists'][$id] = array(
					'name' => $name
				);

				if ( $data['service'] == 'mailchimp' )
					$data['lists'][$id]['url'] = $data['lists_data'][$id]['url'];
			}

		unset( $data['save'] );
		unset( $data['lists_ids'] );
		unset( $data['lists_options'] );
		unset( $data['lists_data'] );

		update_option( 'kol_email_data', $data );
		delete_option( 'kol_email' );
	}

}