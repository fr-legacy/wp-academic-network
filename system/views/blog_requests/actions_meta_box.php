<div>
    <p> <?php _e('You can approve this account request or else trash it.', 'teachblog') ?> </p>
    <?php wp_nonce_field('teachblog_account_request', 'teachblog_approval') ?>
    <input type="submit" class="button button-primary large" name="approve-request" value="<?php esc_attr_e('Approve!', 'teachblog') ?>" />
    <input type="submit" class="button button-secondary" name="trash-request" value="<?php esc_attr_e('Trash', 'teachblog') ?>" />
</div>