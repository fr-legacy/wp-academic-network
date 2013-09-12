<p>
 <?php _e('This widget can be used to display the posts of a designated student blogger.', 'teachblog'); ?>
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
	$id = $widget->get_field_id('author_id');
	$name = $widget->get_field_name('author_id');
	?>
	<label for="widget-<?php echo $id ?>"> <?php _e('Student blogger:', 'teachblog') ?> </label>
	<select id="<?php echo $id ?>" name="<?php echo $name ?>" class="widefat">
		<option value="0"> <?php _e('None selected', 'teachblog') ?> </option>
		<?php foreach ($bloggers as $blogger): ?>
			<option value="<?php esc_attr_e($blogger->ID) ?>"<?php if ($blogger->ID == $author_id) echo ' selected="selected"' ?>>
				<?php esc_html_e($blogger->user_login) ?>
			</option>
		<?php endforeach ?>
	</select>
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