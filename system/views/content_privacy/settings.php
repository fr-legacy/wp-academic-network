<input type="hidden" name="teachblog_privacy_settings" value="<?php esc_attr_e(wp_create_nonce('privacy')) ?>" />
<div class="section">

	<h3> <?php _e('Privacy mode', 'teachblog') ?> </h3>
	<p>
		<label for="mode"> <ul>
			<li> <?php _e('Using a gateway login page means Teachblog will attempt to channel all unauthenticated '
				.'users to a page where they can login.', 'teachblog') ?> </li>
			<li> <?php _e('Auto-hiding of student content means that the site remains open, but student content will not be visible.',
				'teachblog') ?> </li>
		</ul> </label>
		<select name="mode" id="mode">
			<option value="disabled"> <?php _e('Disable content privacy', 'teachblog') ?> </option>
			<option value="gateway" <?php if ('gateway' === $mode) echo 'selected="selected"' ?>> <?php _e('Gateway login page', 'teachblog') ?> </option>
			<option value="selective" <?php if ('selective' === $mode) echo 'selected="selected"' ?>> <?php _e('Auto-hide student content', 'teachblog') ?> </option>
		</select>
	</p>

</div>

<div class="section">

	<h3> <?php _e('Gateway mode settings', 'teachblog') ?> </h3>

	<p>
		<label for="gateway_page"> <?php _e('You can choose a page which all non-authenticated users will be redirected '
			.'too. That page can then be customized to provide login facilities, etc.', 'teachblog') ?> </label>
		<br/>
		<?php echo Teachblog_Form::page_list($gateway_id, 'gateway_page', 'gateway_page') ?>
	</p>

</div>

<div class="section">

	<input id="doaction" class="button action main-action" type="submit" value="Apply &amp; Update" name="save">

</div>