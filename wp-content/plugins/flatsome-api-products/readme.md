# Flatsome API Products

**Contributors:** Oleksii Bondar  
**Tags:** WooCommerce, API, Products, Flatsome, Menu  
**Requires at least:** 5.0  
**Tested up to:** 6.2  
**Stable tag:** 1.1  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

## Description

**Flatsome API Products** is a WordPress plugin that imports products from an external API.The plugin automatically adds product information—such as the product image, title, SKU, regular price, and sale price.

> **Note:** This plugin requires WooCommerce to be installed and active.

## Features

- **API Integration:** Connects to an external API using Basic Authentication.
- **Encryption:** Encrypts and decrypts API keys using `wp_salt()` for basic security.
- **Product Import:** Fetches product data by SKU and creates or updates WooCommerce products accordingly.
- **Admin Interface:** Provides a settings page where you can enter your API URL, API key, API secret, and SKU list.
- **User Notifications:** Displays admin notices for errors and successful operations.

## Installation

1. **Upload the Plugin:**
   - Download the plugin ZIP file.
   - In your WordPress admin panel, go to **Plugins > Add New** and click **Upload Plugin**.
   - Choose the ZIP file and click **Install Now**.

2. **Activate the Plugin:**
   - After installation, click **Activate** on the plugin page.
   - **Important:** Make sure WooCommerce is installed and activated before activating this plugin.

3. **Configure the Plugin:**
   - In the WordPress admin sidebar, go to **Flatsome API Products** (or **Get products**).
   - Enter your API URL, API key, API secret, and a comma-separated list of SKUs.
   - Click **Save Settings** to store your configuration.
   - To import products, click the **Import Products** button on the settings page.

## Usage

1. **Settings Page:**
   - Access the settings page from the admin menu.
   - Fill in all the required fields (API URL, SKU list, etc.).
   - Save the settings; if the API connection fails, you will see an error notice.

2. **Importing Products:**
   - Once your API settings are saved, click the **Import Products** button.
   - The plugin will fetch product data from the external API, create or update products in WooCommerce, and display a success or error notice.
   - Imported product details will then be appended to the Flatsome secondary menu items if they link to a product.

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

