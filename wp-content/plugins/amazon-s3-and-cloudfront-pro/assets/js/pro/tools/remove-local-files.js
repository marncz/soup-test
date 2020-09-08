(function( $, as3cfModal ) {

	as3cfpro.Tools = as3cfpro.Tools ? as3cfpro.Tools : {};

	/**
	 * The object that handles the remove local files tool.
	 */
	as3cfpro.Tools.RemoveLocalFiles = {
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
		if ( null != as3cfpro.Sidebar.tools.media.remove_local_files ) {
			as3cfpro.Tools.RemoveLocalFiles.toggleMediaSettings( as3cfpro.Sidebar.tools.media.remove_local_files, 'remove_local_files' );
		}

		// Toggle settings lock on sidebar status change
		$( '#remove_local_files' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.RemoveLocalFiles.toggleMediaSettings( status, 'remove_local_files' );
		} );
	} );

})( jQuery, as3cfModal );
