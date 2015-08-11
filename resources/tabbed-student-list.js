( function() {
	function tabbed_student_list( $) {
		var gadget   = $( ".wpan_tabbed_student_list_gadget" );
		var settings = "object" === typeof wpan_tabbed_student_list ? wpan_tabbed_student_list : null;
		var css_rsc;

		/**
		 * Handles dynamic loading of panel content.
		 *
		 * @param event
		 * @param ui
		 */
		function load_panel( event, ui ) {
			var panel = ui.newPanel;
			var id    = panel.attr( "id" );

			// Already loaded? Our work is done
			if ( "1" == panel.data( "populated" ) ) return;

			// Set temp loading text
			panel.html( settings.loading_html );

			// Request new panel data
			var request = {
				action: "wpan_tabbed_student_list_get_panel",
				check:  settings.check,
				tab:    id.substr( id.lastIndexOf( "-" ) + 1 ),
				connected_students: settings.connected_students
			};

			$.post( settings.ajax_url, request, function( response ) {
				// Bad response?
				if ( "undefined" === typeof response.status || "success" !== response.status ) {
					panel.html( settings.loading_failure_html );
					return;
				}

				panel.html( response.html );
				panel.data( "populated", "1" );
			}, "json" );
		}



		// Bail if expected elements/data are missing else continue with tabs set up
		if ( null === settings || gadget.length < 1 ) return;
		gadget.tabs( { activate: load_panel } );

		// Pull in stylesheet if not already present
		css_rsc = $( "link[href='" + settings.css_url + "']" );
		if ( css_rsc.length < 1 ) $( "head" ).append( "<link rel='stylesheet' type='text/css' href='" + settings.css_url + "'>" );

	}

	function prereqs() {
		if ( "function" !== typeof jQuery )         return false;
		if ( "object"   !== typeof jQuery.ui )      return false;
		if ( "function" !== typeof jQuery.ui.tabs ) return false;
		return true;
	}

	if ( prereqs() ) tabbed_student_list( jQuery );
} )();