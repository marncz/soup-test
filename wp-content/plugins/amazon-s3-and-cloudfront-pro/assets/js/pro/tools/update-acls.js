(function( $, as3cfModal ) {

	as3cfpro.Tools = as3cfpro.Tools ? as3cfpro.Tools : {};

	/**
	 * The object that handles the copy buckets tool.
	 */
	as3cfpro.Tools.UpdateACLs = {
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
		if ( null != as3cfpro.Sidebar.tools.media.update_acls ) {
			as3cfpro.Tools.UpdateACLs.toggleMediaSettings( as3cfpro.Sidebar.tools.media.update_acls, 'update_acls' );
		}

		// Toggle settings lock on sidebar status change
		$( '#update_acls' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.UpdateACLs.toggleMediaSettings( status, 'update_acls' );
		} );
	} );

})( jQuery, as3cfModal );
