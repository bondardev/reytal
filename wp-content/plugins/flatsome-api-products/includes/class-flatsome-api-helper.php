<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Flatsome_API_Helper
 *
 * Provides static methods for:
 * - Encrypting and decrypting API keys using wp_salt()
 * - Checking the connection to the external API
 * - Fetching products from the external API
 * - Importing products into WooCommerce
 * - Creating or updating WooCommerce products based on API data
 * - Uploading product images to the WordPress media library
 */
class Flatsome_API_Helper {

    /**
     * Encrypts the given API key using wp_salt().
     *
     * This method uses the first 16 bytes of the salt as the IV (Initialization Vector).
     * The encryption method used is AES-256-CBC.
     *
     * @param string $data The API key to encrypt.
     * @return string|false The encrypted key (base64 encoded) on success, or false on failure.
     */
    public static function encrypt_api_key($data) {
        // Check if the openssl_encrypt function is available.
        if ( function_exists('openssl_encrypt') ) {
            // Retrieve the encryption key from wp_salt(). wp_salt() returns a salt string from wp-config.php.
            $encryption_key = wp_salt();
            // Use the first 16 bytes of the salt as the IV (Initialization Vector).
            $iv = substr($encryption_key, 0, 16);
            // Encrypt the data using AES-256-CBC algorithm.
            $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryption_key, 0, $iv);
            // If encryption fails, log the error and return false.
            if ($encrypted === false) {
                error_log('openssl_encrypt failed');
                return false;
            }
            // Return the encrypted data encoded in base64.
            return base64_encode($encrypted);
        }
        // If openssl_encrypt is not available, return the original data.
        return $data;
    }

    /**
     * Decrypts the given API key using wp_salt().
     *
     * Uses the first 16 bytes of the salt as the IV.
     *
     * @param string $data The encrypted API key (base64 encoded).
     * @return string|false The decrypted API key on success, or false on failure.
     */
    public static function decrypt_api_key($data) {
        // Check if the openssl_decrypt function is available.
        if ( function_exists('openssl_decrypt') ) {
            // Retrieve the encryption key from wp_salt().
            $encryption_key = wp_salt();
            // Use the first 16 bytes of the salt as the IV.
            $iv = substr($encryption_key, 0, 16);
            // Base64-decode the encrypted data and then decrypt it using AES-256-CBC.
            $decrypted = openssl_decrypt(base64_decode($data), 'AES-256-CBC', $encryption_key, 0, $iv);
            // If decryption fails, log the error along with the encrypted data.
            if ($decrypted === false) {
                error_log("DECRYPT FAILED. Possibly wrong SALT or incorrect data: " . $data);
                return false;
            }
            // Return the decrypted API key.
            return $decrypted;
        }
        // If openssl_decrypt is not available, return the original data.
        return $data;
    }

    /**
     * Checks the API connection by making a test GET request.
     *
     * Retrieves the API URL, API key, and API secret from the WordPress options,
     * decrypts the keys, and makes a GET request to the WooCommerce REST API to fetch one product.
     *
     * @return bool True if the connection is successful, false otherwise.
     */
    public static function check_api_connection() {
        // Retrieve API settings from the database.
        $api_url    = get_option('flatsome_api_url');
        $api_key    = self::decrypt_api_key(get_option('flatsome_api_key'));
        $api_secret = self::decrypt_api_key(get_option('flatsome_api_secret'));

        // If any of the required settings are missing, return false.
        if (! $api_url || ! $api_key || ! $api_secret) {
            return false;
        }

        // Build the request URL by appending the REST API endpoint for products.
        $request_url = $api_url . "/wp-json/wc/v3/products?per_page=1";

        // Make a GET request using wp_remote_get with a 10-second timeout.
        $response = wp_remote_get($request_url, array(
            'headers' => array(
                // Set up Basic Authentication header with the API key and secret.
                'Authorization' => 'Basic ' . base64_encode($api_key . ':' . $api_secret),
            ),
            'timeout' => 10,
        ));

        // If the request resulted in an error, return false.
        if (is_wp_error($response)) {
            return false;
        }

        // Retrieve the body of the response.
        $body = wp_remote_retrieve_body($response);
        // Decode the JSON response into an associative array.
        $json_data = json_decode($body, true);

        // Check if the response code is 200 and the JSON data is a non-empty array.
        return (wp_remote_retrieve_response_code($response) === 200 && is_array($json_data) && !empty($json_data));
    }

    /**
     * Imports products from the external API into local WooCommerce.
     *
     * Retrieves settings from the database, fetches products by SKU, and for each product
     * calls the create_or_update_product() method. Accumulated messages are saved in a transient
     * for later display.
     */
    public static function import_products() {
        $import_messages = array();  // Initialize an array to hold import messages.

        // Retrieve API settings.
        $api_url    = get_option('flatsome_api_url');
        $api_key    = self::decrypt_api_key(get_option('flatsome_api_key'));
        $api_secret = self::decrypt_api_key(get_option('flatsome_api_secret'));
        $sku_list   = get_option('flatsome_sku_list');

        // Check if all required API settings are provided.
        if (! $api_url || ! $api_key || ! $api_secret || ! $sku_list) {
            $import_messages[] = 'Missing API settings.';
            // Save messages in a transient so they can be displayed later.
            set_transient('flatsome_import_notice', implode('<br>', $import_messages), 30);
            return;
        }

        // Convert the comma-separated SKU list to an array and trim spaces.
        $skus = array_map('trim', explode(',', $sku_list));
        if (empty($skus)) {
            $import_messages[] = 'No SKU provided.';
            set_transient('flatsome_import_notice', implode('<br>', $import_messages), 30);
            return;
        }

        // Fetch products from the external API.
        $products = self::fetch_products_from_api($api_url, $api_key, $api_secret, $skus);
        if (! $products) {
            $import_messages[] = 'No products found.';
            set_transient('flatsome_import_notice', implode('<br>', $import_messages), 30);
            return;
        }

        // Iterate over each product returned from the API.
        foreach ($products as $product_data) {
            // Create or update the WooCommerce product and pass the messages array by reference.
            self::create_or_update_product($product_data, $import_messages);
        }

        // Add a success message and save all messages to a transient.
        $import_messages[] = 'Import completed successfully.';
        set_transient('flatsome_import_notice', implode('<br>', $import_messages), 30);
    }

    /**
     * Fetches products from the external WooCommerce API using Basic Authentication.
     *
     * @param string       $api_url   The API URL.
     * @param string       $api_key   The API Consumer Key.
     * @param string       $api_secret The API Consumer Secret.
     * @param array|string $skus      An array or comma-separated list of SKUs.
     * @return array|null An array of products with necessary fields, or null if an error occurs.
     */
    public static function fetch_products_from_api($api_url, $api_key, $api_secret, $skus) {
        // Ensure $skus is an array.
        if (! is_array($skus)) {
            $skus = explode(',', $skus);
        }

        if (empty($skus)) {
            return null;
        }

        // Build a comma-separated, URL-encoded list of SKUs.
        $sku_list = implode(',', array_map('urlencode', $skus));
        
        // Build the API request URL.
        $request_url = $api_url . '/wp-json/wc/v3/products?sku=' . $sku_list;

        // Make a GET request with a 10-second timeout.
        $response = wp_remote_get($request_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':' . $api_secret),
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 10,
        ));

        // If an error occurs during the request, return null.
        if (is_wp_error($response)) {
            return null;
        }

        // Retrieve the response body and decode it from JSON.
        $body = wp_remote_retrieve_body($response);
        $products = json_decode($body, true);

        // Check for JSON errors or empty responses.
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($products) || empty($products)) {
            return null;
        }

        // Format each product to include only the necessary fields.
        foreach ($products as &$product) {
            $product = array(
                'id'                => $product['id'] ?? '',
                'name'              => $product['name'] ?? '',
                'description'       => $product['description'] ?? '',
                'short_description' => $product['short_description'] ?? '',
                'regular_price'     => $product['regular_price'] ?? '',
                'sale_price'        => $product['sale_price'] ?? '',
                'images'            => ! empty($product['images'][0]['src']) ? $product['images'][0]['src'] : '',
                'sku'               => $product['sku'] ?? '',
            );
        }

        return $products;
    }

    /**
     * Creates or updates a WooCommerce product based on the provided API data.
     *
     * If a product with the given SKU exists, it is updated; otherwise, a new product is created.
     * Any messages (errors or success) are appended to the provided $import_messages array.
     *
     * @param array $product_data The product data from the API.
     * @param array &$import_messages Reference to the array of import messages.
     */
    public static function create_or_update_product($product_data, &$import_messages) {
        // Ensure the product data is an array.
        if (! is_array($product_data)) {
            $import_messages[] = 'Error: Product data is not an array: ' . print_r($product_data, true);
            return;
        }

        // Check if the SKU is provided.
        if (empty($product_data['sku'])) {
            $import_messages[] = 'Error: SKU missing in product data.';
            return;
        }

        // Attempt to get the existing product ID by SKU.
        $existing_product_id = wc_get_product_id_by_sku($product_data['sku']);

        if ($existing_product_id) {
            // If the product exists, load it.
            $product = wc_get_product($existing_product_id);
            $import_messages[] = 'Updating product with SKU: ' . $product_data['sku'];
        } else {
            // Otherwise, create a new simple product.
            $product = new WC_Product_Simple();
            $product->set_sku($product_data['sku']);
            $import_messages[] = 'Creating new product with SKU: ' . $product_data['sku'];
        }

        // Update product fields if data is provided.
        if (! empty($product_data['name'])) {
            $product->set_name($product_data['name']);
        }
        if (! empty($product_data['description'])) {
            $product->set_description($product_data['description']);
        }
        if (! empty($product_data['short_description'])) {
            $product->set_short_description($product_data['short_description']);
        }
        if (isset($product_data['regular_price'])) {
            $product->set_regular_price($product_data['regular_price']);
        }
        if (isset($product_data['sale_price'])) {
            $product->set_sale_price($product_data['sale_price']);
        }

        // Set the product status to 'publish'.
        $product->set_status('publish');
        // Save the product and obtain its ID.
        $product_id = $product->save();

        if (! $product_id) {
            $import_messages[] = 'Error: Could not create/update product with SKU: ' . $product_data['sku'];
            return;
        }

        // If an image URL is provided, attempt to upload the image.
        if (! empty($product_data['images'])) {
            $image_id = self::upload_product_image($product_data['images'], $product_id, $import_messages);
            if ($image_id) {
                // Set the product's featured image.
                $product->set_image_id($image_id);
                $product->save();
            }
        }

        $import_messages[] = 'Product SKU: ' . $product_data['sku'] . ' successfully created/updated. ID: ' . $product_id;
    }

    /**
     * Uploads a product image to the WordPress media library and sets it as the product thumbnail.
     *
     * Downloads the image from the given URL, saves it to the uploads directory,
     * creates an attachment, and generates attachment metadata.
     *
     * @param string $image_url The URL of the image.
     * @param int    $product_id The product ID.
     * @param array  &$import_messages Reference to the array of import messages.
     * @return int|null The attachment ID on success, or null on failure.
     */
    public static function upload_product_image($image_url, $product_id, &$import_messages) {
        global $wpdb;
        // Check if an attachment with this URL already exists.
        $attachment_id = $wpdb->get_var($wpdb->prepare("
            SELECT ID FROM $wpdb->posts
            WHERE guid = %s
            AND post_type = 'attachment'
            LIMIT 1
        ", $image_url));

        // If the attachment exists, return its ID.
        if ($attachment_id) {
            return $attachment_id;
        }

        // Get the WordPress uploads directory information.
        $upload_dir = wp_upload_dir();
        // Extract the file name from the URL.
        $filename = basename($image_url);
        // Build the full file path where the image will be saved.
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Download the image with a 10-second timeout.
        $response = wp_remote_get($image_url, array(
            'timeout' => 10,
        ));
        if (is_wp_error($response)) {
            $import_messages[] = 'Failed to download image: ' . $image_url . ' - ' . $response->get_error_message();
            return null;
        }

        // Retrieve the body of the response which contains the image data.
        $image_data = wp_remote_retrieve_body($response);
        if (! $image_data) {
            $import_messages[] = 'Failed to retrieve image body: ' . $image_url;
            return null;
        }

        // Write the image data to the specified file path.
        $written = file_put_contents($file_path, $image_data);
        if ($written === false) {
            $import_messages[] = 'Failed to write image file: ' . $file_path;
            return null;
        }

        // Determine the MIME type of the file.
        $wp_filetype = wp_check_filetype($filename, null);
        // Prepare an array of attachment data.
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => $image_url, // Store the original image URL as the GUID.
        );

        // Insert the attachment into the database.
        $attach_id = wp_insert_attachment($attachment, $file_path, $product_id);
        if (! $attach_id) {
            $import_messages[] = 'Failed to insert attachment for image: ' . $image_url;
            return null;
        }

        // Include the image handling functions.
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        // Generate attachment metadata (such as image dimensions).
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        // Update the attachment metadata in the database.
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Return the attachment ID.
        return $attach_id;
    }
}
