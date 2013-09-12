<p>
 <?php _e('This widget will display the recent posts from all across all student blogs.', 'teachblog'); ?>
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
	$id = $widget->get_field_id('show');
	$name = $widget->get_field_name('show');
	?>
	<label for="widget-<?php echo $id ?>"> <?php _e('Number of posts to show:', 'teachblog') ?> </label>
	<input type="text" id="<?php echo $id ?>" name="<?php echo $name ?>" value="<?php esc_attr_e($show) ?>" size="3" />
</p>

<p>
	<?php
	$id = $widget->get_field_id('autohide');
	$name = $widget->get_field_name('autohide');
	?>
	<label for="widget-<?php echo $id ?>"> <?php _e('Hide if empty:', 'teachblog') ?> </label>
	<input type="checkbox" id="<?php echo $id ?>" name="<?php echo $name ?>" value="1" size="3" <?php echo $autohide ? 'checked="checked"' : '' ?>/>
</p>