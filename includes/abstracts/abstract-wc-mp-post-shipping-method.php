<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

if (class_exists('WC_Shipping_Method')) {

    /**
     * Class WC_MP_Post_Shipping_Method
     */
    abstract class WC_MP_Post_Shipping_Method extends WC_MP_Shipping_Method implements Wc_Mp_Shipping_Method_Interface
    {
        public $delivery_type = WC_MP_Shipping_Method::SUFFIX_POST;

        /**
         * WC_MP_Post_Shipping_Method constructor.
         *
         * @param int $instance_id
         */
        public function __construct($instance_id = 0)
        {
            parent::__construct($instance_id, self::SUFFIX_POST);
        }

        /**
         * @return string
         */
        public function method_description()
        {
            return sprintf(__("%s will deliver the parcel to the closest post office.",
                    'multiparcels-shipping-for-woocommerce'),
                    $this->courier_name($this->courier_settings));
        }

        /**
         * @return string
         */
        public function default_title()
        {
            return sprintf("%s %s", $this->courier_name($this->courier_settings),
                __("Post office delivery", 'multiparcels-shipping-for-woocommerce'));
        }
    }
}
