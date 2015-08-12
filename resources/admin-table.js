if ( "function" === typeof jQuery ) jQuery( document ).ready( function( $ ) {
	function search_pagination_fix() {
		var search_area  = $( ".wpan" ).find( ".search" );
		var search_field = search_area.find( "input[type='search']" );
		var search_btn   = search_area.find( "input[type='submit']" );

		/**
		 * When the search field is changed, alter the search submit value to "new_search"
		 * in order to indicate that pagination should be reset to page 1 etc.
		 */
		search_field.change( function() {
			search_btn.attr( "name", "new_search" );
		});
	}

	function inline_displayname_editing() {
		var editable_fields = $( ".wpan" ).find( ".inline-editable.user-display-name" );

		function restore( user_id ) {
			var field = editable_fields.filter( "[data-user-id='" + user_id + "']" );
			if ( ! field.length ) return;

			field.html( field.data( "original-content" ) );
			field.removeAttr( "disabled" );
			field.data( "inline-mode", "off" );
		}

		function update( user_id, new_name ) {
			var field = editable_fields.filter( "[data-user-id='" + user_id + "']" );
			if ( ! field.length ) return;

			field.html( new_name );
			field.removeAttr( "disabled" );
			field.data( "inline-mode", "off" );
		}

		function update_response( data ) {
			if ( "fail" === data.result ) {
				restore( data.user_id );
			}

			if ( "success" === data.result ) {
				update( data.user_id, data.new_name );
			}
		}

		function submit( user_id, new_name, check ) {
			var request =  {
				action:   "wpan_student_display_name",
				check:    check,
				user_id:  user_id,
				new_name: new_name
			};

			$.post( ajaxurl, request, update_response, "json" );
		}

		function click_handler() {
			var $this        = $( this );
			var pre_existing = $this.html();
			var cell_width   = $this.parents( "td" ).width();
			var new_field    = "<input type='text' value='" + pre_existing.trim() + "' />";
			var input_field  = $this.find( "input" );

			// Already in edit mode?
			if ( "on" === $this.data( "inline-mode" ) )
				return;

			// Or has it been disabled for submission?
			if ( 1 === input_field.length && "disabled" === input_field.attr( "disabled" ) )
				return;

			// Switch to edit mode
			$this.data( "inline-mode", "on" );
			$this.data( "original-content", pre_existing );
			$this.html( new_field );
			input_field = $( this ).find( "input" );
			input_field.width( cell_width - 50).focus();

			// Capture "return"/"enter"-based submits and "escape"-key cancels
			input_field.keydown( function( event ) {
				// Cancel?
				if ( 27 === event.keyCode ) {
					$this.html( pre_existing );
					$this.data( "inline-mode", "off" );

					event.stopPropagation();
					return false;
				}

				// Submit?
				if ( 13 === event.keyCode ) {
					var new_name = input_field.val();
					var check    = $this.data( "check" );
					var user_id  = $this.data( "user-id" );

					event.stopPropagation();
					input_field.attr( "disabled", "disabled" );
					submit( user_id, new_name, check );
					return false;
				}
			} );
		}

		editable_fields.click( click_handler );
	}

	search_pagination_fix();
	inline_displayname_editing();
} );