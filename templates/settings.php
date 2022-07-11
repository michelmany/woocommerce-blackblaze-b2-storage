<form method="post" id="mainform" action="">
    <h1 style="font-size:23px; font-weight:400; padding:9px 0 4px; line-height: 1.3;"><?php _e('Backblaze B2 Storage', 'wc_backblaze_b2'); ?></h1>
    <hr>

    <?php
    if (isset($_POST['save'])) {
        echo '<div class="notice notice-success is-dismissible" style="margin:5px 15px 2px 0;"><p></button>' . esc_html__('Your settings have been saved.', 'wc_backblaze_b2') . '</p></div>';
    }
    ?>

    <h3><?php _e('Security Credentials', 'wc_backblaze_b2'); ?></h3>

    <table class="form-table">
        <tbody>
        <tr>
            <th
                scope="row"
                class="titledesc">
                <?php esc_html_e('Access Key ID', 'wc_backblaze_b2'); ?>
            </th>
            <td class="forminp">
                <input
                    name="woo_backblaze_access_key"
                    id="woo_backblaze_access_key"
                    type="password"
                    style="min-width:300px;"
                    value="<?php echo esc_attr($admin_options['backblaze_access_key']); ?>">
                <p class="description">
                    <?php esc_html_e('Your Backblaze Cloud Services keyID.', 'wc_backblaze_b2'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row" class="titledesc">
                <?php esc_html_e('Secret Access Key', 'wc_backblaze_b2'); ?>
            </th>
            <td class="forminp">
                <input
                    name="woo_backblaze_access_secret"
                    id="woo_backblaze_access_secret"
                    type="password"
                    style="min-width:300px;"
                    value="<?php echo esc_attr($admin_options['backblaze_access_secret']); ?>">
                <br>
                <p class="description">
                    <?php esc_html_e('Your Backblaze Cloud Services applicationKey.', 'wc_backblaze_b2'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row" class="titledesc">
                <?php esc_html_e('URL Valid Period', 'wc_backblaze_b2'); ?>
            </th>
            <td class="forminp">
                <input
                    name="woo_backblaze_url_period"
                    id="woo_backblaze_url_period"
                    type="text"
                    style="min-width:100px;"
                    value="<?php echo esc_attr($admin_options['backblaze_url_period']); ?>">
                <p class="description">
                    <?php esc_html_e('Time in minutes the URL are valid for downloading, default is 1 minute.', 'wc_backblaze_b2'); ?>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
    <p class="submit">
        <input name="save" class="button button-primary" type="submit" value="<?php esc_attr_e('Save changes', 'wc_backblaze_b2'); ?>"/>
    </p>
</form>
