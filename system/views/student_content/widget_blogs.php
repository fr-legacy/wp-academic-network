<p>
 <?php _e('This widget lists all student blogs.', 'teachblog'); ?>
</p>

<p>
	<?php
	$id = $widget->get_field_id('title');
	$name = $widget->get_field_name('title');
	?>
	<label for="widget-<?php echo $id ?>"> <?php _e('Title:', 'teachblog') ?> </label>
	<input type="text" id="<?php echo $id ?>" name="<?php echo $name ?>" value="<?php esc_attr_e($title) ?>" class="widefat" />
</p>

<p>
	<?php
	$id = $widget->get_field_id('hide_empties');
	$name = $widget->get_field_name('hide_empties');
	?>
	<label for="widget-<?php echo $id ?>"> <?php _e('Do not show empty blogs:', 'teachblog') ?> </label>
	<input type="checkbox" id="<?php echo $id ?>" name="<?php echo $name ?>" value="1" size="3" <?php echo $hide_empties ? 'checked="checked"' : '' ?>/>
</p>

<p>
	<?php
	$id = $widget->get_field_id('autohide');
	$name = $widget->get_field_name('autohide');
	?>
	<label for="widget-<?php echo $id ?>"> <?php _e('Hide if no blogs are found:', 'teachblog') ?> </label>
	<input type="checkbox" id="<?php echo $id ?>" name="<?php echo $name ?>" value="1" size="3" <?php echo $autohide ? 'checked="checked"' : '' ?>/>
</p>