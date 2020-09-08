(function( $, as3cfModal ) {

	as3cfpro.Tools = as3cfpro.Tools ? as3cfpro.Tools : {};

	/**
	 * The object that handles the uploader tool.
	 */
	as3cfpro.Tools.Uploader = {
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
		if ( null != as3cfpro.Sidebar.tools.media.uploader ) {
			as3cfpro.Tools.Uploader.toggleMediaSettings( as3cfpro.Sidebar.tools.media.uploader, 'uploader' );
		}

		// Toggle settings lock on sidebar status change
		$( '#uploader' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.Uploader.toggleMediaSettings( status, 'uploader' );
		} );
	} );

})( jQuery, as3cfModal );
