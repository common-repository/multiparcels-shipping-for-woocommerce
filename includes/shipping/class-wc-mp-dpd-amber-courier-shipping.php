<?php

if ( ! defined('ABSPATH')) {

    exit;

}



if (class_exists('WC_MP_Courier_Shipping_Method')) {

    /**

     * Class WC_MP_Dpd_Amber_Courier_Shipping

     */

    class WC_MP_Dpd_Amber_Courier_Shipping extends WC_MP_Courier_Shipping_Method

    {

        public $carrier_code = 'dpd_amber';

    }

}

