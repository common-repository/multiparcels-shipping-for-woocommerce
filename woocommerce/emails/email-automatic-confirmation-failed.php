<?php
defined('ABSPATH') || exit;

/** @var string $email_heading */
/** @var string $email */
/** @var array $orders */
do_action('woocommerce_email_header', $email_heading, $email);
?>
    <div style="margin-top: 40px;margin-bottom: 40px;">
        <table class="td" cellspacing="0" cellpadding="6"
               style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
            <thead>
            <tr>
                <th class="td" scope="col"><?php esc_html_e('Order ID',
                        'multiparcels-shipping-for-woocommerce'); ?></th>
                <th class="td" scope="col"><?php esc_html_e('Receiver',
                        'multiparcels-shipping-for-woocommerce'); ?></th>
                <th class="td" scope="col"><?php esc_html_e('Shipping',
                        'multiparcels-shipping-for-woocommerce'); ?></th>
                <th class="td" scope="col"></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($orders as $order) {
                ?>
                <tr>
                    <td class="td" scope="col"><?php echo $order['id']; ?></td>
                    <td class="td" scope="col"><?php echo $order['receiver']; ?></td>
                    <td class="td" scope="col"><?php echo $order['shipping']; ?></td>
                    <td class="td" scope="col"><a href="<?php echo $order['link']; ?>"
                                                  target="_blank"><?php esc_html_e('View order',
                                'multiparcels-shipping-for-woocommerce'); ?></a></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
<?php

do_action('woocommerce_email_footer', $email);
