<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

// If this file is called directly, abort.

if ( ! defined('ABSPATH')) {

    die;

}



/**

 * Class MP_Admin

 */

class MP_Admin

{

    const TAB_SETTINGS = 'settings';

    const TAB_SENDER_DETAILS = 'sender-details';

    const TAB_AUTO_COMPLETE = 'auto-complete';

    const TAB_FULL_VERSION = 'full-version';

    const TAB_CARRIER_LOGOS = 'carrier-logos';

    const TAB_CHECKOUT = 'checkout';

    const TAB_AUTOMATIC_CONFIRMATION = 'automatic_confirmation';



    const TABS = [

        self::TAB_SETTINGS,

        self::TAB_SENDER_DETAILS,

        self::TAB_AUTO_COMPLETE,

        self::TAB_FULL_VERSION,

        self::TAB_CARRIER_LOGOS,

        self::TAB_CHECKOUT,

        self::TAB_AUTOMATIC_CONFIRMATION,

    ];



    protected $tab = self::TAB_SETTINGS;



	/**

	 * @var int

	 */

	private $selected_sender_location = 0;



	/**

     * Admin constructor.

     */

    public function __construct()

    {

        add_action('admin_menu', [$this, 'add_admin_menu'], 99);

        add_action('admin_init', [$this, 'settings_init']);

    }


	function add_admin_menu() {

		if ( MultiParcels()->permissions->isFull() ) {

			add_menu_page(

				__('Shipments', 'multiparcels-shipping-for-woocommerce'),

				__('Shipments', 'multiparcels-shipping-for-woocommerce'),

				'manage_woocommerce',

				'multiparcels-shippings',

				[ 'MP_Amazing_shipping', 'shipments' ],

				'dashicons-admin-site',

				55

			);



			add_submenu_page(

				'multiparcels-shippings',

				__('Settings', 'multiparcels-shipping-for-woocommerce'),

				__('Settings', 'multiparcels-shipping-for-woocommerce'),

				'manage_options',

				'multiparcels-shipping-for-woocommerce',

				[ $this, 'options_page' ]

			);



			add_submenu_page(

				'woocommerce',

				'MultiParcels',

				'MultiParcels',

				'manage_options',

				'multiparcels-shipping-for-woocommerce-old',

				[ $this, 'old_menu_redirect' ],

                9999999

			); // old menu support

		} else {

			add_menu_page(

				__( 'Shipments', 'multiparcels-shipping-for-woocommerce' ),

				__( 'Shipments', 'multiparcels-shipping-for-woocommerce' ),

				'manage_woocommerce',

				'multiparcels-shippings',

				[ 'MP_Amazing_shipping', 'free_shipments' ],

				'dashicons-admin-site',

				55

			);



			add_submenu_page(

				'multiparcels-shippings',

				__( 'Settings', 'multiparcels-shipping-for-woocommerce' ),

				__( 'Settings', 'multiparcels-shipping-for-woocommerce' ),

				'manage_options',

				'multiparcels-shipping-for-woocommerce',

				[ $this, 'options_page' ]

			);



			add_submenu_page(

				'woocommerce',

				'MultiParcels',

				'MultiParcels',

				'manage_options',

				'multiparcels-shipping-for-woocommerce-old',

				[ $this, 'old_menu_redirect' ],

                9999999

			); // old menu support

		}

	}



    function old_menu_redirect() {

        wp_redirect(admin_url('admin.php?page=multiparcels-shipping-for-woocommerce'));

    }



    function validate($options)

    {
        if (MultiParcels()->permissions->isFull()) {
            $response = MultiParcels()->api_client->request('restricted_api/couriers', 'GET');

            if ($response->was_successful()) {

                $data     = $response->get_data();

                $carriers = $data['carriers'];

                MultiParcels()->options->set('carriers', $carriers, true);

            }
        }

        /**

         * Force permission update if the API key has changed

         */

        if ($options['api_key'] != MultiParcels()->options->get('api_key')) {

            MultiParcels()->permissions->set(null);

        }



        return $options;

    }



    function validate_sender_details($options)

    {

	    if ( ! $options ) {

		    return $options;

	    }

        $data = [

            'sender' => $options,

        ];



        $response = MultiParcels()->api_client->request('shipments?saving_sender_details=1', 'POST', $data);



        $final_errors = [];



        // There will always be errors

        $validation_errors = $response->get_validation_errors();

        foreach ($validation_errors as $key => $errors) {

            if (substr($key, 0, 7) == 'sender.') {

                $data_key = substr($key, 7);



                foreach ($errors as $error) {

                    $final_errors[$data_key][] = [

                        'rule' => $error['rule'],

                        'text' => $error['text'],

                    ];

                }

            }

        }



        if (count($final_errors)) {

            wp_redirect(MultiParcels()->settings_url([

                'tab'    => MP_Admin::TAB_SENDER_DETAILS,

                'data'   => $options,

                'errors' => $final_errors,

            ], false));

            exit;

        }



	    MultiParcels()->options->set_sender_location(

		    $_POST['multiparcels_sender_details']['code'],

		    $_POST['multiparcels_sender_details']

	    );



	    if ( MultiParcels()->options->get_default_sender_location() == null ) {

		    MultiParcels()->options->set_default_sender_location( $_POST['multiparcels_sender_details']['code'] );

	    }



	    wp_redirect(MultiParcels()->settings_url([

		    'tab'    => MP_Admin::TAB_SENDER_DETAILS,

	    ], false));

	    exit;

    }



    function settings_init()

