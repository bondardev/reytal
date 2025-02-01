<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>Flatsome Menu Products</h1>
    <form method="post" action="">
        <?php wp_nonce_field('flatsome_menu_products_save', 'flatsome_menu_products_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="api_url">API URL</label></th>
                <td><input type="text" name="api_url" value="<?php echo esc_attr(get_option('flatsome_api_url')); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label for="api_key">Consumer Key</label></th>
                <td><input type="password" name="api_key" value="" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="api_secret">Consumer Secret</label></th>
                <td><input type="password" name="api_secret" value="" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="sku_list">Product SKUs (comma-separated)</label></th>
                <td><input type="text" name="sku_list" value="<?php echo esc_attr(get_option('flatsome_sku_list')); ?>" class="regular-text" required></td>
            </tr>
        </table>
        <p><input type="submit" name="save_settings" value="Save Settings" class="button button-primary"></p>
    </form>

    <h2>Import Products</h2>
    <form method="post" action="">
        <?php wp_nonce_field('flatsome_import_products', 'flatsome_import_products_nonce'); ?>
        <p><input type="submit" name="import_products" value="Import Products" class="button button-secondary"></p>
    </form>
</div>
