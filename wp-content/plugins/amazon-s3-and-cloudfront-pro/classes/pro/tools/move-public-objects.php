<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Tools;

use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Background_Tool_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Move_Public_Objects_Process;

class Move_Public_Objects extends Move_Objects {

	/**
	 * @var string
	 */
	protected $tool_key = 'move_public_objects';

	/**
	 * @var array
	 */
	protected static $show_tool_constants = array(
		'AS3CF_SHOW_MOVE_PUBLIC_OBJECTS_TOOL',
	);

	/**
	 * Register tool specific action hooks.
	 */
	protected function register_actions() {
		// Later priority so that move both public and private tool has first dibs.
		add_filter( 'as3cf_action_for_changed_settings_key', array( $this, 'action_for_changed_settings_key' ), 20, 2 );
	}

	/**
	 * Maybe start move public objects process via post request.
	 *
	 * @param array $changed_keys
	 *
	 * @return array
	 */
	public function handle_post_request( $changed_keys ) {
		if (
			! empty( $_GET['action'] ) &&
			( 'move-public-objects' === $_GET['action'] || 'move-objects' === $_GET['action'] ) &&
			! $this->as3cf->get_storage_provider()->needs_access_keys() &&
			$this->as3cf->get_setting( 'bucket' ) &&
			! empty( $_POST['move-public-objects'] ) &&
			empty( $_POST['move-private-objects'] )
		) {
			$this->handle_start();
		}

		return $changed_keys;
	}

	/**
	 * Should the Move Objects prompt be the next action?
	 *
	 * @param string $action
	 * @param array  $changed_keys
	 *
	 * @return string
	 */
	public function action_for_changed_settings_key( $action, $changed_keys ) {
		// If this step already processed, shortcut out.
		if (
			( ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'move-public-objects', 'move-objects' ) ) ) ||
			( ! empty( $_GET['prev_action'] ) && in_array( $_GET['prev_action'], array( 'move-public-objects', 'move-objects' ) ) )
		) {
			return 'move-public-objects' === $action ? null : $action;
		}

		if (
			empty( $action ) &&
			! empty( array_intersect( $changed_keys, array( 'enable-object-prefix', 'object-prefix', 'use-yearmonth-folders', 'object-versioning' ) ) ) &&
			$this->move_public_objects_is_safe( $changed_keys ) &&
			$this->count_offloaded_media_files()
		) {
			return 'move-public-objects';
		}

		return $action;
	}

	/**
	 * Adjust media tab's class attribute.
	 *
	 * @param string $storage_classes Class names related to storage provider.
	 *
	 * @return string
	 */
	public function media_tab_storage_classes( $storage_classes ) {
		if ( ! empty( $_GET['action'] ) && 'move-public-objects' === $_GET['action'] ) {
			$storage_classes .= ' as3cf-move-objects as3cf-move-public-objects';
		}

		return $storage_classes;
	}

	/**
	 * Render modal in footer.
	 */
	public function render_modal() {
		if ( ! empty( $_GET['action'] ) && 'move-public-objects' === $_GET['action'] ) {
			$this->as3cf->render_view(
				'modals/move-objects',
				array(
					'as3cf_move_public_objects'  => true,
					'as3cf_move_private_objects' => false,
				)
			);
		}
	}

	/**
	 * Get title text.
	 *
	 * @return string
	 */
	public function get_title_text() {
		return __( 'Move files to new storage path', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get queued status text.
	 *
	 * @return string
	 */
	public function get_queued_status() {
		return __( 'Moving Media Library items to new storage path.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get more info text.
	 *
	 * @return string
	 */
	public function get_more_info_text() {
		return __( 'Would you like to move your offloaded media files to paths that match the current storage path settings? All existing offloaded media URLs will be updated to reference the new paths.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Message for error notice.
	 *
	 * @param null $message Optional message to override the default for the tool.
	 *
	 * @return string
	 */
	protected function get_error_notice_message( $message = null ) {
		$title   = __( 'Move Objects Errors', 'amazon-s3-and-cloudfront' );
		$message = empty( $message ) ? __( 'Previous attempts at moving your media library to new storage paths have resulted in errors.', 'amazon-s3-and-cloudfront' ) : $message;

		return sprintf( '<strong>%1$s</strong> &mdash; %2$s', $title, $message );
	}

	/**
	 * Get background process class.
	 *
	 * @return Background_Tool_Process|null
	 */
	protected function get_background_process_class() {
		return new Move_Public_Objects_Process( $this->as3cf, $this );
	}
}
