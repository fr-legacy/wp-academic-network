if ( "function" === typeof jQuery ) jQuery( document ).ready( function( $ ) {
	var wpan = $( ".wpan" );

	function search_pagination_fix() {
		var search_area  = wpan.find( ".search" );
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
		var editable_fields = wpan.find( ".inline-editable.user-display-name" );

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

	function tagging_support() {
		function remove_tag() {
			var field   = $( this ).parent( ".user-tag" );
			var request = {
				action:  "wpan_remove_user_tag",
				check:   field.data( "check" ),
				tag:     field.data( "tag" ),
				user_id: field.data( "user_id" )
			};

			$.post( ajaxurl, request, remove_tag_response, "json" );
		}

		function remove_tag_response( data ) {
			if ( data.result !== "success" ) return;
			wpan.find( ".user-tag[data-tag='" + data.tag + "'][data-user_id='" + data.user_id + "']").remove();
		}

		function show_tagging_dialog() {
			var add_link = $( this )
			var controls = add_link.parent( ".user-tagging-controls" );
			var input    = controls.find( "input" );

			add_link.hide()
			input.show().focus().keydown( function( event ) {
				// Cancel?
				if ( 27 === event.keyCode ) {
					add_link.show();
					input.hide();

					event.stopPropagation();
					return false;
				}

				// Submit?
				if ( 13 === event.keyCode ) {
					var tags    = $( this ).val();
					var check   = controls.data( "check" );
					var user_id = controls.data( "user_id" );

					event.stopPropagation();
					submit_tags( user_id, tags, check );
					return false;
				}
			} );
		}

		function submit_tags( user_id, tags, check ) {
			var request = {
				action:  "wpan_add_user_tags",
				user_id: user_id,
				tags:    tags,
				check:   check
			};

			$.post( ajaxurl, request, submit_tags_response, "json" );
		}

		function submit_tags_response( data ) {
			if ( data.result !== "success" ) return;

			var controls = wpan.find( ".user-tagging-controls[data-user_id='" + data.user_id + "']" );
			var link     = controls.find( "a" );
			var input    = controls.find( "input" );

			controls.before( data.html );
			input.val( "" ).hide();
			link.show();
		}

		wpan.find( ".user-tag" ).find( ".remove" ).click( remove_tag );
		wpan.find( ".user-tagging-controls a" ).click( show_tagging_dialog ) ;
	}

	search_pagination_fix();
	inline_displayname_editing();
	tagging_support();
} );