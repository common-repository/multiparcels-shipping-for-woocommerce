<?php
if ( ! defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_MP_Courier_Shipping_Method')) {
    /**
     * Class WC_MP_Venipak_Courier_Shipping
     */
    class WC_MP_Lp_Express_Pickup_Point_Shipping extends WC_MP_Pickup_Point_Shipping_Method
    {
        public $carrier_code = 'lp_express';
    }
}
