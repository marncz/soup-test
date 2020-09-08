<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Background_Processes;

use AS3CF_Error;
use AS3CF_Utils;
use DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item;
use Exception;

class Update_ACLs_Process extends Background_Tool_Process {

	/**
	 * @var string
	 */
	protected $action = 'update_acls';

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
		$bucket = $this->as3cf->get_setting( 'bucket' );
		$region = $this->as3cf->get_setting( 'region' );

		$attachments_to_update = array();

		foreach ( $attachments as $attachment_id ) {
			// If the attachment is offloaded to another provider, skip it.
			if ( ! $this->as3cf->is_attachment_served_by_provider( $attachment_id, true ) ) {
				continue;
			}

			// If the attachment is offloaded to another bucket, skip it, because we don't know its Block Public Access state.
			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );

			if ( $as3cf_item ) {
				if ( $as3cf_item->bucket() !== $bucket ) {
					continue;
				}

				$attachments_to_update[] = $attachment_id;
			} else {
				AS3CF_Error::log( sprintf( 'Update Object ACLs: Offload data for attachment with ID %d could not be found for analysis.', $attachment_id ) );
			}
		}

		$this->update_attachments( $attachments_to_update, $blog_id, $bucket, $region );

		// Whether updated or not, we processed every item.
		return $attachments;
	}

	/**
	 * Bulk update ACLs for attachments.
	 *
	 * @param array  $attachments
	 * @param int    $blog_id
	 * @param string $bucket
	 * @param string $region
	 *
	 * @throws Exception
	 */
	protected function update_attachments( $attachments, $blog_id, $bucket, $region ) {
		if ( empty( $attachments ) ) {
			return;
		}

		$keys = $this->as3cf->get_provider_keys( $attachments );

		if ( empty( $keys ) ) {
			return;
		}

		$items = array();

		foreach ( $keys as $attachment_id => $attachment_keys ) {
			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );

			foreach ( $attachment_keys as $key ) {
				$size = AS3CF_Utils::get_intermediate_size_from_filename( $attachment_id, wp_basename( $key ) );
				$acl  = $this->as3cf->get_acl_for_intermediate_size( $attachment_id, $size, $bucket, $as3cf_item );

				// Only set ACL if actually required, some storage provider and bucket settings disable changing ACL.
				// This is a fallback check, just in case settings changed from under us via define etc, saves throwing lots of errors.
				if ( ! empty( $acl ) ) {
					$items[] = array(
						'Bucket' => $bucket,
						'Key'    => $key,
						'ACL'    => $acl,
					);
				}
			}
		}

		$failures = array();

		if ( ! empty( $items ) ) {
			$client = $this->as3cf->get_provider_client( $region, true );
			try {
				$failures = $client->update_object_acls( $items );
			} catch ( Exception $e ) {
				AS3CF_Error::log( $e->getMessage() );

				return;
			}
		}

		if ( ! empty( $failures ) ) {
			$this->record_failures( $keys, $failures, $blog_id );
		}
	}

	/**
	 * Handle failed keys.
	 *
	 * @param array $keys
	 * @param array $failures
	 * @param int   $blog_id
	 */
	protected function record_failures( $keys, $failures, $blog_id ) {
		foreach ( $failures as $failure ) {
			foreach ( $keys as $attachment_id => $attachment_keys ) {
				if ( false !== array_search( $failure['Key'], $attachment_keys ) ) {
					$error_msg = sprintf( __( 'Error updating object ACL for %1$s: %2$s', 'amazon-s3-and-cloudfront' ), $failure['Key'], $failure['Message'] );

					$this->record_error( $blog_id, $attachment_id, $error_msg );

					unset( $keys[ $attachment_id ] );

					break;
				}
			}
		}
	}

	/**
	 * Get complete notice message.
	 *
	 * @return string
	 */
	protected function get_complete_message() {
		return __( '<strong>WP Offload Media</strong> &mdash; Finished updating object ACLs in bucket.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Called when background process has been cancelled.
	 */
	protected function cancelled() {
		// Do nothing at the moment.
	}

	/**
	 * Called when background process has been paused.
	 */
	protected function paused() {
		// Do nothing at the moment.
	}

	/**
	 * Called when background process has been resumed.
	 */
	protected function resumed() {
		// Do nothing at the moment.
	}

	/**
	 * Called when background process has completed.
	 */
	protected function completed() {
		// Do nothing at the moment.
	}
}