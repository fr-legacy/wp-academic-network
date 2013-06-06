<table>
    <tr>
        <th scope="row"><?php _e('Blog title', 'teachblog') ?></th>
        <td> <input type="text" name="blog_title" value="<?php esc_attr_e($blog_title) ?>" /> </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Blog description (not required)', 'teachblog') ?></th>
        <td> <textarea name="blog_description" cols="40" rows="2"><?php esc_attr_e($blog_description) ?></textarea> </td>
    </tr>

    <tr>
        <th scope="row"><?php _e('User account', 'teachblog') ?></th>
        <td>
            <?php
            if (!$account_requested) {
                sprintf(__('Existing user %s wishes this blog to be created and assigned to him/her', 'teachblog'), esc_html($user_summary));
            }
            else {
                _e('The blog should be associated with the following new user (to be created):', 'teachblog');
                echo '<br/>';
                echo '<input type="text" name="requested_username" id="new_user_login" value="'.esc_attr($account_username).'" />';
            }
            ?>
            </p>
        </td>
    </tr>
</table>