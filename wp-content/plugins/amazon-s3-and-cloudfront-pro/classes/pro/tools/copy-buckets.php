<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Tools;

use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Background_Tool_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Copy_Buckets_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Tool;

class Copy_Buckets extends Background_Tool {

	/**
	 * @var string
	 */
	protected $tool_key = 'copy_buckets';

	/**
	 * @var string
	 */
	protected $tab = 'media';

	/**
	 * @var array
	 */
	protected static $show_tool_constants = array(
		'AS3CF_SHOW_COPY_BUCKETS_TOOL',
		'WPOS3_SHOW_COPY_BUCKETS_TOOL',
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
		add_filter( 'as3cf_action_for_changed_settings_key', array( $this, 'action_for_changed_settings_key' ), 10, 2 );
		add_action( 'as3cfpro_load_assets', array( $this, 'load_assets' ) );
		add_filter( 'as3cfpro_js_strings', array( $this, 'add_js_strings' ) );
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
	 * Maybe start copy buckets process via post request.
	 *
	 * @param array $changed_keys
	 *
	 * @return array
	 */
	public function handle_post_request( $changed_keys ) {
		if (
			! empty( $_GET['action'] ) &&
			'copy-buckets' === $_GET['action'] &&
			! $this->as3cf->get_storage_provider()->needs_access_keys() &&
			$this->as3cf->get_setting( 'bucket' ) &&
			! empty( $_POST['copy-buckets'] )
		) {
			$this->handle_start();
		}

		return $changed_keys;
	}

	/**
	 * Should the Copy Buckets prompt be the next action?
	 *
	 * @param string $action
	 * @param array  $changed_keys
	 *
	 * @return string
	 */
	public function action_for_changed_settings_key( $action, $changed_keys ) {
		// If no previous step, or this step already processed, shortcut out.
		if ( empty( $_GET['action'] ) || 'copy-buckets' === $_GET['action'] || ( ! empty( $_GET['prev_action'] ) && 'copy-buckets' === $_GET['prev_action'] ) ) {
			return $action;
		}

		if ( empty( $action ) && ! empty( array_intersect( $changed_keys, array( 'bucket', 'region' ) ) ) && $this->count_offloaded_media_files() ) {

			// Even if bucket has been changed and we have offloaded media, we can only copy between buckets in same provider.
			if ( empty( $_GET['orig_provider'] ) || $this->as3cf->get_setting( 'provider', false ) === $_GET['orig_provider'] ) {
				return 'copy-buckets';
			}
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
		if ( ! empty( $_GET['action'] ) && 'copy-buckets' === $_GET['action'] ) {
			$storage_classes .= ' as3cf-copy-buckets';
		}

		return $storage_classes;
	}

	/**
	 * Load assets.
	 */
	public function load_assets() {
		parent::load_assets();

		$this->as3cf->enqueue_script( 'as3cf-pro-copy-buckets-script', 'assets/js/pro/tools/copy-buckets', array(
			'jquery',
			'wp-util',
		) );
	}

	/**
	 * Render modal in footer.
	 */
	public function render_modal() {
		if ( ! empty( $_GET['action'] ) && 'copy-buckets' === $_GET['action'] ) {
			$this->as3cf->render_view( 'modals/copy-buckets' );
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
			'error' => __( 'There was an error attempting to start the copy tool. Please try again.', 'amazon-s3-and-cloudfront' ),
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
		return __( 'Copy files to new bucket', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get button text.
	 *
	 * @return string
	 */
	public function get_button_text() {
		return __( 'Begin Copy', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get queued status text.
	 *
	 * @return string
	 */
	public function get_queued_status() {
		return __( 'Copying Media Library items between buckets.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get more info text.
	 *
	 * @return string
	 */
	public function get_more_info_text() {
		return __( 'Would you like to consolidate your offloaded media files by copying them into the currently selected bucket? All existing offloaded media URLs will be updated to reference the new bucket.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Message for error notice.
	 *
	 * @param null $message Optional message to override the default for the tool.
	 *
	 * @return string
	 */
	protected function get_error_notice_message( $message = null ) {
		$title   = __( 'Copy Bucket Errors', 'amazon-s3-and-cloudfront' );
		$message = empty( $message ) ? __( 'Previous attempts at copying your media library between buckets have resulted in errors.', 'amazon-s3-and-cloudfront' ) : $message;

		return sprintf( '<strong>%s</strong> &mdash; %s', $title, $message );
	}

	/**
	 * Get background process class.
	 *
	 * @return Background_Tool_Process|null
	 */
	protected function get_background_process_class() {
		return new Copy_Buckets_Process( $this->as3cf, $this );
	}
}
