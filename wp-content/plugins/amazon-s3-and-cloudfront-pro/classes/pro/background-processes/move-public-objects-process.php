<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Background_Processes;

use AS3CF_Error;
use DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item;
use Exception;

class Move_Public_Objects_Process extends Move_Objects_Process {

	/**
	 * @var string
	 */
	protected $action = 'move_public_objects';

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

		foreach ( $attachments as $attachment_id ) {
			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );

			if ( $as3cf_item ) {
				// Analyze current path to see if it needs changing.
				$old_prefix = $as3cf_item->normalized_path_dir();
				$new_prefix = $this->get_new_public_prefix( $as3cf_item, $old_prefix );

				if ( $new_prefix !== $old_prefix ) {
					$attachments_to_move[ $attachment_id ] = [ 'prefix' => $new_prefix, 'private_prefix' => $as3cf_item->private_prefix() ];
				}
			} else {
				AS3CF_Error::log( sprintf( 'Move Public Objects: Offload data for attachment with ID %d could not be found for analysis.', $attachment_id ) );
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
		return __( '<strong>WP Offload Media</strong> &mdash; Finished moving media files to new storage paths.', 'amazon-s3-and-cloudfront' );
	}
}