jQuery(document).ready(function($) {
	var wrap = $(".wrap.teachblog");
	var main_action = $(wrap).find("input.main-action");
	var last_change = 0;

	function get_timestamp() {
		var date = new Date();
		return date.getTime();
	}

	/**
	 * Change handler for on/off switches
	 */
	function on_off_update() {
		var input = $(this).find("input[type='checkbox']");
		$(this).find("span").removeClass("active");
		if (input.attr("checked") === "checked") {
			input.removeAttr("checked");
			$(this).find("span.off").addClass("active");
		}
		else {
			input.attr("checked", "checked");
			$(this).find("span.on").addClass("active");
		}
		$(input).trigger("change");
	}

	/**
	 * On/off switch user interface element.
	 */
	function make_on_off_switch() {
		var input = $(this).find("input[type='checkbox']");
		$(input).hide();

		var on_active = "";
		var off_active = "";

		if (input.attr("checked") === "checked") on_active = " active";
		else off_active = " active";

		$(this).prepend('<span> <span class="on'+on_active+'">'+teachblog.on+'</span> '
			+'<span class="off'+off_active+'">'+teachblog.off+'</span> </span>');

		$(this).click(on_off_update);
	}

	// Convert relevant inputs to on off switches
	$(wrap).find("div.onoffswitch").each(make_on_off_switch);

	/**
	 * Provides a visual cue to help users remember that they may have changes they need to save.
	 */
	function wiggle_action_button() {
		// We only wiggle if it has been more than 7.4 secs have elapsed since the last change
		if (get_timestamp() - last_change >= 7400) {
			$(main_action).animate(
				{ 'margin-left': '+=12' }, 234, function() { $(this).animate(
					{ 'margin-left': '-=12' }, 340
				)}
			);
		}
		last_change = get_timestamp();
	}

	// If we have a main action button, provide visual cues when settings have changed
	if (main_action.length > 0)
		$(wrap).find("input,select").change(wiggle_action_button);
});