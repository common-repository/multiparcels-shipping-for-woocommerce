<?php
if ( ! defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_MP_Courier_Shipping_Method')) {
    /**
     * Class WC_MP_Post_Lt_Post_Shipping
     */
    class WC_MP_Post_Lt_Post_Shipping extends WC_MP_Post_Shipping_Method
    {
        public $carrier_code = 'post_lt';
    }
}
