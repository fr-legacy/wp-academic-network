<?php
$action = isset($action) ? $action : esc_attr(Teachblog_Form::post_url());
$username = Teachblog_Form::is_posted('username') ? esc_attr($_POST['username']) : '';
$password_1 = Teachblog_Form::is_posted('password_1') ? esc_attr($_POST['password_1']) : '';
$password_2 = Teachblog_Form::is_posted('password_2') ? esc_attr($_POST['password_2']) : '';
$title = Teachblog_Form::is_posted('blog_title') ? esc_attr($_POST['blog_title']) : '';
$description = Teachblog_Form::is_posted('blog_description') ? esc_attr($_POST['blog_description']) : '';
?>

<?php do_action('teachblog_before_blog_request_form') ?>
<form action="<?php echo $action ?>" method="post">
    <div class="teachblog blog-request form">

        <?php do_action('teachblog_editor_before_notices') ?>
        <?php if (isset($notices) and is_array($notices) and count($notices) >= 1): ?>
            <div class="section notices">
                <?php foreach ($notices as $type => $items): ?>
                    <div class="<?php esc_attr_e($type) ?>">
                        <?php foreach ($items as $message): ?>
                            <p> <?php esc_html_e($message) ?> </p>
                        <?php endforeach ?>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php wp_nonce_field('teachblog_blog_request', 'teachblog_check') ?>

        <div class="section newuser">
            <?php if ($is_user and !$has_blog) { ?>
                <div class="inline notice"> <p> <?php _e('<strong>You are already logged in!</strong> '
                    .'If you want to request a blog simply leave the username and password fields blank and proceed to '
                    .'the next section. If you are helping someone else, or need to generate a separate user account '
                    .'for some other reason, please go ahead and complete this section.', 'teachblog') ?> </p>
                </div>
            <?php } elseif ($is_user and $has_blog) { ?>
                <div class="inline notice"> <p> <?php _e('<strong>You are already have a blog!</strong> '
                    .'If you want to request an <strong>additional</strong> blog simply leave the username and '
                    .'password fields blank and proceed to the next section. If you are helping someone else, or need '
                    .'to generate a separate user account for some other reason, please go ahead and complete this '
                    .'section.', 'teachblog') ?> </p>
                </div>
            <?php } ?>

            <label for="username"> <?php _e('What username do you wish to use?', 'teachblog') ?> </label>
            <input type="text" idrequested="username" name="username" value="<?php echo $username ?>" />

            <label for="password_1"> <?php _e('Please enter your password twice', 'teachblog') ?> </label>
            <input type="password" id="password_1" name="password_1" value="<?php echo $password_1 ?>" />
            <input type="password" id="password_2" name="password_2" value="<?php echo $password_2 ?>" />
        </div>

        <div class="section newblog">
            <label for="blog_title"> <?php _e('What do you want to call your blog?', 'teachblog') ?> </label>
            <input type="text" id="blog_title" name="blog_title" value="<?php echo $title ?>" />

            <label for="blog_description"> <?php _e('Add an optional description for your blog', 'teachblog') ?> </label>
            <textarea id="blog_description" name="blog_description" cols="40" rows="5"><?php echo $description ?></textarea>
        </div>

        <div class="section submit">
            <label for="submit"> <?php _e('Click on the submit button to send your request', 'teachblog') ?> </label>
            <input type="submit" id="submit" name="submit-blog-request" value="<?php esc_attr_e('Submit request', 'teachblog') ?>" />
        </div>

    </div>
</form>