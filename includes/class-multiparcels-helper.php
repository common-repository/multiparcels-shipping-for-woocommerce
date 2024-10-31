<?php

// If this file is called directly, abort.
if ( ! defined('ABSPATH')) {
    die;
}

/**
 * Class MultiParcels_Helper
 */
class MultiParcels_Helper
{
    /**
     * @param  string  $text
     *
     * @return string
     */
    public function latin_characters($text)
    {
        $table = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Ă' => 'A', 'Ā' => 'A', 'Ą' => 'A',
            'Æ' => 'A', 'Ǽ' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ă' => 'a', 'ā' => 'a', 'ą' => 'a',
            'æ' => 'a', 'ǽ' => 'a',

            'Þ' => 'B', 'þ' => 'b', 'ß' => 'Ss',

            'Ç' => 'C', 'Č' => 'C', 'Ć' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C',
            'ç' => 'c', 'č' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c',

            'Đ' => 'Dj', 'Ď' => 'D',
            'đ' => 'dj', 'ď' => 'd',

            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ĕ' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ė' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ĕ' => 'e', 'ē' => 'e', 'ę' => 'e', 'ė' => 'e',

            'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
            'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g',

            'Ĥ' => 'H', 'Ħ' => 'H',
            'ĥ' => 'h', 'ħ' => 'h',

            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'İ' => 'I', 'Ĩ' => 'I', 'Ī' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'į' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'ı' => 'i',

            'Ĵ' => 'J',
            'ĵ' => 'j',

            'Ķ' => 'K',
            'ķ' => 'k', 'ĸ' => 'k',

            'Ĺ' => 'L', 'Ļ' => 'L', 'Ľ' => 'L', 'Ŀ' => 'L', 'Ł' => 'L',
            'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l', 'ŀ' => 'l', 'ł' => 'l',

            'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N',
            'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŋ' => 'n', 'ŉ' => 'n',

            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ō' => 'O', 'Ŏ' => 'O', 'Ő' => 'O',
            'Œ' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o',
            'œ' => 'o', 'ð' => 'o',

            'Ŕ' => 'R', 'Ř' => 'R',
            'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r',

            'Š' => 'S', 'Ŝ' => 'S', 'Ś' => 'S', 'Ş' => 'S',
            'š' => 's', 'ŝ' => 's', 'ś' => 's', 'ş' => 's',

            'Ŧ' => 'T', 'Ţ' => 'T', 'Ť' => 'T',
            'ŧ' => 't', 'ţ' => 't', 'ť' => 't',

            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ũ' => 'U', 'Ū' => 'U', 'Ŭ' => 'U', 'Ů' => 'U', 'Ű' => 'U',
            'Ų' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u', 'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u',
            'ų' => 'u',

            'Ŵ' => 'W', 'Ẁ' => 'W', 'Ẃ' => 'W', 'Ẅ' => 'W',
            'ŵ' => 'w', 'ẁ' => 'w', 'ẃ' => 'w', 'ẅ' => 'w',

            'Ý' => 'Y', 'Ÿ' => 'Y', 'Ŷ' => 'Y',
            'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y',

            'Ž' => 'Z', 'Ź' => 'Z', 'Ż' => 'Z',
            'ž' => 'z', 'ź' => 'z', 'ż' => 'z',
        ];

        $text = strtr($text, $table);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return '';
        }

        return $text;
    }

    /**
     * @return bool
     */
    public function is_aerocheckout()
    {
        return in_array('woofunnels-aero-checkout/woofunnels-aero-checkout.php',
            apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * @return bool
     */
    public function has_omnisend()
    {
        return in_array('omnisend-connect/omnisend-woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * @return bool
     */
    public function has_paysera()
    {
        return in_array('woo-payment-gateway-paysera/paysera.php',
            apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * @return bool
     */
    public function has_ewout_fernhout_pdf_invoices_and_packing_slips()
    {
        return in_array('woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php',
            apply_filters('active_plugins', get_option('active_plugins')));
    }

    public function extract_delivery_from_shipping_method($method)
    {
        foreach (WC_MP_Shipping_Method::SUFFIXES as $suffix) {
            if (substr($method, strlen($suffix) * -1) == $suffix) {
                return $suffix;
            }
        }

        return '';
    }
}

return new MultiParcels_Helper();
