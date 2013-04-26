<?php
// Expected vars
$student_blogging = !isset($student_blogging) ? false : $student_blogging;
?>

<p><?php _e('You can turn specific modules on and off here.', TEACHBLOG_I18N) ?></p>
<dl class="options">
	<dt><?php _e('Student blogging facilities', TEACHBLOG_I18N) ?></dt>
	<dd><div class="onoffswitch">
			<input type="checkbox" name="student-blogging" value="on" class="onoffswitch" <?php echo $student_blogging ? 'checked="checked"' : '' ?> />
	</div></dd>
</dl>