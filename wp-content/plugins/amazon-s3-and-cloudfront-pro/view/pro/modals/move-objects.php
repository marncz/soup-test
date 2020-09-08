<div class="as3cf-move-objects-prompt">
	<?php
	/** @var bool $as3cf_move_public_objects */
	if ( $as3cf_move_public_objects ) {
		?>
		<div class="as3cf-move-public-objects">
			<h3><?php _e( 'Storage Path Updated: Would you like to move existing media to the new path?', 'amazon-s3-and-cloudfront' ); ?></h3>
			<p><?php _e( 'You just updated the storage path. Any new media you offload from now on will use this new path.', 'amazon-s3-and-cloudfront' ); ?></p>
			<p><?php _e( 'You can also move existing media to this new path. Beware however that moving existing media will update URLs and will result in 404s for any sites embedding or linking to your media. It could also have a negative impact on SEO. If you\'re unsure about this, we recommend not moving existing media to the new path.', 'amazon-s3-and-cloudfront' ); ?></p>
			<p class="actions select">
				<button type="submit" name="move-public-objects" value="1" class="button button-primary right"><?php _e( 'Yes', 'amazon-s3-and-cloudfront' ); ?></button>
				<button type="submit" name="move-public-objects" value="0" class="button right"><?php _e( 'No', 'amazon-s3-and-cloudfront' ); ?></button>
			</p>
		</div>
		<?php
	}

	/** @var bool $as3cf_move_private_objects */
	if ( $as3cf_move_private_objects ) {
		if ( $as3cf_move_public_objects ) {
			$as3cf_move_private_objects_style = ' style="display: none;"';
			?>
			<input id="as3cf-move-public-objects-selection" type="hidden" name="move-public-objects" value="0">
			<?php
		} else {
			$as3cf_move_private_objects_style = '';
		}
		?>
		<div class="as3cf-move-private-objects"<?php echo $as3cf_move_private_objects_style; ?>>
			<h3><?php _e( 'Private Path Updated: Would you like to move existing media to the new private path?', 'amazon-s3-and-cloudfront' ); ?></h3>
			<p><?php _e( 'You just updated the private media path. Any media you make private from now on will use this new path.', 'amazon-s3-and-cloudfront' ); ?></p>
			<p><?php _e( 'You can also move existing private media to this new path. We recommend keeping the path consistent across all private media.', 'amazon-s3-and-cloudfront' ); ?></p>
			<p class="actions select">
				<button type="submit" name="move-private-objects" value="1" class="button button-primary right"><?php _e( 'Yes', 'amazon-s3-and-cloudfront' ); ?></button>
				<button type="submit" name="move-private-objects" value="0" class="button right"><?php _e( 'No', 'amazon-s3-and-cloudfront' ); ?></button>
			</p>
		</div>
	<?php } ?>
</div>