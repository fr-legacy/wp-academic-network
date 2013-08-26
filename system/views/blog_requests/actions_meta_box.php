<div>
    <p> <?php _e('Please approve this request or trash it &ndash; you will then be taken back to the list of outstanding blog requests.', 'teachblog') ?> </p>
    <?php wp_nonce_field('teachblog_account_request', 'teachblog_approval') ?>
    <input type="hidden" name="request_id" value="<?php esc_attr_e($request_id) ?>" />
    <input type="submit" class="button button-primary large" name="approve-request" value="<?php esc_attr_e('Save Changes &amp; Approve!', 'teachblog') ?>" />
    <input type="submit" class="button button-secondary alignright" name="trash-request" value="<?php esc_attr_e('Trash', 'teachblog') ?>" />
</div>