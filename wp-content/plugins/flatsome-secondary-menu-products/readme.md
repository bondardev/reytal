# Flatsome Secondary Menu Products

**Contributors:** Oleksii Bondar  
**Requires at least:** 5.0  
**Tested up to:** 6.4  
**Requires PHP:** 7.4  
**Stable tag:** 1.2  
**License:** GPL-2.0+  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

This plugin enhances the secondary menu in the Flatsome theme by displaying product details for menu items that link to WooCommerce products.

## 📌 Features

- Automatically detects if a menu item links to a WooCommerce product.
- Adds the product image.
- Displays the product title.
- Shows the SKU (if available).
- Displays the regular price.
- Displays the sale price (if applicable).

## 📦 Installation

### 1. Install via WordPress

1. Go to **Plugins > Add New**.
2. Click **Upload Plugin** and select the plugin ZIP file.
3. Install and activate the plugin.

### 2. Manual Installation (FTP)

1. Unzip the plugin archive.
2. Upload the `flatsome-secondary-menu-products` folder to `/wp-content/plugins/`.
3. Activate the plugin in **Plugins > Installed Plugins**.

## 🔧 Usage

1. Ensure that your **Secondary Menu** in the Flatsome theme contains links to WooCommerce products.
2. When the plugin is active, menu items linking to WooCommerce products will automatically display:
   - The product image.
   - The product title.
   - SKU (if available).
   - Regular price.
   - Sale price (if different from the regular price).

## ❗ Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- Flatsome theme
- PHP 7.4+

## 🛠️ Development

If you want to modify or improve the plugin, you can edit the `flatsome-secondary-menu-products.php` file. The main functions include:
- `get_product_info( $product )` — generates the product info block.
- `filter_menu_item( $item_output, $item, $depth, $args )` — modifies the menu item output.

## 📜 License

This plugin is licensed under **GPL-2.0+**.
