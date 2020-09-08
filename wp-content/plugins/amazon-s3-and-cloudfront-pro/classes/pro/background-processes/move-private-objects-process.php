<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Background_Processes;

use AS3CF_Error;
use DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item;
use Exception;

class Move_Private_Objects_Process extends Move_Objects_Process {

	/**
	 * @var string
	 */
	protected $action = 'move_private_objects';

	/**
	 * Process attachments chunk.
	 *
	 * @param array $attachments
	 * @param int   $blog_id
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function process_attachments_chunk( $attachments, $blog_id ) {
		$attachments_to_move = array();

		if ( $this->as3cf->private_prefix_enabled() ) {
			$new_private_prefix = $this->as3cf->get_setting( 'signed-urls-object-prefix', '' );
		} else {
			$new_private_prefix = '';
		}

		foreach ( $attachments as $attachment_id ) {
			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );

			if ( $as3cf_item ) {
				// Analyze current private prefix to see if it needs changing.
				switch ( $this->should_move_to_new_private_prefix( $as3cf_item, $as3cf_item->private_prefix(), $new_private_prefix ) ) {
					case self::MOVE_NO:
					case self::MOVE_SAME:
						break;
					case self::MOVE_NOOP:
						// If nothing is to be moved to new private prefix, just fix data.
						$this->update_private_prefix_data( $as3cf_item, $new_private_prefix );
						continue 2;
					case self::MOVE_YES:
						$attachments_to_move[ $attachment_id ] = [ 'prefix' => $as3cf_item->normalized_path_dir(), 'private_prefix' => $new_private_prefix ];
						break;
				}
			} else {
				AS3CF_Error::log( sprintf( 'Move Private Objects: Offload data for attachment with ID %d could not be found for analysis.', $attachment_id ) );
			}
		}

		$this->move_attachments( $attachments_to_move, $blog_id );

		// Whether moved or not, we processed every item.
		return $attachments;
	}

	/**
	 * Get complete notice message.
	 *
	 * @return string
	 */
	protected function get_complete_message() {
		return __( '<strong>WP Offload Media</strong> &mdash; Finished moving media files to new private paths.', 'amazon-s3-and-cloudfront' );
	}
}