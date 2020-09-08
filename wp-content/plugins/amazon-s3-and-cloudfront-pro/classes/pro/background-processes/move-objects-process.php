<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Background_Processes;

use AS3CF_Error;
use AS3CF_Utils;
use DeliciousBrains\WP_Offload_Media\Items\Media_Library_Item;
use Exception;

class Move_Objects_Process extends Background_Tool_Process {

	/**
	 * @var string
	 */
	protected $action = 'move_objects';

	const MOVE_NO = 0;
	const MOVE_YES = 1;
	const MOVE_SAME = 2;
	const MOVE_NOOP = 3;

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
			$update     = false;
			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );

			if ( $as3cf_item ) {
				// Analyze current path to see if it needs changing.
				$old_prefix = $as3cf_item->normalized_path_dir();
				$new_prefix = $this->get_new_public_prefix( $as3cf_item, $old_prefix );

				if ( $new_prefix !== $old_prefix ) {
					$update = true;
				}

				// Analyze current private prefix to see if it needs changing.
				$private_prefix = $as3cf_item->private_prefix();

				switch ( $this->should_move_to_new_private_prefix( $as3cf_item, $private_prefix, $new_private_prefix ) ) {
					case self::MOVE_NO:
					case self::MOVE_SAME:
						break;
					case self::MOVE_NOOP:
						// If nothing is to be moved to new private prefix, and public isn't being updated, just fix data.
						if ( false === $update ) {
							$this->update_private_prefix_data( $as3cf_item, $new_private_prefix );
							continue 2;
						}

						$private_prefix = $new_private_prefix;
						break;
					case self::MOVE_YES:
						$private_prefix = $new_private_prefix;
						$update         = true;
						break;
				}

				if ( $update ) {
					$attachments_to_move[ $attachment_id ] = [ 'prefix' => $new_prefix, 'private_prefix' => $private_prefix ];
				}
			} else {
				AS3CF_Error::log( sprintf( 'Move Objects: Offload data for attachment with ID %d could not be found for analysis.', $attachment_id ) );
			}
		}

		$this->move_attachments( $attachments_to_move, $blog_id );

		// Whether moved or not, we processed every item.
		return $attachments;
	}

	/**
	 * Returns new public prefix if required, otherwise returns old prefix.
	 *
	 * @param Media_Library_Item $as3cf_item
	 * @param string             $old_prefix
	 *
	 * @return string
	 */
	protected function get_new_public_prefix( Media_Library_Item $as3cf_item, $old_prefix ) {
		$new_prefix = $this->as3cf->get_new_attachment_prefix( $as3cf_item->source_id() );

		// Length changed is simplest indicator.
		if ( strlen( $old_prefix ) !== strlen( $new_prefix ) ) {
			goto move_item;
		}

		$old_parts = explode( '/', trim( $old_prefix, '/' ) );
		$new_parts = explode( '/', trim( $new_prefix, '/' ) );

		// Number of path elements changed?
		if ( count( $old_parts ) !== count( $new_parts ) ) {
			goto move_item;
		}

		// If object versioning is on, don't compare last segment.
		if ( $this->as3cf->get_setting( 'object-versioning', false ) ) {
			$old_parts = array_slice( $old_parts, 0, -1 );
			$new_parts = array_slice( $new_parts, 0, -1 );
		}

		// Each element should now be the same.
		// Simplest way to check here is walk one and check the other by index.
		// No need to get all fancy!
		foreach ( $old_parts as $key => $val ) {
			if ( $new_parts[ $key ] !== $val ) {
				goto move_item;
			}
		}

		// If here, then prefix does not need to change, regardless of whether private prefix does.
		// This could be important for mixed public/private thumbnails and external links.
		// We already know that old and new prefix are the same except for object version,
		// which is at least still using the same format (length check confirmed that).
		$new_prefix = $old_prefix;

		move_item:

		return $new_prefix;
	}

	/**
	 * Should the given item be moved to the new private prefix?
	 *
	 * @param Media_Library_Item $as3cf_item
	 * @param string             $old_private_prefix
	 * @param string             $new_private_prefix
	 *
	 * @return int One of MOVE_NO, MOVE_YES, MOVE_SAME or MOVE_NOOP.
	 */
	protected function should_move_to_new_private_prefix( Media_Library_Item $as3cf_item, $old_private_prefix, $new_private_prefix ) {
		// Analyze current private prefix to see if it needs changing.
		if ( $old_private_prefix === $new_private_prefix ) {
			// Private prefix not changed, nothing to do.
			return self::MOVE_SAME;
		} elseif ( ! $as3cf_item->is_private() && empty( $as3cf_item->private_sizes() ) ) {
			// Not same, but nothing is to be moved to private prefix, maybe just fix data.
			return self::MOVE_NOOP;
		} else {
			// Private prefix changed, move some private objects.
			return self::MOVE_YES;
		}
	}

	/**
	 * Update an item's private prefix data, but don't move it.
	 *
	 * @param Media_Library_Item $as3cf_item
	 * @param string             $new_private_prefix
	 *
	 * @return Media_Library_Item
	 */
	protected function update_private_prefix_data( Media_Library_Item $as3cf_item, $new_private_prefix ) {
		$extra_info                   = $as3cf_item->extra_info();
		$extra_info['private_prefix'] = $new_private_prefix;

		$as3cf_item = new Media_Library_Item(
			$as3cf_item->provider(),
			$as3cf_item->region(),
			$as3cf_item->bucket(),
			$as3cf_item->path(),
			$as3cf_item->is_private(),
			$as3cf_item->source_id(),
			$as3cf_item->source_path(),
			wp_basename( $as3cf_item->original_source_path() ),
			$extra_info,
			$as3cf_item->id()
		);

		$as3cf_item->save();

		return $as3cf_item;
	}

	/**
	 * Move attachments to new path.
	 *
	 * @param array $attachments id => ['prefix' => 'new/path/prefix', 'private_prefix' => 'private']
	 * @param int   $blog_id
	 *
	 * @throws Exception
	 *
	 * Note: `private_prefix` will be prepended to `prefix` for any object that is private.
	 *       `prefix` and `private_prefix` are "directory" paths and can have leading/trailing slashes, they'll be handled.
	 *       Both `prefix` and `private_prefix` must be set per attachment id, but either/both may be empty.
	 */
	protected function move_attachments( $attachments, $blog_id ) {
		if ( empty( $attachments ) ) {
			return;
		}

		$keys   = $this->as3cf->get_provider_keys( array_keys( $attachments ) );
		$bucket = $this->as3cf->get_setting( 'bucket' );
		$region = $this->as3cf->get_setting( 'region' );

		if ( empty( $keys ) || empty( $bucket ) ) {
			return;
		}

		$new_keys = array();
		$items    = array();

		foreach ( $keys as $attachment_id => $attachment_keys ) {
			// If the attachment is offloaded to another provider, skip it.
			if ( ! $this->as3cf->is_attachment_served_by_provider( $attachment_id, true ) ) {
				$error_msg = sprintf( __( 'Attachment ID %s is offloaded to a different provider than currently configured', 'amazon-s3-and-cloudfront' ), $attachment_id );
				$this->record_error( $blog_id, $attachment_id, $error_msg );
				unset( $keys[ $attachment_id ] );
				continue;
			}

			// If the prefix isn't set, skip it.
			if ( ! isset( $attachments[ $attachment_id ]['prefix'] ) ) {
				$error_msg = sprintf( __( 'Prefix not set for Attachment ID %s (this should never happen, please report to support)', 'amazon-s3-and-cloudfront' ), $attachment_id );
				$this->record_error( $blog_id, $attachment_id, $error_msg );
				unset( $keys[ $attachment_id ] );
				continue;
			}

			// If the private_prefix isn't set, skip it.
			if ( ! isset( $attachments[ $attachment_id ]['private_prefix'] ) ) {
				$error_msg = sprintf( __( 'Private prefix not set for Attachment ID %s (this should never happen, please report to support)', 'amazon-s3-and-cloudfront' ), $attachment_id );
				$this->record_error( $blog_id, $attachment_id, $error_msg );
				unset( $keys[ $attachment_id ] );
				continue;
			}

			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );

			// If the attachment is offloaded to another bucket, skip it.
			if ( $as3cf_item->bucket() !== $bucket ) {
				$error_msg = sprintf( __( 'Attachment ID %s is offloaded to a different bucket than currently configured', 'amazon-s3-and-cloudfront' ), $attachment_id );
				$this->record_error( $blog_id, $attachment_id, $error_msg );
				unset( $keys[ $attachment_id ] );
				continue;
			}

			$prefix         = AS3CF_Utils::trailingslash_prefix( $attachments[ $attachment_id ]['prefix'] );
			$private_prefix = AS3CF_Utils::trailingslash_prefix( $attachments[ $attachment_id ]['private_prefix'] );

			// TODO: Make sure we're not clobbering another item's path.

			// Each key found in old paths will be moved to new path as appropriate for access.
			foreach ( $attachment_keys as $key ) {
				$size       = AS3CF_Utils::get_intermediate_size_from_filename( $attachment_id, wp_basename( $key ) );
				$is_private = $as3cf_item->is_private_size( $size );

				$new_key = $prefix . wp_basename( $key );

				if ( $is_private ) {
					$new_key = $private_prefix . $new_key;
				}

				// We need to record the new key so that we can reconcile with old keys.
				// However, if the old and new key are the same, don't try and move it.
				$new_keys[ $attachment_id ][] = $new_key;

				if ( $key === $new_key ) {
					continue;
				}

				$args = array(
					'Bucket'     => $as3cf_item->bucket(),
					'Key'        => $new_key,
					'CopySource' => urlencode( "{$as3cf_item->bucket()}/{$key}" ),
				);

				$acl = $this->as3cf->get_acl_for_intermediate_size( $attachment_id, $size, $as3cf_item->bucket(), $as3cf_item );

				// Only set ACL if actually required, some storage provider and bucket settings disable changing ACL.
				if ( ! empty( $acl ) ) {
					$args['ACL'] = $acl;
				}

				$args = apply_filters( 'as3cf_object_meta', $args, $attachment_id, $size, true );

				// Protect against filter use and only set ACL if actually required, some storage provider and bucket settings disable changing ACL.
				if ( isset( $args['ACL'] ) && empty( $acl ) ) {
					unset( $args['ACL'] );
				}

				$items[] = $args;
			}
		}

		// All skipped, abort.
		if ( empty( $items ) ) {
			return;
		}

		/*
		 * As there is no such thing as "move" objects in supported providers, and we want to be able to roll-back
		 * an entire attachment's copies if any fail, we copy, check for failures, and then only delete old keys
		 * which have successfully copied. Any partially copied attachments have their successful copies deleted
		 * instead so as to not leave orphaned objects either with old or new key prefixes.
		 */

		$client = $this->as3cf->get_provider_client( $region, true );

		try {
			$failures = $client->copy_objects( $items );
		} catch ( Exception $e ) {
			AS3CF_Error::log( $e->getMessage() );

			return;
		}

		if ( ! empty( $failures ) ) {
			$keys_to_remove = $this->handle_failed_keys( $keys, $failures, $blog_id, $new_keys );
		} else {
			$keys_to_remove = $keys;
		}

		// Prepare and batch delete all the redundant keys.
		$objects_to_delete = array();

		foreach ( $keys_to_remove as $attachment_id => $objects ) {
			foreach ( $objects as $idx => $object ) {
				// If key was not moved, don't delete it.
				if ( in_array( $object, $keys[ $attachment_id ] ) && in_array( $object, $new_keys[ $attachment_id ] ) ) {
					unset( $keys_to_remove[ $attachment_id ][ $idx ] );
					continue;
				}

				$objects_to_delete[] = array( 'Key' => $object );
			}
		}

		if ( ! empty( $objects_to_delete ) ) {
			try {
				$client->delete_objects( array(
					'Bucket' => $bucket,
					'Delete' => array(
						'Objects' => $objects_to_delete,
					),
				) );
			} catch ( Exception $e ) {
				AS3CF_Error::log( $e->getMessage() );
			}
		}

		$this->update_attachment_provider_info( $keys, $new_keys, $keys_to_remove, $attachments );
	}

	/**
	 * Handle failed keys.
	 *
	 * @param array $keys     id => ['path1', 'path2', ...]
	 * @param array $failures [] => ['Key', 'Message']
	 * @param int   $blog_id
	 * @param array $new_keys id => ['path1', 'path2', ...]
	 *
	 * @return array Keys that can be removed, old and new (roll-back)
	 */
	protected function handle_failed_keys( $keys, $failures, $blog_id, $new_keys ) {
		foreach ( $failures as $failure ) {
			foreach ( $new_keys as $attachment_id => $attachment_keys ) {
				$key_id = array_search( $failure['Key'], $attachment_keys );

				if ( false !== $key_id ) {
					$error_msg = sprintf(
						__( 'Error moving %1$s to %2$s for Attachment %3$d: %4$s', 'amazon-s3-and-cloudfront' ),
						$keys[ $attachment_id ][ $key_id ],
						$failure['Key'],
						$attachment_id,
						$failure['Message']
					);

					$this->record_error( $blog_id, $attachment_id, $error_msg );

					// Instead of deleting old keys for attachment, delete new ones (roll-back).
					$keys[ $attachment_id ] = $new_keys[ $attachment_id ];

					// Prevent further errors being shown for aborted attachment.
					unset( $new_keys[ $attachment_id ] );

					break;
				}
			}
		}

		return $keys;
	}

	/**
	 * Update attachment provider info.
	 *
	 * @param array $keys         id => ['path1', 'path2', ...]
	 * @param array $new_keys     id => ['path1', 'path2', ...]
	 * @param array $removed_keys id => ['path1', 'path2', ...]
	 * @param array $attachments  id => ['prefix' => 'new/path/prefix', 'private_prefix' => 'private']
	 */
	protected function update_attachment_provider_info( $keys, $new_keys, $removed_keys, $attachments ) {
		// There absolutely should be old keys, new keys, some removed/moved, and attachment prefix data.
		if ( empty( $keys ) || empty( $new_keys ) || empty( $removed_keys ) || empty( $attachments ) ) {
			return;
		}

		foreach ( $keys as $attachment_id => $attachment_keys ) {
			if ( empty( $attachments[ $attachment_id ] ) || empty( $new_keys[ $attachment_id ] ) || empty( $removed_keys[ $attachment_id ] ) ) {
				continue;
			}

			// As long as none of the new keys have been removed (roll-back),
			// then we're all good to update the primary path and private prefix.
			if ( ! empty( array_intersect( $new_keys[ $attachment_id ], $removed_keys[ $attachment_id ] ) ) ) {
				continue;
			}

			$as3cf_item = Media_Library_Item::get_by_source_id( $attachment_id );
			$new_path   = $attachments[ $attachment_id ]['prefix'] . wp_basename( $as3cf_item->path() );

			$extra_info                   = $as3cf_item->extra_info();
			$extra_info['private_prefix'] = $attachments[ $attachment_id ]['private_prefix'];

			$as3cf_item = new Media_Library_Item(
				$as3cf_item->provider(),
				$as3cf_item->region(),
				$as3cf_item->bucket(),
				$new_path,
				$as3cf_item->is_private(),
				$as3cf_item->source_id(),
				$as3cf_item->source_path(),
				wp_basename( $as3cf_item->original_source_path() ),
				$extra_info,
				$as3cf_item->id()
			);

			$as3cf_item->save();
		}
	}

	/**
	 * Get complete notice message.
	 *
	 * @return string
	 */
	protected function get_complete_message() {
		return __( '<strong>WP Offload Media</strong> &mdash; Finished moving media files to new paths.', 'amazon-s3-and-cloudfront' );
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