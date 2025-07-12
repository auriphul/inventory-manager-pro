# Inventory Manager Pro

Inventory Manager Pro is an advanced inventory management system for WooCommerce with batch tracking features.

## `[inventory_batch_archive]` Shortcode

Use this shortcode to display information about the next available batch for a product while rendering a WooCommerce product archive (shop page, category list, etc.).
When used without parameters, it checks the current product from the WooCommerce loop and outputs the configured batch fields for the batch with the closest expiry date that still has stock. If no batch data exists, nothing is displayed.
You may also pass a `sku` or `product_id` attribute to display data for a specific product outside of the product loop.

### Prerequisites

- When no attributes are supplied it must run inside the WooCommerce product loop so that the `$product` global is available.

### Example Usage

Embed the shortcode in a template or theme file where a product object is available:

```php
<?php echo do_shortcode('[inventory_batch_archive]'); ?>
```

The shortcode can also be used outside the product loop by specifying a product:

```php
<?php echo do_shortcode('[inventory_batch_archive sku="ABC123"]'); ?>
```

For example, you can place it in a WooCommerce template such as `content-product.php` to show batch details under each product title.

## Checkout Stock Notices

When items are placed on backorder during checkout, the plugin now captures the
generated stock notices. These messages are still passed to WooCommerce using
`wc_add_notice()`, but you can also retrieve them for use in custom interfaces
(for example a modal) by calling:

```php
Inventory_Manager_WooCommerce::get_checkout_stock_messages();
```

Each call returns an array of notice strings that were generated during the
current request.

## Stock Deduction Logic

Inventory Manager Pro tracks stock movements for each order item. The workflow
differs slightly depending on where the order originated:

- **Frontend orders** – stock is deducted immediately when a customer checks out.
- **Backend orders** – created via the WooCommerce admin screen – stock is only
  deducted once the order status is changed to `Invoice`.
- When a backend order is changed to `Credit Note` the deducted quantities are
  restored.

Stock movements are logged against the order and item identifiers so the plugin
can safely ignore duplicate events and maintain consistent product inventory
levels.
