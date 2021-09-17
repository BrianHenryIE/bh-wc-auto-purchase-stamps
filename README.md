[![WordPress tested 5.8](https://img.shields.io/badge/WordPress-v5.8%20tested-0073aa.svg)](https://wordpress.org/plugins/bh-wp-plugins-page) [![PHPStan ](.github/phpstan.svg)](https://github.com/szepeviktor/phpstan-wordpress) [![PHPUnit ](.github/coverage.svg)](https://brianhenryie.github.io/bh-wp-plugins-page/) 

# Auto Purchase Stamps.com

An add-on for [WooCommerce Stamps.com API](https://woocommerce.com/products/woocommerce-shipping-stamps/) plugin to allow auto-purchasing and bulk-printing.

When an order is marked processing it uses the Stamps.com plugin to purchase the label as normal.

![Settings](./assets/screenshot-1.png "Enable auto-purchase, order status after auto-purchase, order status after printing, log level")

![Bulk print](./assets/screenshot-2.png "Bulk Print 4x6 Stamps.com labels PDF")

![plugins.php entry](./assets/screenshot-3.png "Settings, logs and Stamps.com links")

```php
/**
 * Disable for wholesale orders
 * 
 * @param bool $disable Should auto-printing be disabled for this order?
 * @param WC_Order $order The order object.
 * @return bool
 */
add_filter( 'bh_wc_auto_purchase_stamps_disable', function( bool $disable, WC_Order $order ): bool {
    return $disable || 'wholesale' === $order->get_meta( '_wwpp_order_type' );
},10,2);
````

## TODO

* Customs.
