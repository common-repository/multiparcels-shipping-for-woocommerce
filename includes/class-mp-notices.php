<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

/**
 * Class MP_Notices
 */
class MP_Notices
{
    public function __construct()
    {
        add_action('admin_notices', [$this, 'api_key_errors']);

        if (MultiParcels()->permissions->isLimitedStrictly()) {
//            add_action('admin_notices', [$this, 'full_version_notice']);
        }

        if (MultiParcels()->permissions->isLimited()) {
            add_action('admin_notices', [$this, 'no_pickup_points_notice']);
        }

        if (MultiParcels()->version == '1.14') {
            add_action('admin_notices', [$this, 'release_notes_1_14']);
        }

    }

    function full_version_notice()
    {
        global $wpdb;

        if ( ! PAnD::is_admin_notice_active('multiparcels-full-version-notice-for-all-60')) {
            return;
        }

        if (strpos(get_current_screen()->id, 'multiparcels-shipping-for-woocommerce') !== false) {
            return;
        }

        ?>
        <div class="notice-info notice is-dismissible" data-dismissible="multiparcels-full-version-notice-for-all-60">
            <p>
                <?php
                echo __('Create shipping labels, print them, add Cash-On-Delivery services and automatically send tracking codes to your customer with just one click.',
                    'multiparcels-shipping-for-woocommerce');
                echo '<br/><br/>'; ?>
                <a class="button button-primary"
                   href="<?php echo esc_attr(MultiParcels()->settings_url(['tab' => 'full-version'])); ?>">
                    <?php _e('Start saving time with MultiParcels', 'multiparcels-shipping-for-woocommerce') ?>
                </a>
            </p>
        </div>
        <?php
    }

    function api_key_errors()
    {
        if (get_current_screen()->id != 'woocommerce_page_multiparcels-shipping-for-woocommerce' && get_current_screen()->id != 'shipments_page_multiparcels-shipping-for-woocommerce') {
            if (MultiParcels()->options->get('api_key') == null) {
                ?>
                <div class="error notice">
                    <p><?php _e('Configuration required for this plugin to function!',
                            'multiparcels-shipping-for-woocommerce'); ?></p>
                    <p>
                        <a href="<?php echo esc_attr(MultiParcels()->settings_url()); ?>">
                            <?php _e('Configure MultiParcels', 'multiparcels-shipping-for-woocommerce') ?>
                        </a>
                    </p>
                </div>
                <?php
            } elseif (MultiParcels()->permissions->get() == null) {
                ?>
                <div class="error notice">
                    <p><?php _e('Your provided API key is not working.',
                            'multiparcels-shipping-for-woocommerce'); ?></p>
                    <p>
                        <a href="<?php echo esc_attr(MultiParcels()->settings_url()); ?>">
                            <?php _e('Configure MultiParcels', 'multiparcels-shipping-for-woocommerce') ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }

    public function no_pickup_points_notice()
    {
        $pages = [
            'edit-shop_order',
        ];

        if ( ! in_array(get_current_screen()->id, $pages)) {
            return;
        }

        $show = false;

        $carriers = MultiParcels()->carriers->all();
        if (is_array($carriers)) {
            foreach ($carriers as $carrier => $settings) {
                if (MultiParcels()->options->getBool($carrier) && ($settings['has_terminals'] || $settings['has_pickup_points'])) {
                    if (count(MultiParcels()->locations->all($carrier)) == 0) {
                        $show = true;
                        break;
                    } else {
                        // Check only one
                        break;
                    }
                }
            }
        }

        if ( ! $show) {
            return;
        }
        ?>
        <div class="error notice" style="display: flex;">
            <div style="-webkit-box-flex: 1;-ms-flex: 1;flex: 1;-webkit-box-align: center;-ms-flex-align: center;align-items: center;display: -webkit-box;display: -ms-flexbox;display: flex;">
                <strong><?php _e('MultiParcels Shipping For WooCommerce', 'multiparcels-shipping-for-woocommerce'); ?>: </strong> <?php _e('No locations found. Please try to run manual update',
                    'multiparcels-shipping-for-woocommerce'); ?>
            </div>
            <p style="-webkit-box-flex: 0;-ms-flex: 0;flex: 0;">
                <a class="button button-primary"
                   href="<?php echo admin_url('admin-post.php?action=multiparcels_update_data'); ?>">
                    <?php _e('Manual update', 'multiparcels-shipping-for-woocommerce') ?>
                </a>
            </p>
        </div>
        <?php
    }

    public function release_notes_1_14()
    {
        global $wpdb;

        if ( ! PAnD::is_admin_notice_active('multiparcels-release_notes-four-365')) {
            return;
        }

        ?>
        <div class="notice-info notice is-dismissible" data-dismissible="multiparcels-release_notes-four-365">
            <p>
                <?php _e('Hello',
                    'multiparcels-shipping-for-woocommerce'); ?>! <?php echo sprintf(__('MultiParcels release notes for %s',
                    'multiparcels-shipping-for-woocommerce'), '2021-06-02'); ?>:
            </p>
            <ol>
                <li><strong><?php _e('New carrier added', 'multiparcels-shipping-for-woocommerce');?></strong>: FedEx<strong></strong></li>
                <li><strong><?php _e('New carrier added', 'multiparcels-shipping-for-woocommerce');?></strong>: Itella<strong></strong></li>
                <li><strong><?php _e('New carrier added', 'multiparcels-shipping-for-woocommerce');?></strong>: Deutsche Post International / DHL Global Mail<strong></strong></li>
                <li><strong><?php _e('New carrier added', 'multiparcels-shipping-for-woocommerce');?></strong>: GLS<strong></strong></li>
                <li><strong><?php _e('New carrier added', 'multiparcels-shipping-for-woocommerce');?></strong>: Hermes World<strong></strong></li>
                <li><strong><?php _e('New carrier added', 'multiparcels-shipping-for-woocommerce');?></strong>: Hermes (United Kingdom)<strong></strong></li>

                <li><?php _e('Ability to select shipping method(economy, express, express saver etc.) in shipping zones', 'multiparcels-shipping-for-woocommerce');?></li>
                <li><?php _e('Ability to edit products worth when dispatching orders(customs value)', 'multiparcels-shipping-for-woocommerce');?></li>
                <li><?php _e('Filter "multiparcels_checkout_carrier_icon_url" to change carrier logo', 'multiparcels-shipping-for-woocommerce');?></li>
                <li><?php _e('Ability to remove selected pickup point', 'multiparcels-shipping-for-woocommerce');?></li>
                <li><?php _e('Better support for default &lt;select&gt; and group by city', 'multiparcels-shipping-for-woocommerce');?></li>
            </ol>
        </div>
        <?php
    }
}

return new MP_Notices();
