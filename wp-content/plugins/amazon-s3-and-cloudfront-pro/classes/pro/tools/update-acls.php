<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Tools;

use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Background_Tool_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Processes\Update_ACLs_Process;
use DeliciousBrains\WP_Offload_Media\Pro\Background_Tool;

class Update_ACLs extends Background_Tool {

	/**
	 * @var string
	 */
	protected $tool_key = 'update_acls';

	/**
	 * @var string
	 */
	protected $tab = 'media';

	/**
	 * @var array
	 */
	protected static $show_tool_constants = array(
		'AS3CF_SHOW_UPDATE_ACLS_TOOL',
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
	 * Maybe start update acls process via post request.
	 *
	 * @param array $changed_keys
	 *
	 * @return array
	 */
	public function handle_post_request( $changed_keys ) {
		if (
			! empty( $_GET['action'] ) &&
			'update-acls' === $_GET['action'] &&
			! $this->as3cf->get_storage_provider()->needs_access_keys() &&
			$this->as3cf->get_setting( 'bucket' ) &&
			$this->as3cf->get_setting( 'use-bucket-acls' ) &&
			! empty( $_POST['update-acls'] )
		) {
			$this->handle_start();
		}

		return $changed_keys;
	}

	/**
	 * Should the Update ACLs prompt be the next action?
	 *
	 * @param string $action
	 * @param array  $changed_keys
	 *
	 * @return string
	 */
	public function action_for_changed_settings_key( $action, $changed_keys ) {
		// If no previous step, or this step already processed, shortcut out.
		if ( empty( $_GET['action'] ) || 'update-acls' === $_GET['action'] || ( ! empty( $_GET['prev_action'] ) && 'update-acls' === $_GET['prev_action'] ) ) {
			return $action;
		}

		if ( empty( $action ) && in_array( 'use-bucket-acls', $changed_keys ) && $this->as3cf->get_setting( 'use-bucket-acls' ) && $this->count_offloaded_media_files() ) {
			return 'update-acls';
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
		if ( ! empty( $_GET['action'] ) && 'update-acls' === $_GET['action'] ) {
			$storage_classes .= ' as3cf-update-acls';
		}

		return $storage_classes;
	}

	/**
	 * Load assets.
	 */
	public function load_assets() {
		parent::load_assets();

		$this->as3cf->enqueue_script( 'as3cf-pro-update-acls-script', 'assets/js/pro/tools/update-acls', array(
			'jquery',
			'wp-util',
		) );
	}

	/**
	 * Render modal in footer.
	 */
	public function render_modal() {
		if ( ! empty( $_GET['action'] ) && 'update-acls' === $_GET['action'] ) {
			$this->as3cf->render_view( 'modals/update-acls' );
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
			'error' => __( 'There was an error attempting to start the Update ACLs tool. Please try again.', 'amazon-s3-and-cloudfront' ),
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
		return __( 'Update Object ACLs', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get button text.
	 *
	 * @return string
	 */
	public function get_button_text() {
		return __( 'Begin Update', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get queued status text.
	 *
	 * @return string
	 */
	public function get_queued_status() {
		return __( 'Updating object ACLs in bucket.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Get more info text.
	 *
	 * @return string
	 */
	public function get_more_info_text() {
		return __( 'After disabling Block All Public Access for a bucket, you many need to update all the objects in the bucket to apply appropriate public and private ACLs so that they can be used correctly.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Message for error notice.
	 *
	 * @param null $message Optional message to override the default for the tool.
	 *
	 * @return string
	 */
	protected function get_error_notice_message( $message = null ) {
		$title   = __( 'Update ACLs Errors', 'amazon-s3-and-cloudfront' );
		$message = empty( $message ) ? __( 'Previous attempts at updating object ACLs have resulted in errors.', 'amazon-s3-and-cloudfront' ) : $message;

		return sprintf( '<strong>%1$s</strong> &mdash; %2$s', $title, $message );
	}

	/**
	 * Get background process class.
	 *
	 * @return Background_Tool_Process|null
	 */
	protected function get_background_process_class() {
		return new Update_ACLs_Process( $this->as3cf, $this );
	}
}
