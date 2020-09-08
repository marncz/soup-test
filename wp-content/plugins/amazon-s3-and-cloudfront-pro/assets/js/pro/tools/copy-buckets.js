(function( $, as3cfModal ) {

	as3cfpro.Tools = as3cfpro.Tools ? as3cfpro.Tools : {};

	/**
	 * The object that handles the copy buckets tool.
	 */
	as3cfpro.Tools.CopyBuckets = {
		/**
		 * Maybe lock/unlock settings.
		 */
		toggleMediaSettings: function( status, tool ) {
			if ( status.is_queued || status.is_paused || status.is_cancelled ) {
				as3cf.Settings.Media.lock( tool );

				return;
			}

			as3cf.Settings.Media.unlock( tool );
		}
	};

	// Event Handlers
	$( document ).ready( function() {
		// Toggle settings lock on page load
		if ( null != as3cfpro.Sidebar.tools.media.copy_buckets ) {
			as3cfpro.Tools.CopyBuckets.toggleMediaSettings( as3cfpro.Sidebar.tools.media.copy_buckets, 'copy_buckets' );
		}

		// Toggle settings lock on sidebar status change
		$( '#copy_buckets' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.CopyBuckets.toggleMediaSettings( status, 'copy_buckets' );
		} );
	} );

})( jQuery, as3cfModal );
