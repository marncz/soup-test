(function( $, as3cfModal ) {

	as3cfpro.Tools = as3cfpro.Tools ? as3cfpro.Tools : {};

	/**
	 * The object that handles the downloader tool.
	 */
	as3cfpro.Tools.Downloader = {
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
		if ( null != as3cfpro.Sidebar.tools.media.downloader ) {
			as3cfpro.Tools.Downloader.toggleMediaSettings( as3cfpro.Sidebar.tools.media.downloader, 'downloader' );
		}
		if ( null != as3cfpro.Sidebar.tools.media.download_and_remover ) {
			as3cfpro.Tools.Downloader.toggleMediaSettings( as3cfpro.Sidebar.tools.media.download_and_remover, 'download_and_remover' );
		}

		// Toggle settings lock on sidebar status change
		$( '#downloader' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.Downloader.toggleMediaSettings( status, 'downloader' );
		} );
		$( '#download_and_remover' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.Downloader.toggleMediaSettings( status, 'download_and_remover' );
		} );
	} );

})( jQuery, as3cfModal );
