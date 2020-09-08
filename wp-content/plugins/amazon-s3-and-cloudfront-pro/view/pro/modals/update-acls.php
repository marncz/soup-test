<div class="as3cf-update-acls-prompt">
	<?php
	if ( ! empty( $_GET['prev_action'] ) && 'change-bucket-access' === $_GET['prev_action'] ) {
		$back_args = array( 'action' => 'change-bucket-access' );

		if ( ! empty( $_GET['orig_provider'] ) ) {
			$back_args['orig_provider'] = $_GET['orig_provider'];
		}
		echo '<a href="' . $this->get_plugin_page_url( $back_args ) . '">' . __( '&laquo;&nbsp;Back', 'amazon-s3-and-cloudfront' ) . '</a>';
	}
	?>
	<h3><?php _e( 'Update Object ACLs', 'amazon-s3-and-cloudfront' ); ?></h3>
	<p>
		<?php
		_e( 'It looks like your configuration has changed to require public and private ACLs on objects, would you like to update the ACLs on your objects?', 'amazon-s3-and-cloudfront' );
		echo ' ' . $this->settings_more_info_link( 'bucket', 'update+acls' );
		?>
	</p>
	<p class="actions select">
		<button type="submit" name="update-acls" value="1" class="button button-primary right"><?php _e( 'Yes', 'amazon-s3-and-cloudfront' ); ?></button>
		<button type="submit" name="update-acls" value="0" class="button right"><?php _e( 'No', 'amazon-s3-and-cloudfront' ); ?></button>
	</p>
</div>