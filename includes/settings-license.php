
<?php

	require_once( 'settings-header.php' );

	$license = get_option( 'edd_sample_license_key' );
	$status  = get_option( 'yoohoo_zapier_license_status' );
	$expires = get_option( 'yoohoo_zapier_license_expires' );

	// Check on Submit and update license server.
	if ( isset( $_REQUEST['submit'] ) ) {

		if ( isset( $_REQUEST['edd_sample_license_key' ] ) ) {
			update_option( 'edd_sample_license_key', $_REQUEST['edd_sample_license_key'] );
			$license = $_REQUEST['edd_sample_license_key'];
			yoohoo_admin_notice( 'Saved successfully.', 'success' );
		}
	}

	// Activate license.
	if( isset( $_POST['activate_license'] ) ) {
		// run a quick security check
	 	if( ! check_admin_referer( 'yoohoo_license_nonce', 'yoohoo_license_nonce' ) ) {
			return; // get out if we didn't click the Activate button
	 	}

		// retrieve the license from the database
		$license = trim( get_option( 'edd_sample_license_key' ) );


		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_id'    => YH_PLUGIN_ID, // The ID of the item in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( YOOHOO_STORE, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );



		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			$message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );

			yoohoo_admin_notice( $message, 'error' );

		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		}
		update_option( 'yoohoo_zapier_license_status', $license_data->license );
		update_option( 'yoohoo_zapier_license_expires', $license_data->expires );
		$status = $license_data->license;
		$expires = $license_data->expires;
		yoohoo_admin_notice( 'License successfully activated.', 'success' );

	}

	// Deactivate license.
	if ( isset( $_POST['deactivate_license'] ) ) {

		if( ! check_admin_referer( 'yoohoo_license_nonce', 'yoohoo_license_nonce' ) ) {
			return; // get out if we didn't click the Activate button
	 	}

	$api_params = array(
		'edd_action' => 'deactivate_license',
		'license' => $license,
		'item_id' => YH_PLUGIN_ID, // the name of our product in EDD
		'url' => home_url()
	);

	// Send the remote request
	$response = wp_remote_post( YOOHOO_STORE, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => false ) );

	// if there's no erros in the post, just delete the option.
	if ( ! is_wp_error( $response ) ) {
		delete_option( 'yoohoo_zapier_license_status' );
		$status = false;
		yoohoo_admin_notice( 'Deactivated license successfully.', 'success' );
	}
	
}

?>
	<div class="wrap">
		<h2><?php _e('Plugin License Options'); ?></h2>
		<form method="post" action="">

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('License Key'); ?>
						</th>
						<td>
							<input id="edd_sample_license_key" name="edd_sample_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="edd_sample_license_key"><?php _e('Enter your license key.'); ?></label><br/>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<?php _e( 'License Status' ); ?>
						</th>
						<td>
							<?php if( $status !== false && $status == 'valid' ) { ?>
								<span style="color:green"><strong><?php _e( 'Active.' ); ?></strong></span> <?php _e( sprintf( 'Expires on %s', $expires ) ); ?>
							<?php } ?>
						</td>
					</tr>
					<?php if( ! empty( $license ) || false != $license ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php _e('Activate License'); ?>
							</th>
							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<?php wp_nonce_field( 'yoohoo_license_nonce', 'yoohoo_license_nonce' ); ?>
									<input type="submit" class="button-secondary" style="color:red;" name="deactivate_license" value="<?php _e('Deactivate License'); ?>"/><br/><br/>
								<?php } else {
									wp_nonce_field( 'yoohoo_license_nonce', 'yoohoo_license_nonce' ); ?>
									<input type="submit" class="button-secondary" name="activate_license" value="<?php _e('Activate License'); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>

		</form>

<?php

function yoohoo_admin_notice( $message, $status ) {
	   ?>
    <div class="notice notice-<?php echo $status; ?> is-dismissible">
        <p><?php _e( $message ); ?></p>
    </div>
    <?php
}