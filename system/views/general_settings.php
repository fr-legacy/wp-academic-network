<?php
$module_table->use_checkbox(true)->set_columns(array(
	__('Module', 'teachblog'),
	__('Active', 'teachblog')
));

$module_list = $modules->get_modules();

foreach ($module_list as $module) {
	list($title, $description, $slug) = $module;
	$summary = "<strong> $title </strong> <br/> $description";
	$on_off = Teachblog_Form::on_off_switch($slug, 1, $modules->is_enabled($slug));
	$module_table->add_data_row($slug, array($summary, $on_off));
}

$module_table->render();