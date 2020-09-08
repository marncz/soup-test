<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Tools;

use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Background_Tool_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Move_Objects_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Tool;

class Move_Objects extends Background_Tool {

	/**
	 * @var string
	 */
	protected $tool_key = 'move_objects';

	/**
	 * @var string
	 */
	protected $tab = 'media';

	/**
	 * @var array
	 */
	protected static $show_tool_constants = array(
		'AS3CF_SHOW_MOVE_OBJECTS_TOOL',
	);

	/**
	 * Initialize the tool.
	 */
	public function init() {
		parent::init();

		if ( ! $this->as3cf->is_pro_plugin_setup( true ) ) {
			return;
		}

		// Prompt
		add_filter( 'as3cf_media_tab_storage_classes', array( $this, 'media_tab_storage_classes' ) );
		add_action( 'as3cf_pre_media_settings', array( $this, 'render_modal' ) );
		add_filter( 'as3cf_handle_post_request', array( $this, 'handle_post_request' ) );
		add_action( 'as3cfpro_load_assets', array( $this, 'load_assets' ) );
		add_filter( 'as3cfpro_js_strings', array( $this, 'add_js_strings' ) );

		$this->register_actions();
	}

	/**
	 * Register tool specific action hooks.
	 */
	protected function register_actions() {
		add_filter( 'as3cf_action_for_changed_settings_key', array( $this, 'action_for_changed_settings_key' ), 10, 2 );
		add_action( 'as3cf_after_setting', array( $this, 'after_setting' ), 10, 2 );
	}

	/**
	 * Get the details for the sidebar block
	 *
	 * @return array|bool
	 */
	protected function get_sidebar_block_args() {
		if ( ! $this->as3cf->is_pro_plugin_setup( true ) ) {
			return false;
		}

		return parent::get_sidebar_block_args();
	}

