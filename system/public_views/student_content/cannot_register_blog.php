<div class="teachblog has-blog-already">

    <h5><?php _e('You cannot register for a new blog', 'teachblog') ?></h5>

    <?php if ($has_blog): ?>

        <p><?php _e('Since you already have a blog there is no need to request a new one. Please speak with a '
            . 'teacher or administrator if you need help accessing your blog or if you need any other help.',
            'teachblog') ?></p>

    <?php elseif (!$has_blog and $is_user): ?>

        <p><?php _e('You are already a registered user &ndash; please  '
            . 'teacher or administrator if you need help accessing your blog or if you need any other help.',
            'teachblog') ?></p>

    <?php endif ?>
</div>