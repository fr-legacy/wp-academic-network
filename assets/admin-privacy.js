jQuery(document).ready(function ($) {
    var gateway_section = $("div#gateway_settings");
    var input_mode = $("select#mode");

    function show_hide() {
        if ("gateway" === input_mode.val()) gateway_section.slideDown();
        else gateway_section.slideUp();
    }

    input_mode.change(show_hide).change();
});