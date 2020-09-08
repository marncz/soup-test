(function( $, as3cfModal ) {

	var $movePublicObjectsPrompt = $( '.as3cf-move-objects-prompt .as3cf-move-public-objects' );
	var $movePrivateObjectsPrompt = $( '.as3cf-move-objects-prompt .as3cf-move-private-objects' );
	var $movePublicObjectsSelection = $( '.as3cf-move-objects-prompt #as3cf-move-public-objects-selection' );

	as3cfpro.Tools = as3cfpro.Tools ? as3cfpro.Tools : {};

	/**
	 * The object that handles the move objects tool.
	 */
	as3cfpro.Tools.MoveObjects = {
		/**
		 * Maybe lock/unlock settings.
		 */
		toggleMediaSettings: function( status, tool ) {
			if ( status.is_queued || status.is_paused || status.is_cancelled ) {
				as3cf.Settings.Media.lock( tool );

				return;
			}

			as3cf.Settings.Media.unlock( tool );
		},

		/*
		 * Toggle warning notice for move public objects.
		 * Only to be called when UI setting changed, not on document load.
		 */
		toggleUseYearMonthFoldersNotice: function() {
			if ( $( '#as3cf-use-yearmonth-folders' ).is( ':checked' ) ) {
				$( '#as3cf-move-public-objects-unsafe-notice-use-yearmonth-folders' ).hide();
			} else {
				$( '#as3cf-move-public-objects-unsafe-notice-use-yearmonth-folders' ).show();
			}
		},

		/*
		 * Toggle warning notice for move public objects.
		 * Only to be called when UI setting changed, not on document load.
		 */
		toggleObjectVersioningNotice: function() {
			if ( $( '#as3cf-object-versioning' ).is( ':checked' ) ) {
				$( '#as3cf-move-public-objects-unsafe-notice-object-versioning' ).hide();
			} else {
				$( '#as3cf-move-public-objects-unsafe-notice-object-versioning' ).show();
			}
		}
	};

	// Event Handlers
	$( document ).ready( function() {
		// Toggle settings lock on page load
		if ( null != as3cfpro.Sidebar.tools.media.move_objects ) {
			as3cfpro.Tools.MoveObjects.toggleMediaSettings( as3cfpro.Sidebar.tools.media.move_objects, 'move_objects' );
		}

		if ( null != as3cfpro.Sidebar.tools.media.move_public_objects ) {
			as3cfpro.Tools.MoveObjects.toggleMediaSettings( as3cfpro.Sidebar.tools.media.move_public_objects, 'move_public_objects' );
		}

		if ( null != as3cfpro.Sidebar.tools.media.move_private_objects ) {
			as3cfpro.Tools.MoveObjects.toggleMediaSettings( as3cfpro.Sidebar.tools.media.move_private_objects, 'move_private_objects' );
		}

		// Toggle settings lock on sidebar status change
		$( '#move_objects' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.MoveObjects.toggleMediaSettings( status, 'move_objects' );
		} );

		$( '#move_public_objects' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.MoveObjects.toggleMediaSettings( status, 'move_public_objects' );
		} );

		$( '#move_private_objects' ).on( 'status-change', function( event, status ) {
			as3cfpro.Tools.MoveObjects.toggleMediaSettings( status, 'move_private_objects' );
		} );

		// Handle dual-mode prompt by populating hidden 'move-public-objects' value if it exists and then let private modal submit.
		$movePublicObjectsSelection.each( function() {
			$movePublicObjectsPrompt.on( 'click', 'button', function( e ) {
				e.preventDefault();
				$movePublicObjectsSelection.val( $( this ).val() );
				$movePublicObjectsPrompt.hide();
				$movePrivateObjectsPrompt.show();
			} );
		} );

		$( '#as3cf-use-yearmonth-folders' ).on( 'change', function( e ) {
			as3cfpro.Tools.MoveObjects.toggleUseYearMonthFoldersNotice();
		} );

		$( '#as3cf-object-versioning' ).on( 'change', function( e ) {
			as3cfpro.Tools.MoveObjects.toggleObjectVersioningNotice();
		} );
	} );

})( jQuery, as3cfModal );
