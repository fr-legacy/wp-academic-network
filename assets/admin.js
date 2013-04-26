jQuery(document).ready(function($) {
	var wrap = $(".wrap.teachblog");

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

		$(this).click(on_off_update).trigger("click");
	}

	// Convert relevant inputs to on off switches
	$(wrap).find("div.onoffswitch").each(make_on_off_switch);
});