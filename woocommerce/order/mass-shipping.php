<form action="" method="post">
    <?php
    if ($all_done) {
        echo sprintf("<h2 style='%s'>%s</h2>", 'color:green',
            __('All orders shipped', 'multiparcels-shipping-for-woocommerce'));

        submit_button(__('Download all labels', 'multiparcels-shipping-for-woocommerce'), 'submit', 'print');
    } else {
        submit_button(__('Dispatch orders', 'multiparcels-shipping-for-woocommerce'));
    }
    ?>

    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th>ID</th>
            <th><?php _e('Receiver', 'multiparcels-shipping-for-woocommerce') ?></th>
            <th><?php _e('Shipping method', 'multiparcels-shipping-for-woocommerce') ?></th>
            <th><?php _e('Confirmed', 'multiparcels-shipping-for-woocommerce') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order) { ?>
            <tr>
                <td>
                    <a href="<?php echo $order['link']; ?>" target="_blank">
                        <?php echo $order['id']; ?>
                    </a>
                </td>
                <td><?php echo $order['receiver']; ?></td>
                <td><?php echo $order['shipping_method']; ?></td>
                <td>
                    <span style="<?php echo $order['confirmed_style'] ?>">
                        <?php
                        echo $order['confirmed'];
                        ?>
                    </span>
                </td>
                <td>
                    <ul style="color: red;">
                        <?php
                        foreach ($order['errors'] as $error) {
                            echo sprintf("<li>%s</li>", $error);
                        }
                        ?>
                    </ul>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php
    if ( ! $all_done) {
        submit_button(__('Dispatch orders', 'multiparcels-shipping-for-woocommerce'));
    }
    ?>
</form>

