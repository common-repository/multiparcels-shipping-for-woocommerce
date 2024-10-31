<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Class MultiParcels_Services
 */
class MultiParcels_Services {
	const SERVICE_REGISTERED = 'registered';
	const SERVICE_REGISTERED_AND_PRIORITY = 'registered_and_priority';
	const SERVICE_SATURDAY = 'saturday';
	const SERVICE_B2C = 'b2c';
	const SERVICE_COD = 'cod';
	const SERVICE_SKIP_WAREHOUSE_INTEGRATIONS = 'skip_warehouse_integrations';
	const SERVICE_SAME_DAY_DELIVERY = 'same_day_delivery';
	const SERVICE_RETURN_LABEL = 'return_label';
	const SERVICE_UNREGISTERED = 'unregistered';
	const SERVICE_KEEP_FOR_10_DAYS_IN_POST_OFFICE = 'keep_for_10_days_in_post_office';
	const SERVICE_LABEL_AT_TERMINAL = 'label_at_terminal';
	const SERVICE_SWAP_PARCEL_CONTENT_EXCHANGE = 'swap_parcel_content_exchange';
	const SERVICE_INSURANCE = 'insurance';

	public function title( $code ) {
		return self::service_title( $code );
	}

	public static function service_title( $code ) {
		$title = $code;
		if ( $code == self::SERVICE_REGISTERED ) {
			$title = __( 'Registered', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_REGISTERED_AND_PRIORITY ) {
			$title = __( 'Registered and priority', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_SATURDAY ) {
			$title = __( 'Saturday delivery', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_B2C ) {
			$title = __( 'Delivery to private person', 'multiparcels-shipping-for-woocommerce' ) . ' (B2C)';
		}

		if ( $code == self::SERVICE_COD ) {
			$title = __( 'Cash-on-delivery (COD)', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_SKIP_WAREHOUSE_INTEGRATIONS ) {
			$title = __( 'Do not use warehouse integrations', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_SAME_DAY_DELIVERY ) {
			$title = __( 'Same day delivery', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_RETURN_LABEL ) {
			$title = __( 'Return label', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_UNREGISTERED ) {
			$title = __( 'Unregistered', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_KEEP_FOR_10_DAYS_IN_POST_OFFICE ) {
			$title = __( 'Keep for 10 days in post office', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_LABEL_AT_TERMINAL ) {
			$title = __( 'Print label at terminal', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_SWAP_PARCEL_CONTENT_EXCHANGE ) {
			$title = __( 'Parcel content exchange (SWAP)', 'multiparcels-shipping-for-woocommerce' );
		}

		if ( $code == self::SERVICE_INSURANCE ) {
			$title = __( 'Insurance', 'multiparcels-shipping-for-woocommerce' );
		}

		return $title;
	}
}

return new MultiParcels_Services();
