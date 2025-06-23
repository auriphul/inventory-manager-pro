# Inventory Manager Pro

Inventory Manager Pro is an advanced inventory management system for WooCommerce with batch tracking features.

## `[inventory_batch_archive]` Shortcode

Use this shortcode to display information about the next available batch for a product while rendering a WooCommerce product archive (shop page, category list, etc.). The shortcode checks the current product from the WooCommerce loop and outputs the configured batch fields for the batch with the closest expiry date that still has stock. If no batch data exists, nothing is displayed.

### Prerequisites

- Must run inside the WooCommerce product loop so that the `$product` global is available.
- Support for passing product attributes via shortcode parameters may be added in future versions.

### Example Usage

Embed the shortcode in a template or theme file where a product object is available:

```php
<?php echo do_shortcode('[inventory_batch_archive]'); ?>
```

For example, you can place it in a WooCommerce template such as `content-product.php` to show batch details under each product title.
