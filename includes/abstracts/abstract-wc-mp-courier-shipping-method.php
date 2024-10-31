<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

if (class_exists('WC_Shipping_Method')) {

    /**
     * Class WC_MP_Courier_Shipping_Method
     */
    abstract class WC_MP_Courier_Shipping_Method extends WC_MP_Shipping_Method implements Wc_Mp_Shipping_Method_Interface
    {
        /**
         * WC_MP_Courier_Shipping_Method constructor.
         *
         * @param int $instance_id
         */
        public function __construct($instance_id = 0)
        {
            parent::__construct($instance_id, self::SUFFIX_COURIER);
        }

        /**
         * @return string
         */
        public function method_description()
        {
            return sprintf(__("%s courier will deliver the parcel right to the customer's hands.",
                'multiparcels-shipping-for-woocommerce'), $this->courier_settings['name']);
        }

        /**
         * @return string
         */
        public function default_title()
        {
            return sprintf("%s %s", $this->courier_settings['name'],
                __("Courier", 'multiparcels-shipping-for-woocommerce'));
        }
    }
}