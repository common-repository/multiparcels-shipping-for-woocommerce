<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

if (class_exists('WC_Shipping_Method')) {

    /**
     * Class WC_MP_Terminal_Shipping_Method
     */
    abstract class WC_MP_Terminal_Shipping_Method extends WC_MP_Shipping_Method implements Wc_Mp_Shipping_Method_Interface
    {
        const INPUT_ID = 'mp-wc-pickup-point-shipping-select';
        const INPUT_NAME = 'multiparcels_location_identifier';

        public $delivery_type = WC_MP_Shipping_Method::SUFFIX_TERMINAL;

        /**
         * WC_MP_Pickup_Point_Shipping_Method constructor.
         *
         * @param int $instance_id
         */
        public function __construct($instance_id = 0)
        {
            parent::__construct($instance_id, self::SUFFIX_TERMINAL);
        }

        /**
         * @return string
         */
        public function method_description()
        {
            return sprintf(__("%s courier will deliver the parcel to the selected self-service terminal for customer to pickup any time.",
                'multiparcels-shipping-for-woocommerce'), $this->courier_settings['name']);
            $text = '';

            if ($this->courier_settings['has_terminals']) {
                if ($this->courier_settings['cod_allowed']) {
                    $text = __(' This courier allows cash-on-delivery service when delivering to self-service parcel terminals.',
                        'multiparcels-shipping-for-woocommerce');
                }
            }

            return $text;
        }


        /**
         * @return string
         */
        function default_title()
        {
            $title = __('Terminals', 'multiparcels-shipping-for-woocommerce');

            return sprintf("%s %s", $this->courier_settings['name'], $title);
        }
    }
}
