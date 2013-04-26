<?php
// Expected vars
$student_blogging = !isset($student_blogging) ? false : $student_blogging;
?>

<p><?php _e('You can turn specific modules on and off here.', TEACHBLOG_I18N) ?></p>

<?php
$table = new Teachblog_Admin_Table;

$table->use_checkbox(false);
$table->set_columns(array(__('Module', TEACHBLOG_I18N), __('Active', TEACHBLOG_I18N)));

$table->set_data(array(
	array(
		'<strong>'.__('Student Blogging', TEACHBLOG_I18N).'</strong><br/>'
		.__('Provides tools to let students submit their own content and interact with others', TEACHBLOG_I18N),
		'<div class="onoffswitch"> <input type="checkbox" name="student_blogging" value="1" checked="checked" /> </div>'
	)
));
$table->render();
?>