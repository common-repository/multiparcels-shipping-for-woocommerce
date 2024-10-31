## multiparcels_order_shipping_packages

Update the number of packages in the order. Example: x items = x packages.

```php
add_filter('multiparcels_order_shipping_packages', function ($packages, $order, $items, $order_id) {
    $packages = 0;

    /** @var WC_Order_Item_Product[] $items */
    foreach ($items as $item) {
        $packages += $item->get_quantity();
    }

    if ($packages < 1) {
        $packages = 1;
    }


    return $packages;
}, 4, 10);
```