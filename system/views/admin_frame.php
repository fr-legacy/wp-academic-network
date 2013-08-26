<div class="wrap teachblog">
	<div class="icon32"></div>
	<h2><?php esc_html_e($title) ?></h2>

	<?php echo $menu ?>

	<br/>

	<form method="post" enctype="multipart/form-data" action="<?php echo Teachblog_Form::admin_url() ?>">
		<div class="content">
			<?php echo $content ?>
		</div>
	</form>
</div>