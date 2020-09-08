<?php

namespace DeliciousBrains\WP_Offload_Media\Pro\Providers\Delivery;

use AS3CF_Utils;
use DeliciousBrains\WP_Offload_Media\Aws3\Aws\CloudFront\UrlSigner;
use DeliciousBrains\WP_Offload_Media\Items\Item;
use DeliciousBrains\WP_Offload_Media\Providers\Delivery\AWS_CloudFront;

class AWS_CloudFront_Pro extends AWS_CloudFront {

	/**
	 * @var array
	 */
	protected static $signed_urls_key_id_constants = array(
		'AS3CF_AWS_CLOUDFRONT_SIGNED_URLS_KEY_ID',
	);

	/**
	 * @var array
	 */
	protected static $signed_urls_key_file_path_constants = array(
		'AS3CF_AWS_CLOUDFRONT_SIGNED_URLS_KEY_FILE_PATH',
	);

	/**
	 * @var array
	 */
	protected static $signed_urls_object_prefix_constants = array(
		'AS3CF_AWS_CLOUDFRONT_SIGNED_URLS_OBJECT_PREFIX',
	);

	/**
	 * A short description of what features the delivery provider enables.
	 *
	 * @return string
	 */
	public function features_description() {
		return __( 'Fast, Private Media Supported', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Title used in various places for the Signed URLs Key ID.
	 *
	 * @return string
	 */
	public static function signed_urls_key_id_name() {
		return __( 'CloudFront Key Pair Access Key ID', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Description used in various places for the Signed URLs Key ID.
	 *
	 * @return string
	 */
	public static function signed_urls_key_id_description() {
		return __( 'Any files set to private need a signed URL that includes the Access Key ID from a CloudFront Key Pair.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Title used in various places for the Signed URLs Key File Path.
	 *
	 * @return string
	 */
	public static function signed_urls_key_file_path_name() {
		return __( 'CloudFront Private Key File Path', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Description used in various places for the Signed URLs Key File Path.
	 *
	 * @return string
	 */
	public static function signed_urls_key_file_path_description() {
		return __( 'Any files set to private need to have their URLs signed with the Private Key File downloaded from the CloudFront Key Pair.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * Description used in various places for the Signed URLs Private Object Prefix.
	 *
	 * @return string
	 */
	public static function signed_urls_object_prefix_description() {
		return __( 'Any files set to private will be stored at this path prepended to the path configured above. An Amazon CloudFront behaviour is then set up to restrict public access to the files at this path.', 'amazon-s3-and-cloudfront' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_signed_url( Item $as3cf_item, $path, $domain, $scheme, $timestamp, $headers = array() ) {
		if ( static::use_signed_urls_key_file() ) {
			$path = $as3cf_item->private_prefix() . $path;

			if ( $this->as3cf->private_prefix_enabled() ) {
				$item_path      = $this->as3cf->maybe_update_delivery_path( $path, $domain, $timestamp );
				$item_path      = AS3CF_Utils::encode_filename_in_path( $item_path );
				$private_prefix = AS3CF_Utils::trailingslash_prefix( static::get_signed_urls_object_prefix() );

				// If object in correct private prefix, sign it.
				if ( 0 === strpos( $item_path, $private_prefix ) ) {
					$url                   = $scheme . '://' . $domain . '/' . $item_path;
					$key_id                = static::get_signed_urls_key_id();
					$private_key_file_path = static::get_signed_urls_key_file_path();

					$cf_url_signer = new UrlSigner( $key_id, $private_key_file_path );

					return $cf_url_signer->getSignedUrl( $url, $timestamp );
				}
			}
		}

		// Not set up for signing or in different private prefix, punt to default implementation.
		return parent::get_signed_url( $as3cf_item, $path, $domain, $scheme, $timestamp, $headers );
	}
}
