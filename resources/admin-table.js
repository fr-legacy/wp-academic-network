if ( "function" === typeof jQuery ) jQuery( document ).ready( function( $ ) {
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
} );