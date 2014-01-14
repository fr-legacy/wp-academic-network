if ( undefined !== typeof jQuery ) jQuery( document ).ready( function( $ ) {
	// Key components and vars
	var worker_buttons = $( "#worker_buttons" );
	var run_worker = $( "#run_worker" );
	var stop_worker = $( "#stop_worker" );
	var worker_advice = $( "#worker_advice_js" );
	var worker_progress = $( "#worker_progress" );
	var worker_progress_text = $( "#worker_progress_text" );
	var do_work = false;
	var working = false;


	/**
	 * Controls the workdot animation used to visually indicate if work is in progress.
	 */
	function workdottery() {
		var workdots = $( ".workdots" ).find( ".dot" );
		var is_animated = false;
		var animator;

		function animate() {
			workdots.each( function() {
				if ( 2 === Math.floor( Math.random() * 4 ) ) return;
				$( this ).removeClass( "alt" ).removeClass( "active" );
				switch ( Math.floor( Math.random() * 4 ) ) {
					case 1: $( this ).addClass( "alt" ); break;
					case 2: $( this ).addClass( "active" ); break;
					case 3: $( this ).addClass( "alt" ).addClass( "active" ); break;
				}
			} );
		}

		/**
		 * Sets the dot animation in motion.
		 */
		function start() {
			if ( is_animated ) return;
			animator = setInterval( animate, 186 );
			is_animated = true;
		}

		/**
		 * Stops the animation and restores normal appearance.
		 */
		function stop() {
			clearInterval( animator );
			is_animated = false;
			workdots.each( function() {
				$( this).removeClass( "alt").removeClass( "active" );
			} );
		}

		/**
		 * Checks periodically to see if the animation should be turned on or off.
		 */
		function checker() {
			if ( working ) start();
			else stop();
		}

		// Check the work status periodically
		setInterval( checker, 375 );
	}

	/**
	 * Handles realtime processing.
	 */
	function processor() {
		/**
		 * If a request cycle is in progress.
		 *
		 * @type {boolean}
		 */
		var in_progress = false;

		/**
		 * Flag is set after the job completes and the relevant message to the customer
		 * has been displayed.
		 *
		 * @type {boolean}
		 */
		var completed = false;

		/**
		 * Keeps track of bad responses being returned from the server.
		 *
		 * @type {number}
		 */
		var bad_responses = 0;


		/**
		 * Displays a "job complete" message.
		 */
		function show_completion_msg() {
			if ( completed ) return;
			var completed = true;

			var controls = $( "#worker_controls" );
			var msg = '<div class="section_wrapper warning"> <p> ' + wpan_worker.completion_msg + ' </p> </div>';

			controls.fadeOut( "slow", function() { controls.after( msg ) } );
		}

		/**
		 * Updates the progress indicator.
		 *
		 * @param processed
		 * @param total
		 */
		function progress_bar( processed, total ) {
			// Avoid a div-by-0 error
			total = ( 0 === total ) ? 1 : total;

			var pc = Math.floor( ( processed / total ) * 100 ) + "%";
			worker_progress.width( pc );
			worker_progress_text.html( pc );
		}

		/**
		 * Updates the check variables used as request validators (and to stop them from
		 * expiring).
		 *
		 * @param check
		 * @param typecheck
		 */
		function update_check_vars( check, typecheck ) {
			wpan_worker.check = check;
			wpan_worker.typecheck = typecheck;
		}

		/**
		 * Assesses the response object to see if it contains the expected fields and that they
		 * are of the expected type.
		 *
		 * @param response_object
		 */
		function valid_response( response_object ) {
			if ( undefined === response_object.total_rows )
				return false;

			if ( undefined === response_object.remaining_rows )
				return false;

			return true;
		}

		/**
		 * Checks the response to determine if the job is complete or should continue running.
		 */
		function response( data ) {
			in_progress = false;

			// Has the server indicated that the job is complete and was a job in progress?
			if ( undefined !== data.complete && do_work ) {
				show_completion_msg();
				do_work = false;
			}

			// Valid response?
			if ( valid_response( data ) && bad_responses > 0 ) {
				bad_responses--;
			}
			// Invalid response and too many fails?
			if ( ! valid_response( data ) && bad_responses > 5 ) {
				do_work = false;
				return;
			}
			// Invalid response - opportunity to try again
			else if ( ! valid_response( data ) ) {
				bad_responses++;
				return;
			}

			// Update progress stats
			var total = parseInt( data.total_rows );
			var remaining = parseInt( data.remaining_rows );
			var processed = total - remaining;

			progress_bar( processed, total );
			update_check_vars( data.check, data.typecheck );
		}

		/**
		 * Sends a message to the server to continue processing (unless we are
		 * still waiting for an existing response).
		 */
		function prompt() {
			if ( in_progress ) return;
			working = in_progress = true;
			$.post( ajaxurl, wpan_worker, response, 'json' );
		}

		/**
		 * Checks if we should be working and triggers the prompt() function to
		 * connect with the server.
		 */
		function manager() {
			if ( do_work ) prompt();
			if ( ! do_work && ! in_progress ) working = false;
		}

		if ( undefined === wpan_worker || undefined === ajaxurl ) return;
		setInterval( manager, 540 );
	}

	/**
	 * Controls starting and stopping the worker process.
	 */
	function start_stopper() {
		run_worker.click( function( event ) {
			do_work = true;
			event.stopImmediatePropagation();

			run_worker.removeClass( "button-primary").addClass( "button-secondary" );
			stop_worker.addClass( "button-primary" ).removeClass( "button-secondary" );

			return false;
		} );

		stop_worker.click( function( event ) {
			do_work = false;
			event.stopImmediatePropagation();

			run_worker.addClass( "button-primary").removeClass( "button-secondary" );
			stop_worker.removeClass( "button-primary").addClass( "button-secondary" );

			return false;
		} );
	}

	// Show the buttons and hide the no/broken JS message
	worker_buttons.removeClass( "hidden" );
	worker_advice.addClass( "hidden" );

	// Set thing in motion
	start_stopper();
	processor();
	workdottery();

	// Run immediately?
	if ( undefined !== wpan_worker.immediate ) run_worker.trigger( "click" );
} );