	/**
	 * Maybe start move objects process via post request.
	 *
	 * @param array $changed_keys
	 *
	 * @return array
	 */
	public function handle_post_request( $changed_keys ) {
		if (
			! empty( $_GET['action'] ) &&
			'move-objects' === $_GET['action'] &&
			! $this->as3cf->get_storage_provider()->needs_access_keys() &&
			$this->as3cf->get_setting( 'bucket' ) &&
			! empty( $_POST['move-public-objects'] ) &&
			! empty( $_POST['move-private-objects'] )
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
		if ( ( ! empty( $_GET['action'] ) && 'move-objects' === $_GET['action'] ) || ( ! empty( $_GET['prev_action'] ) && 'move-objects' === $_GET['prev_action'] ) ) {
			return 'move-objects' === $action ? null : $action;
		}

		if (
			empty( $action ) &&
			! empty( array_intersect( $changed_keys, array( 'enable-object-prefix', 'object-prefix', 'use-yearmonth-folders', 'object-versioning' ) ) ) &&
			(
				! empty( array_intersect( $changed_keys, array( 'enable-signed-urls', 'signed-urls-object-prefix' ) ) ) ||
				( ! empty( array_intersect( $changed_keys, array( 'enable-delivery-domain' ) ) ) && $this->as3cf->get_setting( 'enable-signed-urls' ) )
			) &&
			$this->move_public_objects_is_safe( $changed_keys ) &&
			$this->count_offloaded_media_files()
		) {
			return 'move-objects';
		}

		return $action;
	}

	/**
	 * Is it safe to show the move public objects prompt?
	 *
	 * @param array $changed_keys
	 *
	 * @return bool
	 */
	public function move_public_objects_is_safe( $changed_keys ) {
		// Basic safeguard against objects from different attachments sharing a path.
		if ( in_array( 'object-versioning', $changed_keys ) && ! $this->as3cf->get_setting( 'object-versioning' ) ) {
			return false;
		}
		if ( in_array( 'use-yearmonth-folders', $changed_keys ) && ! $this->as3cf->get_setting( 'use-yearmonth-folders' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Handler for the as3cf_after_setting action.
	 *
	 * @param string $key
	 * @param string $title
	 */
	public function after_setting( $key, $title ) {
		if ( in_array( $key, array( 'object-versioning', 'use-yearmonth-folders' ) ) && $this->count_offloaded_media_files() ) {
			// At time of UI render, if returning to the setting's current state would be considered unsafe for move,
			// then we do not need to render the notice.
			if ( ! $this->move_public_objects_is_safe( array( $key ) ) ) {
				return;
			}

			$notice_link = $this->as3cf->more_info_link( '/wp-offload-media/doc/how-to-move-media-to-a-new-bucket-path/', 'error-media+move+objects' );
			$notice_msg  = sprintf( __( '<strong>Warning</strong> &mdash; Because you\'ve turned off %1$s, we will not offer to move existing media to the new path as it could result in overwriting some media in your bucket. %2$s', 'amazon-s3-and-cloudfront' ), $title, $notice_link );
			$notice_args = array(
				'message' => $notice_msg,
				'id'      => 'as3cf-move-public-objects-unsafe-notice-' . $key,
				'inline'  => true,
				'type'    => 'notice-warning',
				'style'   => 'display: none',
			);
			$this->as3cf->render_view( 'notice', $notice_args );
		}
	}

	/**
	 * Adjust media tab's class attribute.
	 *
	 * @param string $storage_classes Class names related to storage provider.
	 *
	 * @return string
	 */
	public function media_tab_storage_classes( $storage_classes ) {
		if ( ! empty( $_GET['action'] ) && 'move-objects' === $_GET['action'] ) {
			$storage_classes .= ' as3cf-move-objects as3cf-move-public-objects as3cf-move-private-objects';
		}

		return $storage_classes;
	}

	/**
	 * Load assets.
	 */
	public function load_assets() {
		parent::load_assets();

		$this->as3cf->enqueue_script( 'as3cf-pro-move-objects-script', 'assets/js/pro/tools/move-objects', array(
			'jquery',
			'wp-util',
		) );
	}

	/**
	 * Render modal in footer.
	 */
	public function render_modal() {
		if ( ! empty( $_GET['action'] ) && 'move-objects' === $_GET['action'] ) {
			$this->as3cf->render_view(
				'modals/move-objects',
				array(
					'as3cf_move_public_objects'  => true,
					'as3cf_move_private_objects' => true,
				)
			);
		}
	}

	/**
	 * Add localized strings to Javascript.
	 *
	 * @param $strings
	 *
	 * @return array
	 */
	public function add_js_strings( $strings ) {
		$strings['tools'][ $this->tool_key ] = array(
			'error' => __( 'There was an error attempting to start the move objects tool. Please try again.', 'amazon-s3-and-cloudfront' ),
		);

		return $strings;
	}

	/**
	 * Should render.
	 *
	 * @return bool
	 */
	public function should_render() {
		if ( false !== static::show_tool_constant() && constant( static::show_tool_constant() ) ) {
			return true;
		}

		return $this->is_queued() || $this->is_processing() || $this->is_paused() || $this->is_cancelled();
	}

	/**
	 * Get title text.
	 *
	 * @return string
	 */
	public function get_title_text() {
		return __( 'Move files to new path prefix', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get button text.
	 *
	 * @return string
	 */
	public function get_button_text() {
		return __( 'Begin Move', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get queued status text.
	 *
	 * @return string
	 */
	public function get_queued_status() {
		return __( 'Moving Media Library items to new path prefix.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get more info text.
	 *
	 * @return string
	 */
	public function get_more_info_text() {
		return __( 'Would you like to move your offloaded media files to paths that match the current path prefix settings? All existing offloaded media URLs will be updated to reference the new paths.', 'amazon-s3-and-cloudfront' );
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
		$message = empty( $message ) ? __( 'Previous attempts at moving your media library to new paths have resulted in errors.', 'amazon-s3-and-cloudfront' ) : $message;

		return sprintf( '<strong>%1$s</strong> &mdash; %2$s', $title, $message );
	}

	/**
	 * Get background process class.
	 *
	 * @return Background_Tool_Process|null
	 */
	protected function get_background_process_class() {
		return new Move_Objects_Process( $this->as3cf, $this );
	}
}