    {
		
		//wp_nonce_field('_wpnonce2','_wpnonce2');
        if (array_key_exists('tab', $_GET)) {

            $this->tab = $_GET['tab'];

        }



        if (array_key_exists('multiparcels_tab', $_POST)) {

            $this->tab = $_POST['multiparcels_tab'];

        }



        if ( ! in_array($this->tab, self::TABS)) {

            $this->tab = self::TAB_SETTINGS;

        }



        add_settings_section(

            'multiparcels-shipping-for-woocommerce_section',

            null,

            [$this, 'multiparcels_settings_section_callback'],

            'multiparcels-shipping-for-woocommerce'

        );



        if ($this->tab == self::TAB_SETTINGS) {
			
		//	$set_url = '&_wpnonce2='.$set_nonce;

            register_setting('multiparcels-shipping-for-woocommerce', 'multiparcels_settings', [$this, 'validate']);



            add_settings_field(

                'multiparcels_api_key',

                __('API key', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'api_key_field_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_permissions',

                __('Permissions', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'permissions_field_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_google_maps',

                __('Google maps API key', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'google_maps_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_carriers',

                __('Carriers', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_field_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_show_all_cities',

                __('Show all cities', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'show_all_cities_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_pickup_location_place',

                __('Pickup location display place', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'pickup_location_place_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_selected_pickup_location_information',

                __('Display selected pickup location information in checkout', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'display_selected_pickup_location_information_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_display_pickup_location_title',

                __('Display pickup location title in checkout', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'display_pickup_location_title'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            if (MultiParcels()->permissions->isFull()) {
                add_settings_field(

                    'multiparcels_show_terminal_options',

                    __('Terminal options', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'terminal_options_render'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );

                add_settings_field(

                    'multiparcels_default_carrier',

                    __('Default carrier', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'default_carrier_field_render'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );

                add_settings_field(

                    'multiparcels_default_pickup_type',

                    __('Preferred pickup type', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'preferred_pickup_type_field_render'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_maximum_items_per_package',

                    __('Default maximum items per package', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'maximum_items_per_package_field_render'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_logger',

                    __('Enable log', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'enable_log'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_default_product_weight',

                    __('Default product weight', 'multiparcels-shipping-for-woocommerce') . '(' . get_option('woocommerce_weight_unit').')',

                    [$this, 'set_default_product_weight'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_split_skus',

                    __('Product code delimiter', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'split_skus_render'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_default_package_size',

                    __('Default package size', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'default_package_size_render'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_change_order_status_after_dispatch',

                    __('Change order status after dispatching', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'change_order_status_after_dispatch'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_change_order_status_after_dispatch_cod',

                    __('Change order status after dispatching with COD service', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'change_order_status_after_dispatch_cod'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_download_labels',

                    __('Download labels', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'download_labels'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );

			  add_settings_field(

                    'multiparcels_loactions_update',

                    __('Disable automatic locations update', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'enable_automatic_locations_update'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );

		

                if (MultiParcels()->helper->has_ewout_fernhout_pdf_invoices_and_packing_slips()) {

                    add_settings_field(

                        'multiparcels_ewout_fernhout_pdf_invoices_and_packing_slips',

                        __('Selected carrier and pickup location on invoice',

                            'multiparcels-shipping-for-woocommerce'),

                        [$this, 'ewout_fernhout_pdf_invoices_and_packing_slips'],

                        'multiparcels-shipping-for-woocommerce',

                        'multiparcels-shipping-for-woocommerce_section'

                    );

                }



                add_settings_field(

                    'multiparcels_skip_dispatching_for_specific_methods',

                    __('Skip dispatching for specific methods', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'skip_dispatching_for_specific_methods'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );

            }



            add_settings_field(

                'multiparcels_update',

                __('Data update', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'update_field_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_terminals',

                __('Pickup locations', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'terminals_list_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

        } elseif ($this->tab == self::TAB_SENDER_DETAILS) {

            register_setting('multiparcels-shipping-for-woocommerce', 'multiparcels_sender_details',

                [$this, 'validate_sender_details']);


				
			
			if ( array_key_exists( 'delete_sender_location', $_GET ) ) {
				if (current_user_can('administrator')){
					MultiParcels()->options->delete_sender_location($_GET['delete_sender_location']);



					if( $_GET['delete_sender_location'] == MultiParcels()->options->get_default_sender_location() ) {

						$sender_locations = MultiParcels()->options->get_sender_locations();



						if ( count( $sender_locations ) ) {

							$keys = array_keys( $sender_locations );



							MultiParcels()->options->set_default_sender_location( $keys[0] );

						} else {

							MultiParcels()->options->set_default_sender_location( null );

						}

					}
 
				}
			}




	        if ( $default_sender = MultiParcels()->options->get_default_sender_location() ) {

		        $this->selected_sender_location = $default_sender;

	        }



	        if ( array_key_exists( 'set_sender_location', $_GET ) ) {

		        MultiParcels()->options->set('default_sender_location', $_GET['set_sender_location']);

	        }



	        if ( array_key_exists( 'sender_location', $_GET ) ) {

		        $this->selected_sender_location = $_GET['sender_location'];

	        }			
					
					
					
			

	        



            add_settings_field(

                'multiparcels_sending_locations',

                __('Sending locations', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'sending_locations_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );
			
		



            add_settings_field(

                'multiparcels_sender_name',

                __('Name Surname/Company', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'sender_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_sender_street',

                __('Street', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'street_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_house_street',

                __('House', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'house_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_apartment_street',

                __('Apartment', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'apartment_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_city',

                __('City', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'city_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_postal_code',

                __('Postal code', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'postal_code_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_country_code',

                __('Country', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'country_code_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_phone_number',

                __('Phone number', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'phone_number_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

            add_settings_field(

                'multiparcels_email',

                __('E-mail', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'email_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

	        add_settings_field(

		        'multiparcels_location_code',

		        __('Location code', 'multiparcels-shipping-for-woocommerce'),

		        [$this, 'sender_location_code_render'],

		        'multiparcels-shipping-for-woocommerce',

		        'multiparcels-shipping-for-woocommerce_section'

	        );

        } elseif ($this->tab == self::TAB_FULL_VERSION) {

            add_settings_field(

                'multiparcels_full_version',

                __('Benefits', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'full_version_render'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

        } elseif ($this->tab == self::TAB_AUTO_COMPLETE) {

            register_setting('multiparcels-shipping-for-woocommerce', 'multiparcels_address_autocomplete');



            add_settings_field(

                'multiparcels_autocomplete_enable',

                __('Enabled', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'autocomplete_enable'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_autocomplete_display_notice',

                __('Display notice to customers', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'autocomplete_display_notice'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

        } elseif ($this->tab == self::TAB_CARRIER_LOGOS) {

            register_setting('multiparcels-shipping-for-woocommerce', 'multiparcels_carrier_logos');



            // Set the notice time

            $option_name        = 'multiparcels-carrier-logos';

            $dismissible_length = '360';



            $dismissible_length = (0 == absint($dismissible_length)) ? 1 : $dismissible_length;

            $transient          = absint($dismissible_length) * DAY_IN_SECONDS;

            $dismissible_length = strtotime(absint($dismissible_length) . ' days');



            set_site_transient($option_name, $dismissible_length, $transient);



            add_settings_field(

                'multiparcels_carrier_logos',

                __('Disabled', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_enable'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_icon_pasition',

                __('Icon position', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_icon_position'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_icon_visibility',

                __('Icon visibility', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_icon_visibility'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_icon_width_cart',

                __('Icon width (cart)', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_icon_width_cart'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_icon_width_checkout',

                __('Icon width (checkout)', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_icon_width_checkout'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_grid_display',

                __('Grid display', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_grid_display'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_grid_display_aligned',

                __('Grid display (aligned)', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'carrier_logos_grid_display_aligned'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

        } elseif ($this->tab == self::TAB_CHECKOUT) {

            register_setting('multiparcels-shipping-for-woocommerce', 'multiparcels_checkout');



            add_settings_field(

                'multiparcels_checkout_hide_terminal_fields',

                __('Hide not required fields when shipping to pickup locations', 'multiparcels-shipping-for-woocommerce'),

//                __('Can not working with WooPayments plugin', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'checkout_hide_terminal_fields'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_checkout_hide_local_pickup_fields',

                __('Hide not required fields for "local pickup"', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'checkout_hide_local_pickup_fields'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_checkout_show_address_2_field',

                __('Show address 2 field', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'checkout_show_address_2_field'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_checkout_force_required_shipping_number',

                __('Force phone number to be required', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'checkout_force_required_shipping_number'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_checkout_hide_delivery_phone_number',

                __('Hide delivery phone number', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'checkout_hide_delivery_phone_number'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_checkout_hide_state_field',

                __('Hide state field', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'checkout_hide_state_field'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );

        } elseif ($this->tab == self::TAB_AUTOMATIC_CONFIRMATION) {

            register_setting('multiparcels-shipping-for-woocommerce', 'multiparcels_automatic_confirmation');



            add_settings_field(

                'multiparcels_automatic_confirmation',

                __('Enable', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'automatic_confirmation_enable'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_automatic_confirmation_frequency',

                __('Frequency', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'automatic_confirmation_frequency'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_automatic_confirmation_days',

                __('Run days', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'automatic_confirmation_days'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            add_settings_field(

                'multiparcels_automatic_confirmation_statuses',

                __('Statuses that will be confirmed', 'multiparcels-shipping-for-woocommerce'),

                [$this, 'automatic_confirmation_statuses'],

                'multiparcels-shipping-for-woocommerce',

                'multiparcels-shipping-for-woocommerce_section'

            );



            if (!MultiParcels()->options->get_other_setting('automatic_confirmation', 'enabled')) {

                wp_unschedule_hook('multiparcels_automatic_confirmation_cron');

            } else {

                add_settings_field(

                    'multiparcels_automatic_confirmation_last_run',

                    __('Last run', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'automatic_confirmation_last_run'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                add_settings_field(

                    'multiparcels_automatic_confirmation_next_run',

                    __('Next run', 'multiparcels-shipping-for-woocommerce'),

                    [$this, 'automatic_confirmation_next_run'],

                    'multiparcels-shipping-for-woocommerce',

                    'multiparcels-shipping-for-woocommerce_section'

                );



                if (!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON == false) {

                    add_settings_field(

                        'multiparcels_automatic_confirmation_configuration_suggestion',

                        __('Notice', 'multiparcels-shipping-for-woocommerce'),

                        [$this, 'automatic_confirmation_configuration_suggestion'],

                        'multiparcels-shipping-for-woocommerce',

                        'multiparcels-shipping-for-woocommerce_section'

                    );

                }

            }

        }

    }



    public function checkout_hide_terminal_fields()

    {

        echo sprintf("<input type='hidden' name='multiparcels_tab' value='%s'/>", MP_Admin::TAB_CHECKOUT);

        $enabled = MultiParcels()->options->get_other_setting('checkout', 'enabled');



        ?>

        <select name='multiparcels_checkout[enabled]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>





        <div style="padding: 15px 0;">

            <?php _e("It will hide the address, city, postal code fields when delivery to pickup points is selected", 'multiparcels-shipping-for-woocommerce') ?>
            <?php _e("(can not working with WooPayments plugin)", 'multiparcels-shipping-for-woocommerce') ?>

        </div>

        <?php

    }



    public function checkout_hide_local_pickup_fields()

    {

        $enabled = MultiParcels()->options->get_other_setting('checkout', 'hide_for_local_pickup');



        ?>

        <select name='multiparcels_checkout[hide_for_local_pickup]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>





        <div style="padding: 15px 0;">

            <?php _e('It will hide the address, city, postal code fields when client selects "Local pickup"', 'multiparcels-shipping-for-woocommerce') ?>

        </div>

        <?php

    }



    public function checkout_show_address_2_field()

    {

        $enabled = MultiParcels()->options->get_other_setting('checkout', 'show_address_2_field');



        ?>

        <select name='multiparcels_checkout[show_address_2_field]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>

        <?php

    }



    public function checkout_force_required_shipping_number()

    {

        $enabled = MultiParcels()->options->get_other_setting('checkout', 'force_phone_number_required');



        ?>

        <select name='multiparcels_checkout[force_phone_number_required]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>

        <?php

    }



    public function checkout_hide_delivery_phone_number()

    {

        $enabled = MultiParcels()->options->get_other_setting('checkout', 'hide_delivery_phone_number');



        ?>

        <select name='multiparcels_checkout[hide_delivery_phone_number]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>





        <div style="padding: 15px 0;">

            <?php _e('WooCommerce does not provide a phone number field for delivery address when shipping to a different address', 'multiparcels-shipping-for-woocommerce') ?>

        </div>

        <?php

    }



    public function checkout_hide_state_field()

    {

        $value = MultiParcels()->options->get_other_setting('checkout', 'hide_state_field');



        ?>

        <select name='multiparcels_checkout[hide_state_field]'>

            <option value="yes"><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="no" <?php

            if ($value == 'no') {

                echo 'selected';

            }

            ?>><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>

        <?php

    }



    public function prepare_debug_info($for_mail_body = true, $full_version_question = false)

    {

        $fields = [

            [

                'title' => __('Plugin version', 'multiparcels-shipping-for-woocommerce'),

                'value' => MultiParcels()->version,

            ],

            [

                'title' => __('Plugin version (database)', 'multiparcels-shipping-for-woocommerce'),

                'value' => MultiParcels()->options->get('version', true),

            ],

            [

                'title' => 'URL',

                'value' => str_replace('www.', '', parse_url(get_bloginfo('wpurl'), PHP_URL_HOST)),

            ],

            [

                'title' => __('Settings', 'multiparcels-shipping-for-woocommerce'),

                'value' => MultiParcels()->options->all(),

            ],

            [

                'title' => __('Locations count', 'multiparcels-shipping-for-woocommerce'),

                'value' => count(MultiParcels()->locations->all()),

            ],

            [

                'title' => __('API key', 'multiparcels-shipping-for-woocommerce'),

                'value' => MultiParcels()->options->get('api_key'),

            ],

            [

                'title' => __('Google maps API key', 'multiparcels-shipping-for-woocommerce'),

                'value' => MultiParcels()->options->get('google_maps_api_key'),

            ],

            [

                'title' => __('Permissions', 'multiparcels-shipping-for-woocommerce'),

                'value' => MultiParcels()->permissions->get(),

            ],

        ];



        if ($full_version_question) {

            global $wpdb;



            $date_from_31  = date('Y-m-d H:i:s', strtotime('-31 days'));

            $date_from_365 = date('Y-m-d H:i:s', strtotime('-365 days'));

            $date_to       = date('Y-m-d H:i:s');



            $post_status = implode("','", ['wc-processing', 'wc-completed']);

            if (class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )) {
                if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
                    // HPOS usage is enabled.
                    $result_31  = $wpdb->get_results("SELECT * FROM $wpdb->wc_orders 

                    WHERE post_type = 'shop_order'

                    AND post_status IN ('{$post_status}')

                    AND post_date BETWEEN '{$date_from_31}'  AND '{$date_to}'

                ");

                    $result_365 = $wpdb->get_results("SELECT * FROM $wpdb->posts 

                    WHERE post_type = 'shop_order'

                    AND post_status IN ('{$post_status}')

                    AND post_date BETWEEN '{$date_from_365}'  AND '{$date_to}'

                ");
                }else {
                    // Traditional CPT-based orders are in use.
                    $result_31  = $wpdb->get_results("SELECT * FROM $wpdb->posts 

                    WHERE post_type = 'shop_order'

                    AND post_status IN ('{$post_status}')

                    AND post_date BETWEEN '{$date_from_31}'  AND '{$date_to}'

                    ");

                    $result_365 = $wpdb->get_results("SELECT * FROM $wpdb->posts 

                    WHERE post_type = 'shop_order'

                    AND post_status IN ('{$post_status}')

                    AND post_date BETWEEN '{$date_from_365}'  AND '{$date_to}'

                    ");
                }
            } else {
                // Traditional CPT-based orders are in use.
                $result_31  = $wpdb->get_results("SELECT * FROM $wpdb->posts 

                    WHERE post_type = 'shop_order'

                    AND post_status IN ('{$post_status}')

                    AND post_date BETWEEN '{$date_from_31}'  AND '{$date_to}'

                ");

                $result_365 = $wpdb->get_results("SELECT * FROM $wpdb->posts 

                    WHERE post_type = 'shop_order'

                    AND post_status IN ('{$post_status}')

                    AND post_date BETWEEN '{$date_from_365}'  AND '{$date_to}'

                ");
            }





            $totalOrders31Days  = count($result_31);

            $totalOrders365Days = count($result_365);



            $fields = [

                [

                    'title' => __('Last 31 days orders', 'multiparcels-shipping-for-woocommerce'),

                    'value' => $totalOrders31Days,

                ],

                [

                    'title' => __('Last 365 days orders', 'multiparcels-shipping-for-woocommerce'),

                    'value' => $totalOrders365Days,

                ],

            ];

        }



        $debug_text = '';



        foreach ($fields as $key => $field) {

            $value = $field['value'];



            if (is_array($value)) {

                $value = json_encode($value);

            }



            $debug_text .= sprintf("%s: %s", $field['title'], $value);



            if ($key != (count($fields) - 1)) {

                $debug_text .= ", ";

            }

            $debug_text .= "\n";

        }

        if ($for_mail_body) {

            $debug_text = "\n\n" . $debug_text;



            $debug_text = str_replace("\n", '%0A', $debug_text);

        }



        return $debug_text;

    }



	public function sending_locations_render() {

		echo sprintf( "<input type='hidden' name='multiparcels_tab' value='%s'/>", MP_Admin::TAB_SENDER_DETAILS );

		echo sprintf( "<input type='hidden' name='multiparcels_selected_sender_location' value='%s'/>", $this->selected_sender_location );



		echo "<select onchange='window.location = this.value'>";

		echo sprintf( "<option value='%s'>%s</option>",

			MultiParcels()->settings_url( [ 'tab' => self::TAB_SENDER_DETAILS, 'sender_location' => 'new' ] ),

			__( 'Create', 'multiparcels-shipping-for-woocommerce' ) );

		foreach ( MultiParcels()->options->get_sender_locations() as $id => $sender_location ) {

			$selected    = '';

			$is_default  = '';



			if ( $this->selected_sender_location === $id ) {

				$selected = 'selected';

			}



			if ( MultiParcels()->options->get( 'default_sender_location' ) === $id ) {

				$is_default = sprintf(' (%s) ', __('Default', 'multiparcels-shipping-for-woocommerce'));

			}



			echo sprintf(

				"<option value='%s' %s>%s</option>",

				MultiParcels()->settings_url( [ 'tab' => self::TAB_SENDER_DETAILS, 'sender_location' => $id ] ),

				$selected ? 'selected' : '',

				$is_default . $sender_location['name'] . ' - ' . $id . ''

			);

		}

		echo "</select>";

		echo str_repeat( "&nbsp;", 3 );



		if ( $this->selected_sender_location !== 0 ) {

			$set_url = MultiParcels()->settings_url(

				[

					'tab'                 => self::TAB_SENDER_DETAILS,

					'sender_location'     => $this->selected_sender_location,

					'set_sender_location' => $this->selected_sender_location,

				]

			);



			$delete_url = MultiParcels()->settings_url(

				[

					'tab'                    => self::TAB_SENDER_DETAILS,

					'delete_sender_location' => $this->selected_sender_location,

				]

			);

			
			$set_nonce= wp_create_nonce('_wpnonce2');


			$default_url = $set_url .'&_wpnonce2='.$set_nonce;

            $seturl2 = '&_wpnonce2='.$set_nonce;


			
			

			echo "<a href='" . $default_url. "' class='button'>" . __( 'Default', 'multiparcels-shipping-for-woocommerce' ) . "</a>";
	
//			$nonce_delete= wp_create_nonce('sender_locations');
			$delete_url = $delete_url.'&nonce_sender_locations='.$seturl2;
	
			echo " <a href='" . $delete_url  . "' class='button'>" . __( 'Delete', 'multiparcels-shipping-for-woocommerce' ) . "</a>";

		}



		echo "<br/>";

		echo "<br/>";

		echo "<hr/>";

	}



    public function sender_render()

    {

        $field = [

            'key'      => 'name',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function sender_location_code_render()

    {

	    $field = [

		    'key'           => 'code',

		    'type'          => 'text',

		    'required'      => true,

		    'default_value' => 'SL' . mt_rand( 100, 999 ),

	    ];



        $this->field($field);



        echo '<br/>';

        echo sprintf("<small>%s</small>", __('Used to identify the sender location by developers', 'multiparcels-shipping-for-woocommerce'));

    }



    public function street_render()

    {

        $field = [

            'key'      => 'street',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function house_render()

    {

        $field = [

            'key'      => 'house',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function apartment_render()

    {

        $field = [

            'key'      => 'apartment',

            'type'     => 'text',

            'required' => false,

        ];



        $this->field($field);

    }



    public function city_render()

    {

        $field = [

            'key'      => 'city',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function postal_code_render()

    {

        $field = [

            'key'      => 'postal_code',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function phone_number_render()

    {

        $field = [

            'key'      => 'phone_number',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function email_render()

    {

        $field = [

            'key'      => 'email',

            'type'     => 'text',

            'required' => true,

        ];



        $this->field($field);

    }



    public function full_version_render()

    {

        // Set the notice time

        $option_name        = 'multiparcels-full-version-notice-for-all';

        $dismissible_length = '60';



        $dismissible_length = (0 == absint($dismissible_length)) ? 1 : $dismissible_length;

        $transient          = absint($dismissible_length) * DAY_IN_SECONDS;

        $dismissible_length = strtotime(absint($dismissible_length) . ' days');



        set_site_transient($option_name, $dismissible_length, $transient);



        echo "<h2>";

        _e("Single order shipping details take about 5 minutes to enter in carrier's system, with our plugin only 1 minute for all orders.",

            'multiparcels-shipping-for-woocommerce');

        echo "</h2>";



        echo __("Let us save your precious time.", 'multiparcels-shipping-for-woocommerce');



        echo "<ol>";

        echo "<li>" . __("Order shipping details sent to carrier's system",

                'multiparcels-shipping-for-woocommerce') . "</li>";

        echo "<li>" . __('Easy label print from our plugin', 'multiparcels-shipping-for-woocommerce') . "</li>";

        echo "<li>" . __('Tracking code sent directly to customer', 'multiparcels-shipping-for-woocommerce') . "</li>";

        echo "<li>" . __('No hidden fees', 'multiparcels-shipping-for-woocommerce') . "</li>";

        echo "<li>" . __('Any many more features coming soon', 'multiparcels-shipping-for-woocommerce') . "</li>";

        echo "</ol>";



        $link = 'https://multiparcels.com/pricing/';



	    if ( get_locale() == 'lt_LT' || get_locale() == 'lt' ) {

		    $link = 'https://multisiuntos.lt/kaina/';

	    }



        echo sprintf("<a href='%s' class='button button-primary' target='_blank'>%s</a>", $link, __("Pricing", 'multiparcels-shipping-for-woocommerce'));



        echo "<br/>";

        echo "<br/>";



        ?>

        <strong>

            <?php _e('Yes, our plugin only takes 1 minute for any amount of orders',

                'multiparcels-shipping-for-woocommerce'); ?> :)

        </strong>

        <?php

    }



    public function country_code_render()

    {

        $field = [

            'key'      => 'country_code',

            'type'     => 'select',

            'required' => true,

            'values'   => WC()->countries->get_countries(),

        ];



        $this->field($field);

    }



    private function field($data)

    {

	    $data['value']    = '';

	    $data['errors']   = [];



        $sender_details = MultiParcels()->options->get_sender_location($this->selected_sender_location);



        if (array_key_exists($data['key'], $sender_details)) {

            $data['value'] = $sender_details[$data['key']];

        }



	    if ( ! $data['value'] && array_key_exists( 'default_value', $data ) ) {

		    $data['value'] = $data['default_value'];

	    }



        if (array_key_exists('data', $_GET) && array_key_exists($data['key'], $_GET['data'])) {

            $data['value'] = $_GET['data'][$data['key']];

        }



        if (array_key_exists('errors', $_GET) && array_key_exists($data['key'], $_GET['errors'])) {

            $data['errors'] = $_GET['errors'][$data['key']];

        }



        if ($data['type'] == 'text') {

            $required = '';



            if ($data['required']) {

                $required = 'required';

            }



            echo sprintf("<input type='text' name='multiparcels_sender_details[%s]' value='%s' %s/>", esc_attr($data['key']),

               esc_attr( $data['value']), $required);

        } elseif ($data['type'] == 'select') {

            echo sprintf("<select name='multiparcels_sender_details[%s]'>", esc_attr($data['key']));



            foreach ($data['values'] as $code => $name) {

                $selected = '';



                if ($data['value'] == $code) {

                    $selected = 'selected';

                }



                echo sprintf("<option value='%s' %s>%s</option>", esc_attr($code), $selected, esc_html($name));

            }

        }



        if (count($data['errors'])) {

            foreach ($data['errors'] as $error_data) {

                $error = $error_data['rule'];



                if ($error == 'VALID_POSTAL_CODE_RULE') {

                    $text = __('Not valid or not found',

                        'multiparcels-shipping-for-woocommerce');

                } elseif ($error == 'VALID_PHONE_NUMBER_RULE') {

                    $text = __('Not valid', 'multiparcels-shipping-for-woocommerce');

                } elseif ($error == 'REQUIRED' || $error == 'MAYBE_REQUIRED') {

                    $text = __('This field is required', 'multiparcels-shipping-for-woocommerce');

                } elseif ($error == 'EMAIL') {

                    $text = __('Not valid', 'multiparcels-shipping-for-woocommerce');

                } else {

                    $text = sprintf("%s:<br/> %s",

                        __('Unknown error occurred', 'multiparcels-shipping-for-woocommerce'),

                       esc_html( $error_data['text']));

                }



                if ($text) {

                    echo sprintf("<div style='color: red;'>%s</div>", $text);

                }

            }

        }

    }



    public function google_maps_render()

    {

        $key      = MultiParcels()->options->get('google_maps_api_key');



        echo sprintf("<input type='text' name='multiparcels_settings[google_maps_api_key]' value='%s'/><br/>", $key);



        if ( ! $key) {

            echo sprintf("<small>%s</small><br/>",

                __("Without the API key the map won't be displayed", 'multiparcels-shipping-for-woocommerce'));

            echo sprintf("<a href='%s' target='_blank'>%s</a><br/>",

                "https://developers.google.com/maps/documentation/javascript/get-api-key#standard-auth",

                __('Get the API key', 'multiparcels-shipping-for-woocommerce'));

        }

    }





    public function permissions_field_render()

    {

        $permissions      = MultiParcels()->permissions->get();

        $permission_title = sprintf("<strong>%s</strong>",

            __("None. Wrong API key", 'multiparcels-shipping-for-woocommerce'));



        if (MultiParcels()->permissions->isLimitedStrictly()) {

            $permission_title = __("Limited (terminals/pickup points only)", 'multiparcels-shipping-for-woocommerce');

        }



        if (MultiParcels()->permissions->isFull()) {

            $permission_title = __("Full", 'multiparcels-shipping-for-woocommerce');

        }



        echo $permission_title;



        if ($permissions == null || MultiParcels()->permissions->is_none()) {

            ?>

            <br>

            <br>

            <a href="#TB_inline?width=600&height=550&inlineId=my-content-id" class="thickbox button button-primary">

                <?php _e('Get a free API key', 'multiparcels-shipping-for-woocommerce'); ?>

            </a>

            <?php

        }

    }



    public

    function terminals_list_render()

    {

        $locations = MultiParcels()->locations->all();



        if (isset($_REQUEST['show_locations_list']) && $_REQUEST['show_locations_list'] == 1) {

            ?>

            <a href="?page=multiparcels-shipping-for-woocommerce" class="button">

                <?php _e('Hide', 'multiparcels-shipping-for-woocommerce') ?>

            </a>



            <div class="locations-list">

                <div class="locations-list-item header">

                    <div><?php _e('Carrier', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('Identification', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('Type', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('Name', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('Address', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('Postal code', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('City', 'multiparcels-shipping-for-woocommerce') ?></div>

                    <div><?php _e('Country', 'multiparcels-shipping-for-woocommerce') ?></div>

                </div>



                <?php

                foreach ($locations as $location) {

                    ?>

                    <div class="locations-list-item">

                        <div><?php echo MultiParcels()->carriers->name($location['carrier_code']); ?></div>

                        <div><?php echo $location['identifier']; ?></div>

                        <div><?php echo MultiParcels()->locations->type_name($location['type']); ?></div>

                        <div><?php echo $location['name']; ?></div>

                        <div><?php echo $location['address']; ?></div>

                        <div><?php echo $location['postal_code']; ?></div>

                        <div><?php echo $location['city']; ?></div>

                        <div><?php echo $location['country_code']; ?></div>

                    </div>

                <?php } ?>

            </div>



            <style>

                .locations-list {

                    display: flex;

                    width: 100%;

                    flex-direction: column;

                }



                .locations-list > div {

                    flex: 1;

                }



                .locations-list-item {

                    width: 100%;

                    display: flex;

                }



                .locations-list-item > div {

                    flex: 1;

                    padding: 3px;

                    border: 1px solid #ccc;

                }



                .locations-list-item.header {

                    font-weight: bold;

                }

            </style>

            <?php

        } else {

            ?>

            <a href="?page=multiparcels-shipping-for-woocommerce&amp;show_locations_list=1" class="button">

                <?php _e('Show', 'multiparcels-shipping-for-woocommerce') ?>

            </a>

            <?php

        }

    }



    public function automatic_confirmation_enable()

    {

        echo sprintf("<input type='hidden' name='multiparcels_tab' value='%s'/>", MP_Admin::TAB_AUTOMATIC_CONFIRMATION);

        $value = MultiParcels()->options->get_other_setting('automatic_confirmation', 'enabled');



        ?>

        <select name='multiparcels_automatic_confirmation[enabled]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value === "1" || $value === 1) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>

        <p>

            <?php _e('Paid or Cash-On-Delivery orders that have correct statuses will be automatically confirmed', 'multiparcels-shipping-for-woocommerce') ?>

        </p>

        <?php

    }



    public function automatic_confirmation_frequency()

    {

        $values = [

          '10 min' => __('10 min', 'multiparcels-shipping-for-woocommerce'),

          '30 min' => __('30 min', 'multiparcels-shipping-for-woocommerce'),

          '60 min' => __('60 min', 'multiparcels-shipping-for-woocommerce'),

          '24 hour' => __('24 hour', 'multiparcels-shipping-for-woocommerce'),

        ];



        $current_value = MultiParcels()->options->get_other_setting('automatic_confirmation', 'frequency');



        ?>

        <select name='multiparcels_automatic_confirmation[frequency]'>

            <?php foreach ($values as $value => $text) {

                $selected = '';

                if ($value == $current_value) {

                    $selected = 'selected';

                }

                echo sprintf("<option value='%s' %s>%s</option>", $value, $selected, $text);

            } ?>

        </select>

        <?php

    }



    public function automatic_confirmation_days()

    {

        $values = MultiParcels()->options->get_other_setting('automatic_confirmation', 'run_days');



        if (!$values) {

            $values = [

                1,

                2,

                3,

                4,

                5

            ];

        }



        $days = [

            1 => __( 'Monday', 'multiparcels-shipping-for-woocommerce' ),

            2 => __( 'Tuesday', 'multiparcels-shipping-for-woocommerce' ),

            3 => __( 'Wednesday', 'multiparcels-shipping-for-woocommerce' ),

            4 => __( 'Thursday', 'multiparcels-shipping-for-woocommerce' ),

            5 => __( 'Friday', 'multiparcels-shipping-for-woocommerce' ),

            6 => __( 'Saturday', 'multiparcels-shipping-for-woocommerce' ),

            7 => __( 'Sunday', 'multiparcels-shipping-for-woocommerce' ),

        ];



        foreach ($days as $day => $title) {

            $checked = '';



            if (is_array($values) && in_array($day, $values)) {

                $checked = 'checked';

            }



            echo sprintf("<input type='checkbox' name='multiparcels_automatic_confirmation[run_days][%s]' value='%s' %s/> %s<br/>",

                $day, $day, $checked, $title);

        }

    }



    public function automatic_confirmation_statuses()

    {

        $values = MultiParcels()->options->get_other_setting('automatic_confirmation', 'statuses');



        if (!$values) {

            $values = [

                'wc-processing'

            ];

        }



        $statuses = wc_get_order_statuses();



        foreach ($statuses as $code => $title) {

            $checked = '';



            if (is_array($values) && in_array($code, $values)) {

                $checked = 'checked';

            }



            echo sprintf("<input type='checkbox' name='multiparcels_automatic_confirmation[statuses][%s]' value='%s' %s/> %s<br/>",

                $code, $code, $checked, $title);

        }

    }



    public function automatic_confirmation_last_run()

    {

        $last_update = MultiParcels()->options->get('automatic_confirmation_last_update', true);



        if ($last_update === null || (is_array($last_update) && count($last_update) === 0)) {

            $last_update = __('Never', 'multiparcels-shipping-for-woocommerce');

        }



        echo $last_update;



        ?>

        <br>
		
		<?php
							
		$set_nonce= wp_create_nonce('_wpnonce2');
		$set_url =admin_url('admin-post.php?action=multiparcels_run_automatic_confirmation').'&_wpnonce2='.$set_nonce;
							
		?>

        <a href="<?php echo $set_url; ?>" class="button button-primary">

            <?php _e('Run now', 'multiparcels-shipping-for-woocommerce') ?>

        </a>

        <?php

    }





    public function automatic_confirmation_next_run()

    {

        $current_value = MultiParcels()->options->get_other_setting('automatic_confirmation', 'frequency');

        $current_cron_frequency = wp_get_schedule('multiparcels_automatic_confirmation_cron');

        $recurrence = 'multiparcels_every_10min';

        $seconds = 10 * 60;



        if ($current_value == '30 min') {

            $recurrence = 'multiparcels_every_30min';

            $seconds = 30 * 60;

        }



        if ($current_value == '60 min') {

            $recurrence = 'multiparcels_every_60min';

            $seconds = 60 * 60;

        }



        if ($current_value == '24 hour') {

            $recurrence = 'multiparcels_every_24h';

            $seconds = 60 * 60 * 24;

        }



        $next_cron_time = wp_next_scheduled('multiparcels_automatic_confirmation_cron');



        if ($current_cron_frequency != $recurrence) {

            $next_cron_time = null; // force re-add

        }



        if ($next_cron_time) {

            echo get_date_from_gmt( date('Y-m-d H:i:s', $next_cron_time) );



            return;

        }



        wp_unschedule_hook('multiparcels_automatic_confirmation_cron');



        // find next even time. if starting at 10:31 and looking for 10min run, next time will be 10:40

        $current_time = time();



        $frac = $seconds;

        $r = $current_time % $frac;



        $new_even_time = $current_time + ($frac-$r);

        // end find next even time



        if ($current_value == '24 hour') {

            // start in 24 hours

            $new_even_time = $current_time + ($seconds);

        }



        wp_schedule_event($new_even_time, $recurrence, 'multiparcels_automatic_confirmation_cron');



        $next_cron_time = wp_next_scheduled('multiparcels_automatic_confirmation_cron');



        if ($next_cron_time) {

            echo get_date_from_gmt( date('Y-m-d H:i:s', $next_cron_time) );

        }

    }



    public function automatic_confirmation_configuration_suggestion()

    {

            ?>

            <p>

                <?php _e('We HIGHLY ecommend to disable the default WordPress cron job schedules and setup a unix cronjob', 'multiparcels-shipping-for-woocommerce') ?>.

                <?php _e('Please add the following to the end of your wp-config.php configuration file', 'multiparcels-shipping-for-woocommerce') ?>:</p>

            <blockquote><pre>define(&#39;DISABLE_WP_CRON&#39;, true);</pre></blockquote>

            <p>

                <?php _e('Cronjob command', 'multiparcels-shipping-for-woocommerce') ?>:

            </p>

            <blockquote><pre style="word-wrap: break-word"><?php echo $this->cronjob_commad() ?></pre></blockquote>

            <?php

    }



    public function cronjob_commad()

    {

        return '* * * * * wget -qO- &quot;' . esc_attr(get_bloginfo('wpurl')) .'/wp-cron.php?doing_wp_cron&quot; &>/dev/null';

    }

    public function terminal_options_render()
    {
        $value = MultiParcels()->options->get('terminal_options');

        ?>

        <select name='multiparcels_settings[terminal_options]'>

            <option value="0"><?php _e('All', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value === "1" || $value === 1) {

                echo 'selected';

            }

            ?>><?php _e('Only terminals', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>



        <small>

            <?php _e('All', 'multiparcels-shipping-for-woocommerce') ?> -

            <?php _e('shows all terminals and pickup points',

                'multiparcels-shipping-for-woocommerce') ?>

            <br>

            <?php _e('Only terminals', 'multiparcels-shipping-for-woocommerce') ?> -

            <?php _e('will show all terminals', 'multiparcels-shipping-for-woocommerce') ?>

        </small>

        <?php
    }

    public function show_all_cities_render()

    {

        $value = MultiParcels()->options->get('show_all_cities');



        ?>

        <select name='multiparcels_settings[show_all_cities]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value === "1" || $value === 1) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="2" <?php

            if ($value === "2" || $value === 2) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?> (<?php _e('group by city', 'multiparcels-shipping-for-woocommerce') ?>)</option>

        </select><br>



        <small>

            <?php _e('No', 'multiparcels-shipping-for-woocommerce') ?> -

            <?php _e('shows only pickup points in customer\'s delivery city',

                'multiparcels-shipping-for-woocommerce') ?>

            <br>

            <?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?> -

            <?php _e('will show all pickup points', 'multiparcels-shipping-for-woocommerce') ?>

            <br>

            <span style="background: #d4edda;padding: 4px 7px;color: black;display: inline-block;font-weight: bold;"><?php _e('NEW', 'multiparcels-shipping-for-woocommerce') ?></span> <?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?> (<?php _e('group by city', 'multiparcels-shipping-for-woocommerce') ?>) -

            <?php _e('will show all pickup points', 'multiparcels-shipping-for-woocommerce') ?> <?php _e('and group them by city', 'multiparcels-shipping-for-woocommerce') ?>

        </small>

        <?php

    }



    public function pickup_location_place_render()

    {

	    $possible_places = [

		    'woocommerce_review_order_before_payment'   => __( 'Before payment', 'multiparcels-shipping-for-woocommerce' ),

		    'woocommerce_after_shipping_rate'           => __( 'After shipping rate', 'multiparcels-shipping-for-woocommerce' ),

		    'woocommerce_after_order_notes'             => __( 'After order notes', 'multiparcels-shipping-for-woocommerce' ),

		    'woocommerce_before_checkout_billing_form'  => __( 'Before billing form', 'multiparcels-shipping-for-woocommerce' ),

		    'woocommerce_after_checkout_billing_form'   => __( 'After billing form', 'multiparcels-shipping-for-woocommerce' ),

		    'woocommerce_before_checkout_shipping_form' => __( 'Before shipping form', 'multiparcels-shipping-for-woocommerce' ),

		    'woocommerce_after_checkout_shipping_form'  => __( 'After shipping form', 'multiparcels-shipping-for-woocommerce' ),

	    ];



	    $using_filter = has_filter( 'multiparcels_location_selector_hook' );



	    if ( $using_filter ) {

		    $filter_hook = apply_filters( 'multiparcels_location_selector_hook', '' );



		    echo sprintf( "%s: <strong>%s</strong>",

			    __( 'You are using a custom location', 'multiparcels-shipping-for-woocommerce' ), $filter_hook );

        } elseif (MultiParcels()->helper->is_aerocheckout()) {

            echo sprintf( "%s: <strong>%s</strong>",

                __( 'You are using a custom location', 'multiparcels-shipping-for-woocommerce' ), 'AeroCheckout' );

        } else {

		    $value = MultiParcels()->options->get('pickup_location_display_hook');



		    ?>



            <select name='multiparcels_settings[pickup_location_display_hook]'>

			    <?php foreach ( $possible_places as $hook => $title ) {

				    echo sprintf( "<option value='%s'%s>%s (%s)</option>",

					    $hook,

					    $hook == $value ? 'selected' : '',

					    $title,

					    $hook

				    );

			    } ?>

            </select>

		    <?php

	    }

    }



	public function display_selected_pickup_location_information_render() {

		$value = MultiParcels()->options->get( 'display_selected_pickup_location_information', false, 'yes');



		?>

        <select name='multiparcels_settings[display_selected_pickup_location_information]'>

            <option value="yes"><?php _e( 'Yes', 'multiparcels-shipping-for-woocommerce' ) ?></option>

            <option value="no" <?php

			if ( $value == 'no') {

				echo 'selected';

			}

			?>><?php _e( 'No', 'multiparcels-shipping-for-woocommerce' ) ?></option>

        </select>

		<?php

	}



	public function display_pickup_location_title() {

		$value = MultiParcels()->options->get( 'display_pickup_location_title', false, 'yes');



		?>

        <select name='multiparcels_settings[display_pickup_location_title]'>

            <option value="yes"><?php _e( 'Yes', 'multiparcels-shipping-for-woocommerce' ) ?></option>

            <option value="no" <?php

			if ( $value == 'no') {

				echo 'selected';

			}

			?>><?php _e( 'No', 'multiparcels-shipping-for-woocommerce' ) ?></option>

        </select>

		<?php

	}



    public function autocomplete_enable()

    {

        echo sprintf("<input type='hidden' name='multiparcels_tab' value='%s'/>", MP_Admin::TAB_AUTO_COMPLETE);

        $enabled  = false;

        $settings = MultiParcels()->options->get('address_autocomplete', true);



        if (is_array($settings) && array_key_exists('enabled', $settings) && $settings['enabled'] == 1) {

            $enabled = true;

        }



        ?>

        <select name='multiparcels_address_autocomplete[enabled]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>

        <?php

    }



    public function carrier_logos_enable()

    {

        echo sprintf("<input type='hidden' name='multiparcels_tab' value='%s'/>", MP_Admin::TAB_CARRIER_LOGOS);

        $disabled = MultiParcels()->options->get_other_setting('carrier_logos','disabled');



        ?>

        <select name='multiparcels_carrier_logos[disabled]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($disabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>

        <?php

    }



    public function carrier_logos_icon_position()

    {

        $value = MultiParcels()->options->get_other_setting('carrier_logos','icon_position');



        ?>

        <select name='multiparcels_carrier_logos[icon_position]'>

            <option value="after_label"><?php _e('After label', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="before_label" <?php

            if ($value == 'before_label') {

                echo 'selected';

            }

            ?>><?php _e('Before Label', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>

        <?php

    }



    public function carrier_logos_icon_visibility()

    {

        $value = MultiParcels()->options->get_other_setting('carrier_logos','icon_visibility');



        ?>

        <select name='multiparcels_carrier_logos[icon_visibility]'>

            <option value="0"><?php _e('Cart and checkout', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="only_cart" <?php

            if ($value == 'only_cart') {

                echo 'selected';

            }

            ?>><?php _e('Only cart', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="only_checkout" <?php

            if ($value == 'only_checkout') {

                echo 'selected';

            }

            ?>><?php _e('Only checkout', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>

        <?php

    }



    public function carrier_logos_icon_width_cart()

    {

        $this->carrier_logos_icon_width('cart');



    }



    public function carrier_logos_icon_width_checkout()

    {

        $this->carrier_logos_icon_width('checkout');

    }



    public function carrier_logos_icon_width($where)

    {

        $value = MultiParcels()->options->get_other_setting('carrier_logos','icon_width_'.$where, '100px');



        ?>

        <input type="text" name='multiparcels_carrier_logos[icon_width_<?php echo $where; ?>]' value="<?php echo $value; ?>"> <br>

        <?php

    }



    public function carrier_logos_grid_display()

    {

        $value = MultiParcels()->options->get_other_setting('carrier_logos','grid_display', '1');



        ?>

        <select name='multiparcels_carrier_logos[grid_display]'>

            <option value="0"><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

                echo 'selected';

            }

            ?>><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>

        <?php

    }



    public function carrier_logos_grid_display_aligned()

    {

        $value = MultiParcels()->options->get_other_setting('carrier_logos','grid_display_aligned');



        ?>

        <select name='multiparcels_carrier_logos[grid_display_aligned]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>

        <?php



        ?>



    <div style="padding: 15px 0;">

        <?php _e("Grid display setting doesn't work on all themes because of different alignment techniques. Play with the settings to find the best version. Or write us and maybe we can help ;)", 'multiparcels-shipping-for-woocommerce') ?>

    </div>



        <div style="">

            <p style="margin-bottom: 5px;"><?php _e('Grid display on + grid display aligned', 'multiparcels-shipping-for-woocommerce') ?></p>

            <?php echo sprintf("<img src='%s'/>", MultiParcels()->public_plugin_url('images/demos/carrier_logos_grid_display_aligned.png')); ?>



            <p style="margin-bottom: 5px;"><?php _e('Grid display on', 'multiparcels-shipping-for-woocommerce') ?></p>

            <?php echo sprintf("<img src='%s'/>", MultiParcels()->public_plugin_url('images/demos/carrier_logos_grid_display.png')); ?>

            <br><br>



            <p style="margin-bottom: 5px;"><?php _e('Grid display off', 'multiparcels-shipping-for-woocommerce') ?></p>

            <?php echo sprintf("<img src='%s'/>", MultiParcels()->public_plugin_url('images/demos/carrier_logos_all_disabled.png')); ?>

            <br><br>

        </div>

        <?php

    }



    public function autocomplete_display_notice()

    {

        echo sprintf("<input type='hidden' name='multiparcels_tab' value='%s'/>", MP_Admin::TAB_AUTO_COMPLETE);

        $enabled  = false;

        $settings = MultiParcels()->options->get('address_autocomplete', true);



        if (is_array($settings) && array_key_exists('display_notice', $settings) && $settings['display_notice'] == 1) {

            $enabled = true;

        }



        ?>

        <select name='multiparcels_address_autocomplete[display_notice]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($enabled) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select>



        <div>

            <?php _e('Notice:', 'multiparcels-shipping-for-woocommerce'); ?>

            <strong> <?php _e('Address suggestions are enabled. Start typing the street name, city or postal code.',

                    'multiparcels-shipping-for-woocommerce'); ?></strong>

        </div>

        <?php

    }



    public function enable_log()

    {

        $value = MultiParcels()->options->getBool('logger_enabled');



        if ( ! $value) {

            MultiParcels()->logger->clear();

        }

        ?>

        <select name='multiparcels_settings[logger_enabled]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

                echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>



        <div>

		    <?php _e( 'This can help find problems faster. Enable and contact support',

			    'multiparcels-shipping-for-woocommerce' ); ?>

        </div>



        <?php

    }



    public function set_default_product_weight() {

	    $value = MultiParcels()->options->get( 'default_product_weight' );



        if ($value <= 0) {

            $value = 0.1; // default dummy weight

        }



	    ?>

        <input type="number" min="0.001" step="0.001" name='multiparcels_settings[default_product_weight]' value="<?php echo $value; ?>"> <br>



        <small>

		    <?php _e('Default weight for products with no weight', 'multiparcels-shipping-for-woocommerce') ?>

        </small>

	    <?php

    }



    public function split_skus_render() {

	    $value = MultiParcels()->options->get( 'split_sku_delimiter' );



	    ?>

        <input type="text" name='multiparcels_settings[split_sku_delimiter]' value="<?php echo $value; ?>"> <br>



        <small>

		    <?php _e('You can use this feature to split product codes in to different products when dispatching', 'multiparcels-shipping-for-woocommerce'); ?>

            <br> <br>

		    <?php _e('Example product code: good-product1,better-product2,the-best-product3', 'multiparcels-shipping-for-woocommerce'); ?>

            <br>

		    <?php _e('If the delimiter is a comma(,) - there would be three products when dispatching an order and not one', 'multiparcels-shipping-for-woocommerce'); ?>

            <br>



        </small>

	    <?php

    }



	public function default_package_size_render() {

		$value = MultiParcels()->options->get( 'default_package_size' );



		?>

        <select name='multiparcels_settings[default_package_size]'>

	        <?php foreach ( MP_Woocommerce_Order_Shipping::PACKAGE_SIZES as $package_size ) {

		        echo sprintf( "<option value='%s'%s>%s</option>",

			        $package_size,

			        $value == $package_size ? 'selected' : '',

			        MP_Woocommerce_Order_Shipping::package_name( $package_size )

		        );

	        } ?>

        </select><br>

		<?php

	}



	public function change_order_status_after_dispatch() {

		$value = MultiParcels()->options->getBool( 'not_change_order_status_after_dispatch' );



		?>

        <select name='multiparcels_settings[not_change_order_status_after_dispatch]'>

            <option value="0"><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

	            echo 'selected';

            }

            ?>><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>



        <small>

			<?php _e('Change order status to "Completed" after dispatching the order', 'multiparcels-shipping-for-woocommerce') ?>

        </small>

		<?php

	}



	public function change_order_status_after_dispatch_cod() {

		$value = MultiParcels()->options->getBool( 'not_change_order_status_after_dispatch_cod' );



		?>

        <select name='multiparcels_settings[not_change_order_status_after_dispatch_cod]'>

            <option value="0"><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

	            echo 'selected';

            }

            ?>><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>



        <small>

			<?php _e('Change order status to "Completed" after dispatching the order with COD service', 'multiparcels-shipping-for-woocommerce') ?>

        </small>

		<?php

	}



    public function download_labels()

    {

        $value = MultiParcels()->options->getBool('disable_label_downloading');



        ?>

        <select name='multiparcels_settings[disable_label_downloading]'>

            <option value="0"><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

	            echo 'selected';

            }

            ?>><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>

        <?php

    }

		 public function enable_automatic_locations_update()

    {

        $value = MultiParcels()->options->getBool('multiparcels_loactions_update');



        ?>

        <select name='multiparcels_settings[multiparcels_loactions_update]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

	            echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>

        <?php

    }



    public function ewout_fernhout_pdf_invoices_and_packing_slips()

    {

        $value = MultiParcels()->options->getBool('ewout_fernhout_pdf_invoices_and_packing_slips_enabled');



        ?>

        <select name='multiparcels_settings[ewout_fernhout_pdf_invoices_and_packing_slips_enabled]'>

            <option value="0"><?php _e('No', 'multiparcels-shipping-for-woocommerce') ?></option>

            <option value="1" <?php

            if ($value) {

	            echo 'selected';

            }

            ?>><?php _e('Yes', 'multiparcels-shipping-for-woocommerce') ?></option>

        </select><br>



        <p>

            <?php _e('Add carrier and selected pickup location information to "WooCommerce PDF Invoices & Packing Slips" invoice.', 'multiparcels-shipping-for-woocommerce'); ?>

        </p>

        <?php

    }



    public function skip_dispatching_for_specific_methods()

    {

        $selected = MultiParcels()->options->get_array('skip_methods_for_dispatching');



        $shipping_methods = WC()->shipping()->get_shipping_methods();

        echo "<div id='skip_dispatching_for_specific_methods'>";

        foreach ($shipping_methods as $method) {

            $checked = '';



            if (in_array($method->id, $selected)) {

                $checked = ' checked';

            }



            echo sprintf(

                "<input type='checkbox' name='multiparcels_settings[skip_methods_for_dispatching][]' value='%s'%s/> %s<br/>",

                $method->id,

                $checked,

                $method->method_title

            );

        }

        echo "</div>";

    }



    public function carrier_field_render()

    {

        $carriers = MultiParcels()->carriers->all();



        if (isset($_GET['action']) && in_array($_GET['action'], ['enable_carrier_location_country', 'disable_carrier_location_country'])) {

            if (isset($_GET['carrier']) && isset($_GET['country'])) {

                if ($_GET['action'] == 'enable_carrier_location_country') {

                    MultiParcels()->locations->enabled_extra_country($_GET['carrier'], $_GET['country']);

                } else {

                    MultiParcels()->locations->disable_extra_country($_GET['carrier'], $_GET['country']);

                }



                wp_redirect(MultiParcels()->settings_url());

                exit;

            }

        }

        if (isset($_GET['action']) && in_array($_GET['action'], ['enable_carrier_location_city', 'disable_carrier_location_city'])) {

            if (isset($_GET['carrier']) && isset($_GET['city'])) {

                if ($_GET['action'] == 'enable_carrier_location_city') {

                    MultiParcels()->locations->enabled_extra_city($_GET['carrier'], $_GET['city']);

                } else {

                    MultiParcels()->locations->disable_extra_city($_GET['carrier'], $_GET['city']);

                }



                wp_redirect(MultiParcels()->settings_url());

                exit;

            }

        }

        if (isset($_GET['action']) && in_array($_GET['action'], ['enable_siuntos_autobusais_pickup_location_city', 'disable_siuntos_autobusais_pickup_location_city'])) {

            if (isset($_GET['carrier']) && isset($_GET['city'])) {

                if ($_GET['action'] == 'enable_siuntos_autobusais_pickup_location_city') {

                    MultiParcels()->locations->enabled_extra_siuntos_autobusais_pickup_city($_GET['carrier'], $_GET['city']);

                } else {

                    MultiParcels()->locations->disable_extra_siuntos_autobusais_pickup_city($_GET['carrier'], $_GET['city']);

                }



                wp_redirect(MultiParcels()->settings_url());

                exit;

            }

        }

        if (isset($_GET['action']) && in_array($_GET['action'], ['enable_carrier_location_delivery_pickup_point', 'disable_carrier_location_delivery_pickup_point'])) {

            if (isset($_GET['carrier']) && isset($_GET['delivery_pickup_point'])) {

                if ($_GET['action'] == 'enable_carrier_location_delivery_pickup_point') {



                    MultiParcels()->locations->enabled_extra_delivery_pickup_point($_GET['carrier'], $_GET['delivery_pickup_point']);

                } else {

                    MultiParcels()->locations->disable_extra_delivery_pickup_point($_GET['carrier'], $_GET['delivery_pickup_point']);

                }



                wp_redirect(MultiParcels()->settings_url());

                exit;

            }

        }



        if (MultiParcels()->permissions->isLimited()) {

            if (is_array($carriers)) {



                // show enabled first

                foreach ($carriers as $carrier => $settings) {

                    if (MultiParcels()->options->getBool($carrier)) {

                        $carriers = array_merge([$carrier => $settings], $carriers);

                    }

                }



                foreach ($carriers as $carrier => $settings) {

                    $enabled = MultiParcels()->options->getBool($carrier);



                    ?>

                    <div style="margin-bottom: 15px;display: flex;width: 50%;border-bottom: 1px solid #DDDDDD;padding-bottom: 15px;">

                        <div style="flex: 1;justify-content:  center;align-items: center;display:  flex">

                            <div style="text-align: center">

                                <img src="<?php echo MultiParcels()->public_plugin_url('images/carriers/'.esc_attr($carrier).'.png'); ?>"

                                     style="width: 150px;">

                                <br>

	                            <?php echo MultiParcels()->carriers->name($carrier); ?>



                                <?php



                                if ($settings['has_terminals'] || $settings['has_pickup_points']) {

                                    if (is_array($settings['pickup_points_countries']) && count($settings['pickup_points_countries'])) {

                                        $extraCountries = null;

                                        if (isset($settings['pickup_points_countries_extra'])) {

                                            $extraCountries = $settings['pickup_points_countries_extra'];

                                        }



                                        echo "<br/>";



                                        echo "<small>";

                                        echo __("Pickup locations", 'multiparcels-shipping-for-woocommerce').': ';

                                        echo implode(', ', $settings['pickup_points_countries']);

                                        echo "</small>";

                                        echo "<br/>";



                                        if ($enabled) {
                                            if($settings['carrier_code'] == 'packeta') {
                                                echo '<p>' . __("Delivery methods packeta", 'multiparcels-shipping-for-woocommerce').': ' . '</p>';

                                                echo '<a href="#!" class="js--show-packeta-delivery-methods">' . __("Show delivery methods packeta", 'multiparcels-shipping-for-woocommerce'). '</a>';

                                                echo '<div class="packeta" style="display: none;">';

                                                    echo "<ul style='font-size: 12px;display: flex;flex-wrap:wrap;'>";
                                                        foreach ($settings['delivery_methods'] as $key => $one_pickup_point) {
                                                            $deliveryMethodEnabled = MultiParcels()->locations->check_extra_delivery_method($carrier, $one_pickup_point);

                                                            $icon = '❌';

                                                            $link = MultiParcels()->settings_url([

                                                                'action' => 'enable_carrier_location_delivery_pickup_point',

                                                                'carrier' => $carrier, 'delivery_pickup_point' => $one_pickup_point

                                                            ]);



                                                            if ($deliveryMethodEnabled) {

                                                                $icon = '✅';



                                                                $link = MultiParcels()->settings_url([

                                                                    'action' => 'disable_carrier_location_delivery_pickup_point',

                                                                    'carrier' => $carrier, 'delivery_pickup_point' => $one_pickup_point

                                                                ]);

                                                            }

                                                            $one_pickup_point_text = '';

                                                            $one_pickup_point_text = self::packeta_delivery_method_text($one_pickup_point);

                                                            echo sprintf("<li style='%s'><a href='%s'>%s %s</a></li>", 'flex-basis:100%;', $link, $one_pickup_point_text, $icon);
                                                        }

                                                    echo '</ul>';
                                                echo '</div>';




//                                                    MultiParcels()->options->get('permissions', true);
                                            }
                                        }



                                        if ($extraCountries){

                                            echo "<br/>";

                                            echo "<br/>";

                                            echo "<small>";

                                            echo __("Optional pickup countries", 'multiparcels-shipping-for-woocommerce').': ';

                                            echo "</small>";



                                            echo "<ul style='font-size: 12px;display: flex;flex-wrap:wrap;'>";

                                            foreach ($extraCountries as $country) {

                                                $countryEnabled = MultiParcels()->locations->check_extra_country($carrier, $country);

                                                $icon = '❌';

                                                $link = MultiParcels()->settings_url([

                                                    'action' => 'enable_carrier_location_country',

                                                    'carrier' => $carrier, 'country' => $country

                                                ]);



                                                if ($countryEnabled) {

                                                    $icon = '✅';



                                                    $link = MultiParcels()->settings_url([

                                                        'action' => 'disable_carrier_location_country',

                                                        'carrier' => $carrier, 'country' => $country

                                                    ]);

                                                }



                                                echo sprintf("<li style='%s'><a href='%s'>%s %s</a></li>", 'flex-basis:33.3%;', $link, $country, $icon);

                                            }

                                            echo "</ul>";

                                        }

                                    }

//                                    $pickup_ = null;
//
//                                    if (isset($settings['pickup_points_countries_extra'])) {
//
//                                        $extraCountries = $settings['pickup_points_countries_extra'];
//
//                                    }

                                    if ($enabled) {
                                        if (isset($settings['pickup_points_cities'])) {
                                            echo "<br/>";

                                            echo '<a href="#!" class="js--select-cities-siuntos-autobusais">' . __("Show and select which cities are available for pickup points.", 'multiparcels-shipping-for-woocommerce'). '</a>';

                                            echo "<br/>";

                                            echo '<div class="siuntos-autobusais-pickup-points" style="display: none;">';

                                            echo "<ul style='font-size: 12px;display: flex;flex-wrap:wrap;'>";

                                            foreach ($settings['pickup_points_cities'] as $city_key => $city) {
                                                if ($city_key == 'anyksciai') {
                                                    $city = 'Anykščiai AS';
                                                }

                                                $cityEnabled = MultiParcels()->locations->check_extra_siuntos_autobusais_cities($carrier, $city);

                                                $icon = '❌';

                                                $link = MultiParcels()->settings_url([

                                                    'action' => 'enable_siuntos_autobusais_pickup_location_city',

                                                    'carrier' => $carrier, 'city' => $city

                                                ]);



                                                if ($cityEnabled) {

                                                    $icon = '✅';



                                                    $link = MultiParcels()->settings_url([

                                                        'action' => 'disable_siuntos_autobusais_pickup_location_city',

                                                        'carrier' => $carrier, 'city' => $city

                                                    ]);

                                                }



                                                echo sprintf("<li style='%s'><a href='%s'>%s %s</a></li>", 'flex-basis:33.3%;', $link, $city, $icon);

                                            }

                                            echo "</ul>";

                                            echo "</div>";


                                            echo "<br>";

                                            echo "<small>";

//                                            echo __("Optional pickup cities", 'multiparcels-shipping-for-woocommerce').': ';

                                            echo "</small>";

                                            echo "<br>";

                                            echo '<a href="#!" class="js--show-siuntos-autobusais-pickup-cities">' . __("Show and select the city from which to send shipments.", 'multiparcels-shipping-for-woocommerce'). '</a>';

                                            echo '<div class="siuntos-autobusais" style="display: none;">';

                                            echo "<ul style='font-size: 12px;display: flex;flex-wrap:wrap;'>";

                                            foreach ($settings['pickup_points_cities'] as $city) {

                                                $cityEnabled = MultiParcels()->locations->check_extra_city($carrier, $city);

                                                $icon = '❌';

                                                $link = MultiParcels()->settings_url([

                                                    'action' => 'enable_carrier_location_city',

                                                    'carrier' => $carrier, 'city' => $city

                                                ]);



                                                if ($cityEnabled) {

                                                    $icon = '✅';



                                                    $link = MultiParcels()->settings_url([

                                                        'action' => 'disable_carrier_location_city',

                                                        'carrier' => $carrier, 'city' => $city

                                                    ]);

                                                }



                                                echo sprintf("<li style='%s'><a href='%s'>%s %s</a></li>", 'flex-basis:33.3%;', $link, $city, $icon);

                                            }

                                            echo "</ul>";

                                            echo '</div>';




                                        }
                                    }


                                }

                                ?>

                            </div>

                        </div>

                        <?php if ($enabled) { ?>

                            <input type="hidden" name="multiparcels_settings[<?php echo $carrier; ?>]" value="1"/>

                            <div style="flex: 1;justify-content:  center;align-items: center;display:  flex; flex-direction: column">

							<?php 
							$set_nonce= wp_create_nonce('_wpnonce2');
							$set_url = admin_url('admin.php?page=wc-settings&tab=shipping').'&_wpnonce2='.$set_nonce;
							?>

                                <div style="margin: 5px 0;">

                                    <a href="<?php echo $set_url ?>"

                                       class="button">

                                        <?php _e('Shipping zones', 'multiparcels-shipping-for-woocommerce') ?>

                                    </a>

                                </div>



	                            <?php if ( $enabled && ( $settings['has_terminals'] || $settings['has_pickup_points'] ) ) {

		                            $locations_count = count( MultiParcels()->locations->all( $carrier ) );



                                    if ($locations_count == 0) {

                                        echo sprintf("<br><span style='%s'>%s</span>",

                                            'color:red;text-align:center;',

                                            __('No locations found. Please try to run manual update',

                                                'multiparcels-shipping-for-woocommerce'));



                                     
										$set_nonce= wp_create_nonce('_wpnonce2');
										$set_url = admin_url('admin-post.php?action=multiparcels_update_data').'&_wpnonce2='.$set_nonce;
										?>

                                        <a href="<?php

                                        echo $set_url; ?>"

                                           class="button button-secondary" style="background: red;color: white;border: 1px solid white;margin-top: 5px;">

                                            <?php

                                            _e('Manual update',

                                                'multiparcels-shipping-for-woocommerce') ?>

                                        </a>

                                        <?php

                                    } ?>

	                            <?php } ?>



                            </div>

                            <div style="padding-left:20px;flex: 1;justify-content:  center;align-items: center;display:  flex;">

                                <?php
                                $set_nonce= wp_create_nonce('_wpnonce2');
                                $set_url = admin_url('admin-post.php?action=multiparcels_carrier_change&change=disable&carrier=' . $carrier).'&_wpnonce2='.$set_nonce;
                                ?>

                                <a href="<?php echo $set_url; ?>"

                                   class="button">

                                    <?php _e('Disable', 'multiparcels-shipping-for-woocommerce') ?>

                                </a>

                            </div>

                        <?php } else { ?>

                            <input type="hidden" name="multiparcels_settings[<?php echo $carrier; ?>]" value="0"/>

                            <div style="flex: 1;justify-content:  center;align-items: center;display:  flex;">
							<?php
							
							$set_nonce= wp_create_nonce('_wpnonce2');
							$set_url = admin_url('admin-post.php?action=multiparcels_carrier_change&change=enable&carrier=' . $carrier).'&_wpnonce2='.$set_nonce;
							
							?>
							
							
							
                                <a href="<?php echo $set_url; ?>"

                                   class="button button-primary">

                                    <?php _e('Enable', 'multiparcels-shipping-for-woocommerce') ?>

                                </a>

                            </div>

                            <div style="flex: 1;justify-content:  center;align-items: center;display:  flex;"></div>

                        <?php } ?>

                    </div>

                    <?php

                }

            } else {

                echo sprintf("<strong style='%s'>%s</strong>",

                    'color: red',

                    __('Please update the data to receive the carriers list', 'multiparcels-shipping-for-woocommerce'));

            }

        }

    }

    public function packeta_delivery_method_text($value)
    {
        $text = '';
        if ($value == 'packeta_lt_omniva_box') {
            $text = 'Lietuvos omniva Box';
        }

        if ($value == 'packeta_lt_omniva_hd') {
            $text = 'Lietuvos omniva Courier';
        }

        if ($value == 'packeta_lt_lithuanian_post_box') {
            $text = 'Lietuvos lp express Box';
        }

        if ($value == 'packeta_lt_lithuanian_post_hd') {
            $text = 'Lietuvos lp express Courier';
        }

        if ($value == 'packeta_de_hermes_hd') {
            $text = 'Vokietijos hermes Courier';
        }

        if ($value == 'packeta_de_hermes_pp') {
            $text = 'Vokietijos hermes Pickup Point';
        }

        if ($value == 'packeta_gb_royal_mail_24_hd') {
            $text = 'GB Royal Mail 24 Courier';
        }

        if ($value == 'packeta_gb_royal_mail_48_hd') {
            $text = 'GB Royal Mail 48 Courier';
        }

        if ($value == 'packeta_nl_dhl_hd') {
            $text = 'Olandijos dhl Courier';
        }

        if ($value == 'packeta_nl_dhl_pp') {
            $text = 'Olandijos dhl Pickup Point';
        }

        if ($value == 'packeta_nl_dutch_post_hd') {
            $text = 'Olandijos dutch post Courier';
        }

        if ($value == 'packeta_cz_packeta_home_hd') {
            $text = 'CZ packeta Courier';
        }

        if ($value == 'packeta_cz_packeta_evening_brno_hd') {
            $text = 'CZ packeta Evening Brno Courier';
        }

        if ($value == 'packeta_cz_packeta_evening_praha_hd') {
            $text = 'CZ packeta Evening Praha Courier';
        }

        if ($value == 'packeta_cz_packeta_evening_ostrava_hd') {
            $text = 'CZ packeta Evening Ostrava Courier';
        }

        if ($value == 'packeta_sk_packeta_home_hd') {
            $text = 'SK Packeta Courier';
        }

        if ($value == 'packeta_si_dpd_hd') {
            $text = 'SI dpd Courier';
        }

        if ($value == 'packeta_si_dpd_pp') {
            $text = 'Suomijos dpd Pickup Point';
        }

        if ($value == 'packeta_si_post_hd') {
            $text = 'Suomijos post Courier';
        }

        if ($value == 'packeta_si_post_pp') {
            $text = 'Suomijos post Pickup Point';
        }

        if ($value == 'packeta_pp') {
            $text = 'Packeta Pickup point';
        }

        if ($value == 'packeta_at_rakouska_post_hd') {
            $text = 'AT Rakouska Courier';
        }

        if ($value == 'packeta_sk_packeta_evening_bratislava_hd') {
            $text = 'SK packeta evening bratislava courier';
        }

        if ($value == 'packeta_ro_cargus_hd') {
            $text = 'RO Cargus Courier';
        }

        if ($value == 'packeta_ro_fan_courier_hd') {
            $text = 'RO Fan Courier';
        }

        if ($value == 'packeta_hu_madarska_post_hd') {
            $text = 'HU Madarska Post Courier';
        }

        if ($value == 'packeta_ua_meest_post_hd') {
            $text = 'UA Meest Post Courier';
        }

        if ($value == 'packeta_pl_inpost_paczkomaty_box') {
            $text = 'PL inpost Paczkomaty Box';
        }

        if ($value == 'packeta_ch_li_svycarska_posta_hd') {
            $text = 'CH LI Svycarska Courier';
        }

        if ($value == 'packeta_ua_nova_poshta_pp') {
            $text = 'UA Nova Poshta Pickup point';
        }

        if ($value == 'packeta_hu_express_one_hd') {
            $text = 'HU Express One Courier';
        }

        if ($value == 'packeta_ch_li_svycarska_posta_prioritni_hd') {
            $text = 'CH li svycarska posta Prioritni Courier';
        }

        if ($value == 'packeta_bg_speedy_hd') {
            $text = 'Bg speedy Courier';
        }

        if ($value == 'packeta_bg_speedy_pp') {
            $text = 'Bg speedy Pickup Point';
        }

        if ($value == 'packeta_hu_doruceni_na_adresu_hd') {
            $text = 'HU doruceni na adresu Courier';
        }

        if ($value == 'packeta_ro_doruceni_na_adresu_hd') {
            $text = 'RO doruceni na adresu Courier';
        }

        if ($value == 'packeta_pl_doruceni_na_adresu_hd') {
            $text = 'PL doruceni na adresu Courier';
        }

        if ($value == 'packeta_fr_colissimo_pp') {
            $text = 'FR colissimo Pickup Point';
        }

        if ($value == 'packeta_fr_colissimo_hd') {
            $text = 'FR colissimo Courier';
        }

        if ($value == 'packeta_hu_madarska_posta_pp') {
            $text = 'HU madarska posta Pickup Point';
        }

        if ($value == 'packeta_hr_chorvatska_posta_hd') {
            $text = 'HR chorvatska posta Courier';
        }

        if ($value == 'packeta_hr_chorvatska_posta_pp') {
            $text = 'HR chorvatska posta Pickup Point';
        }

        if ($value == 'packeta_es_correos_hd') {
            $text = 'ES Correos Courier';
        }

        if ($value == 'packeta_es_mrw_hd') {
            $text = 'ES Mrw Courier';
        }

        if ($value == 'packeta_es_mrw_pp') {
            $text = 'ES Mrw Pickup Point';
        }

        if ($value == 'packeta_pt_mrw_hd') {
            $text = 'PT Mrw Courier';
        }

        if ($value == 'packeta_pt_mrw_pp') {
            $text = 'PT Mrw Pickup Point';
        }

        if ($value == 'packeta_se_post_nord_pp') {
            $text = 'SE post Nord Pickup Point';
        }

        if ($value == 'packeta_se_post_nord_hd') {
            $text = 'SE post Nord Courier';
        }

        if ($value == 'packeta_fi_post_nord_pp') {
            $text = 'Fi post nord Pickup Point';
        }

        if ($value == 'packeta_fi_post_nord_hd') {
            $text = 'Fi post nord Courier';
        }

        if ($value == 'packeta_be_nizozemska_posta_hd') {
            $text = 'BE nizozemska posta Courier';
        }

        if ($value == 'packeta_dk_post_nord_hd') {
            $text = 'DK post nord Courier';
        }

        if ($value == 'packeta_dk_post_nord_pp') {
            $text = 'DK post nord Pickup Point';
        }

        if ($value == 'packeta_ee_omniva_hd') {
            $text = 'EE omniva Courier';
        }

        if ($value == 'packeta_ee_omniva_pp') {
            $text = 'EE omniva Pickup Point';
        }

        if ($value == 'packeta_ee_omniva_box') {
            $text = 'EE omniva Box';
        }

        if ($value == 'packeta_lv_omniva_hd') {
            $text = 'LV omniva Courier';
        }

        if ($value == 'packeta_lv_omniva_box') {
            $text = 'LV omniva Box';
        }

        if ($value == 'packeta_at_dpd_hd') {
            $text = 'AT dpd Courier';
        }

        if ($value == 'packeta_ro_sameday_hd') {
            $text = 'RO sameday Courier';
        }

        if ($value == 'packeta_ro_sameday_box') {
            $text = 'RO sameday Box';
        }

        if ($value == 'packeta_be_belgicka_posta_hd') {
            $text = 'BE belgicka posta Courier';
        }

        if ($value == 'packeta_be_belgicka_posta_pp') {
            $text = 'BE belgicka posta Pickup Point';
        }

        if ($value == 'packeta_lu_lucemburska_posta_hd') {
            $text = 'LU lucemburska posta Courier';
        }

        if ($value == 'packeta_gr_taxydromiki_hd') {
            $text = 'GR taxydromiki Courier';
        }

        if ($value == 'packeta_it_bartolini_hd') {
            $text = 'IT bartolini Courier';
        }

        if ($value == 'packeta_it_bartolini_pp') {
            $text = 'IT bartolini Pickup Point';
        }

        if ($value == 'packeta_ie_fastway_hd') {
            $text = 'IE fastway Courier';
        }

        if ($value == 'packeta_hr_overseas_hd') {
            $text = 'HR overseas Courier';
        }

        if ($value == 'packeta_hr_overseas_pp') {
            $text = 'HR overseas Pickup Point';
        }

        if ($value == 'packeta_ae_aramex_hd') {
            $text = 'AE Aramex Courier';
        }

        if ($value == 'packeta_it_hr_parcel_hd') {
            $text = 'IT HR parcel Courier';
        }

        if ($value == 'packeta_gr_speedex_hd') {
            $text = 'GR speedex Courier';
        }

        if ($value == 'packeta_fr_mondial_relay_pp') {
            $text = 'FR mondial relay Pickup Point';
        }

        if ($value == 'packeta_de_home_delivery_hd') {
            $text = 'DE Home delivery Courier';
        }

        if ($value == 'packeta_pl_polska_posta_pp') {
            $text = 'PL polska posta Pickup Point';
        }

        if ($value == 'packeta_fr_colis_prive_direct_hd') {
            $text = 'FR colis prive direct Courier';
        }

        if ($value == 'packeta_gr_acs_hd') {
            $text = 'GR ACS Courier';
        }

        if ($value == 'packeta_gr_acs_pp') {
            $text = 'GR ACS Pickup Point';
        }

        if ($value == 'packeta_ee_lithuanian_post_hd') {
            $text = 'EE Lithuania post Courier';
        }

        if ($value == 'packeta_lv_lithuanian_post_hd') {
            $text = 'LV Lithuania post Courier';
        }

        if ($value == 'packeta_us_fedex_priority_hd') {
            $text = 'US fedex priority Courier';
        }

        if ($value == 'packeta_us_fedex_economy_hd') {
            $text = 'US fedex economy Courier';
        }

        if ($value == 'packeta_tr_fedex_economy_hd') {
            $text = 'TR fedex economy Courier';
        }

        if ($value == 'packeta_tr_fedex_priority_hd') {
            $text = 'TR fedex priority Courier';
        }

        if ($value == 'packeta_bg_econt_hd') {
            $text = 'BG econt Courier';
        }

        if ($value == 'packeta_bg_econt_box') {
            $text = 'BG econt Box';
        }

        if ($value == 'packeta_bg_econt_pp') {
            $text = 'BG econt Pickup Point';
        }

        if ($value == 'packeta_si_post_box') {
            $text = 'SI post Box';
        }

        if ($value == 'packeta_gr_boxnow_box') {
            $text = 'GR boxnow Box';
        }

        if ($value == 'packeta_bg_doruceni_na_adresu_hd') {
            $text = 'BG doruceni na adresu Courier';
        }

        if ($value == 'packeta_gb_fedex_connect_plus_hd') {
            $text = 'GB fedex connect plus Courier';
        }

        if ($value == 'packeta_gb_fedex_priority_hd') {
            $text = 'GB fedex priority Courier';
        }

        if ($value == 'packeta_ie_fedex_connect_plus_hd') {
            $text = 'IE fedex connect plus Courier';
        }

        if ($value == 'packeta_ie_fedex_priority_hd') {
            $text = 'IE fedex priority Courier';
        }

        if ($value == 'packeta_si_express_one_hd') {
            $text = 'SI Express one Courier';
        }

        if ($value == 'packeta_si_express_one_pp') {
            $text = 'SI Express one Pickup Point';
        }

        if ($value == 'packeta_ee_venipak_hd') {
            $text = 'EE Venipak Courier';
        }

        if ($value == 'packeta_lv_venipak_hd') {
            $text = 'LV Venipak Courier';
        }

        if ($value == 'packeta_lt_venipak_hd') {
            $text = 'LT Venipak Courier';
        }

        if ($value == 'packeta_ee_venipak_pp') {
            $text = 'LT Venipak Pickup Point';
        }

        if ($value == 'packeta_lv_venipak_pp') {
            $text = 'LV Venipak Pickup Point';
        }

        if ($value == 'packeta_lt_venipak_pp') {
            $text = 'LT Venipak Pickup Point';
        }

        if ($value == 'packeta_ee_venipak_box') {
            $text = 'EE Venipak box';
        }

        if ($value == 'packeta_lv_venipak_box') {
            $text = 'LV Venipak box';
        }

        if ($value == 'packeta_lt_venipak_box') {
            $text = 'LT Venipak box';
        }

        if ($value == 'packeta_bg_sameday_hd') {
            $text = 'BG Sameday Courier';
        }

        if ($value == 'packeta_bg_sameday_box') {
            $text = 'BG Sameday Box';
        }

        if ($value == 'packeta_fi_matkahuolto_hd') {
            $text = 'FI matkahuolto Courier';
        }

        if ($value == 'packeta_fi_matkahuolto_pp') {
            $text = 'FI matkahuolto Pickup Point';
        }

        if ($value == 'packeta_fi_matkahuolto_box') {
            $text = 'FI matkahuolto Box';
        }
        if ($value == 'packeta_gr_elta_courier_hd') {
            $text = 'GR Elta courier';
        }
        if ($value == 'packeta_gr_elta_courier_pp') {
            $text = 'GR Elta courier Pickup Point';
        }
        if ($value == 'packeta_it_bartolini_box') {
            $text = 'IT Bartolini Box';
        }
        if ($value == 'packeta_hu_magyar_post_box') {
            $text = 'Hu magyar post box';
        }

        return $text;
    }



    public function default_carrier_field_render()

    {

        $value = MultiParcels()->options->get('default_carrier');



        ?>

        <select name='multiparcels_settings[default_carrier]'>

            <?php

            foreach (MultiParcels()->carriers->all() as $carrier) {

                $enabled = MultiParcels()->options->getBool($carrier['carrier_code']);

                if ($enabled) {

                    $selected = '';

                    if ($value == $carrier['carrier_code']) {

                        $selected = 'selected';

                    }



                    echo sprintf("<option value='%s'%s>%s</option>", $carrier['carrier_code'], $selected,

                        $carrier['name']);

                }

            }



            ?>

        </select>

        <?php

    }



    public function preferred_pickup_type_field_render()

    {

	    $value = MultiParcels()->options->get( 'preferred_pickup_type' );



	    $types = [

		    'terminal' => _x( 'From terminal', 'Pickup type', 'multiparcels-shipping-for-woocommerce' ),

	    ];

        ?>

        <select name='multiparcels_settings[preferred_pickup_type]'>

            <option value="hands"><?php echo _ex('From hands', 'Pickup type', 'multiparcels-shipping-for-woocommerce') ?></option>

            <?php

            foreach ($types as $code => $type ) {

	            $selected = '';

                if ($value == $code) {

                    $selected = 'selected';

                }



                echo sprintf("<option value='%s'%s>%s</option>", $code, $selected,

	                $type);

            }



            ?>

        </select>

        <?php

    }



    public function maximum_items_per_package_field_render()

    {

        $value = MultiParcels()->options->get('default_maximum_items_per_package');



        if ( ! $value) {

            $value = 0;

        }



        ?>

        <input type="number" name='multiparcels_settings[default_maximum_items_per_package]'

               value="<?php echo $value; ?>"/>

        <?php

    }



    function api_key_field_render()

    {

        $value = MultiParcels()->options->get('api_key');



        // Remember the default sender location

        ?>

        <input type="hidden" name="multiparcels_settings[default_sender_location]" value="<?php echo esc_attr(MultiParcels()->options->get('default_sender_location'));?>">



        <div style="float:right;position: relative;width: 250px;">

            <div style="position: absolute;top:0;right:0;background: white; padding: 15px;width: 100%;">

                <strong>

                    <?php _e("Have any suggestions or problems?", 'multiparcels-shipping-for-woocommerce'); ?>

                </strong><br>

                <?php

                echo sprintf(__("Please do not hesitate to <a href='%s'>contact us</a>",

                    'multiparcels-shipping-for-woocommerce'),

                    sprintf('mailto:%s?subject=%s&body=%s',

                        MultiParcels()->contact_email,

                        MultiParcels()->plugin_title,

                        $this->prepare_debug_info()

                    )

                );

                ?>

            </div>

        </div>



        <input type='text' name='multiparcels_settings[api_key]' value='<?php echo $value; ?>'>

        <?php



        $permissions = MultiParcels()->permissions->get();



        if ($value == null || $permissions == null || MultiParcels()->permissions->is_none()) {

            $auto_show = true;



            /**

             * If the API key is provided but wrong - have free API key form ready

             */

            if ($permissions == null || MultiParcels()->permissions->is_none()) {

                $auto_show = false;

            }



            ?>

            <?php add_thickbox(); ?>

            <div id="my-content-id" style="display: none">

                <h3>

                    <?php _e('Get your free API key for terminals/pickup points!',

                        'multiparcels-shipping-for-woocommerce') ?>

                </h3>

                <h4>

                    <?php _e('Features that come with the free version:', 'multiparcels-shipping-for-woocommerce') ?>

                </h4>

                <ol>

                    <li>

                        <?php _e('Easy to configure', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e('Costs 0&euro; per month', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e('No credit card required', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>



                    <li>

                        <?php _e('Automatic daily updated pickup locations', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e('Configure every carrier the way you want', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e('Free shipping option', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e("Shows only possible locations for customer's city",

                            'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e('New carriers without updating the plugin', 'multiparcels-shipping-for-woocommerce') ?>

                    </li>

                    <li>

                        <?php _e('Do you even need more features?', 'multiparcels-shipping-for-woocommerce') ?> <span

                                class="dashicons dashicons-smiley"></span>

                    </li>

                </ol>

                <p>

                    <?php _e('E-mail', 'multiparcels-shipping-for-woocommerce') ?>:



                    <input id="multiparcels-setup-email" type="text" name="email"

                           value="<?php echo esc_attr(wp_get_current_user()->get('user_email')); ?>"><br><br>



                    <button id="multiparcels-setup-submit" type="button" class="button button-primary">

                        <?php _e('Get the free API key', 'multiparcels-shipping-for-woocommerce') ?>

                    </button>

                    <br>



                    <small>

                        *<?php _e( 'By continuing you agree to occasionally receive information about new features, important notices about security etc. to your e-mail address',

		                    'multiparcels-shipping-for-woocommerce' ) ?>

                        <br>

		                <?php _e( 'You can unsubscribe any time!', 'multiparcels-shipping-for-woocommerce' ) ?>

                    </small>

                </p>

            </div>

            <?php $set_nonce= wp_create_nonce('_wpnonce2'); ?>

            <script type="text/javascript">

                jQuery(document).ready(function ($) {

                    $('#multiparcels-setup-submit').on('click', function (event) {

                        event.preventDefault();

                        var newForm = $('<form>', {

                            'action': '<?php echo admin_url('admin-post.php?action=multiparcels_request_api_key&_wpnonce2=' .$set_nonce);?>',

                            'method': 'post'

                        }).append($('<input>', {

                            'name': 'email',

                            'value': $("#multiparcels-setup-email").val(),

                            'type': 'hidden'

                        }));

                        $(document.body).append(newForm);

                        newForm.submit();

                    });

                })

            </script>



            <?php if ($auto_show) { ?>

                <script type="text/javascript">

                    window.onload = function () {

                        tb_show("<?php echo __("MultiParcels Shipping For WooCommerce",

                            'multiparcels-shipping-for-woocommerce');?>", "#TB_inline?inlineId=my-content-id", false);

                    };

                </script>

                <?php

            }

        }

    }



    public function update_field_render()

    {

        $last_update = MultiParcels()->options->get('last_update', true);

//        var_dump($last_update);

//        var_dump(MultiParcels()->options->getBool('multiparcels_loactions_update'));

//		if (!MultiParcels()->options->getBool('multiparcels_loactions_update')){
//
//			wp_clear_scheduled_hook( 'multiparcels_update_data_cron' );
//
//		}

        if (MultiParcels()->options->getBool('multiparcels_loactions_update')){

            wp_clear_scheduled_hook( 'multiparcels_update_data_cron' );

        }

        $next_cron_time = wp_next_scheduled('multiparcels_update_data_cron');
		
        $next_cron      = date('Y-m-d H:i:s', $next_cron_time);





        if ($last_update === null || (is_array($last_update) && count($last_update) === 0)) {

            $last_update = __('Never', 'multiparcels-shipping-for-woocommerce');

        }

//        var_dump(MultiParcels()->options->getBool('multiparcels_loactions_update'));

        // re-add cron if it's in the past
//        if (MultiParcels()->options->getBool('multiparcels_loactions_update')){
//            if ($next_cron_time < time() || $next_cron_time === false) {
//
//                wp_clear_scheduled_hook('multiparcels_update_data_cron');
//
//                $time = sprintf('%d:%d', mt_rand(2, 6), mt_rand(0, 59));
//
//
//                wp_schedule_event(strtotime("next day ".$time."am"), 'daily',
//
//                    'multiparcels_update_data_cron');
//
//
//
//                $next_cron_time = wp_next_scheduled('multiparcels_update_data_cron');
//
//                $next_cron      = date('Y-m-d H:i:s', $next_cron_time);
//
//            }
//        }

		if (!MultiParcels()->options->getBool('multiparcels_loactions_update')){
			if ($next_cron_time < time() || $next_cron_time === false) {

				wp_clear_scheduled_hook('multiparcels_update_data_cron');

				$time = sprintf('%d:%d', mt_rand(2, 6), mt_rand(0, 59));


				wp_schedule_event(strtotime("next day ".$time."am"), 'daily',

					'multiparcels_update_data_cron');



				$next_cron_time = wp_next_scheduled('multiparcels_update_data_cron');

				$next_cron      = date('Y-m-d H:i:s', $next_cron_time);

			}
		}



        ?>

        <a href="<?php echo admin_url('admin-post.php?action=multiparcels_update_data'); ?>"

           class="button button-primary">

            <?php _e('Manual update', 'multiparcels-shipping-for-woocommerce') ?>

        </a>

        <br>

        <br>

        <?php _e('Last update', 'multiparcels-shipping-for-woocommerce') ?>:

        <strong><?php echo $last_update; ?></strong>



        <?php

	
		
        if ($next_cron) { ?>

            <br>

            <?php _e('Next update', 'multiparcels-shipping-for-woocommerce') ?>:

            <strong><?php echo $next_cron; ?></strong>

            <?php

        }

    }



    function multiparcels_settings_section_callback()

    {

    }



    function options_page()

    {

        if (isset($_REQUEST['settings-updated'])) {

            echo '<div class="updated"><p>' . __('Settings saved.',

                    'multiparcels-shipping-for-woocommerce') . '</p></div>';

        }







        ?>

        <form action='options.php' method='post'>
		<?php wp_nonce_field('_wpnonce2','_wpnonce2'); ?>
            <div style="margin-bottom: 15px;">

                <img src="<?php echo MultiParcels()->public_plugin_url('images/logo.svg'); ?>" alt="MultiParcels"

                     style="width: 150px;margin-top: 15px;">

            </div>

            <?php
		
            if (MultiParcels()->helper->has_paysera()) {

                $payseraOptions = get_option('woocommerce_paysera_settings');

                if ($payseraOptions) {

                    if (array_key_exists('paymentCompletedStatus', $payseraOptions) && $payseraOptions['paymentCompletedStatus'] == 'wc-completed'){

                        ?>

                        <div id="message" class="error inline" style="margin-bottom: 15px;">

                            <p>

                                <strong>

                                    <?php

                                    _e("Paysera default paid order status is \"Completed\". If you dispatch an order that has status \"Completed\" - the buyer will not get the tracking code in his email.", 'multiparcels-shipping-for-woocommerce');

                                    echo "<br/>";

                                    _e("Please change the Paysera setting  \"New Order Status\" to \"Pending payment\" and \"Paid Order Status\" to \"Processing\".",

                                        'multiparcels-shipping-for-woocommerce'); ?>

                                    <br>

                                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paysera');?>" class="button button-primary">Paysera <?php                                     echo strtolower(__("Settings",'multiparcels-shipping-for-woocommerce')); ?></a>

                                </strong>

                            </p>

                        </div>

                        <?php

                    }

                }

            }

            ?>

            <a class="nav-tab <?php if ($this->tab == self::TAB_SETTINGS) {

                echo 'nav-tab-active';

            } ?>"

               href="<?php echo MultiParcels()->settings_url(); ?>"><?php _e('Settings',

                    'multiparcels-shipping-for-woocommerce'); ?> </a>



            <a class="nav-tab <?php if ($this->tab == self::TAB_CHECKOUT) {

                echo 'nav-tab-active';

            } ?>"

               href="<?php echo MultiParcels()->settings_url(['tab' => self::TAB_CHECKOUT]); ?>"><?php _e('Checkout',

                    'multiparcels-shipping-for-woocommerce'); ?> </a>



            <a class="nav-tab <?php if ($this->tab == self::TAB_CARRIER_LOGOS) {

                echo 'nav-tab-active';

            } ?>"

               href="<?php echo MultiParcels()->settings_url(['tab' => self::TAB_CARRIER_LOGOS]); ?>"><?php _e('Carrier logos',

                    'multiparcels-shipping-for-woocommerce'); ?> </a>



            <?php if (MultiParcels()->permissions->isFull()) { ?>

                <a class="nav-tab <?php if ($this->tab == self::TAB_SENDER_DETAILS) {

                    echo 'nav-tab-active';

                } ?>"

                   href="<?php echo MultiParcels()->settings_url(['tab' => self::TAB_SENDER_DETAILS]); ?>"><?php _e('Sender details',

                        'multiparcels-shipping-for-woocommerce'); ?> </a>



                <a class="nav-tab <?php if ($this->tab == self::TAB_AUTOMATIC_CONFIRMATION) {

                    echo 'nav-tab-active';

                } ?>"

                   href="<?php echo MultiParcels()->settings_url(['tab' => self::TAB_AUTOMATIC_CONFIRMATION]); ?>"><?php _e('Automatic confirmation',

                        'multiparcels-shipping-for-woocommerce'); ?> </a>



                <?php if (MultiParcels()->permissions->hasAddressAutoComplete()) { ?>

                    <a class="nav-tab <?php if ($this->tab == self::TAB_AUTO_COMPLETE) {

                        echo 'nav-tab-active';

                    } ?>"

                       href="<?php echo MultiParcels()->settings_url(['tab' => self::TAB_AUTO_COMPLETE]); ?>"><?php _e('Address autocomplete',

                            'multiparcels-shipping-for-woocommerce'); ?> </a>

                <?php } ?>



            <?php } else { ?>

                <a class="nav-tab <?php if ($this->tab == self::TAB_FULL_VERSION) {

                    echo 'nav-tab-active';

                } ?>"

                   href="<?php echo MultiParcels()->settings_url(['tab' => self::TAB_FULL_VERSION]); ?>"><?php _e('Full version',

                        'multiparcels-shipping-for-woocommerce'); ?> </a>

            <?php } ?>



            <?php



            settings_fields('multiparcels-shipping-for-woocommerce');

            do_settings_sections('multiparcels-shipping-for-woocommerce');



            if ($this->tab != self::TAB_FULL_VERSION) {

                submit_button();

            }

            ?>



        </form>

        <?php



    }

}



return new MP_Admin();

