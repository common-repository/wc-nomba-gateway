<?php
/**
 * WC_Gateway_Nomba_Subscriptions class
 *
 * @author   Tunbosun Ayinla
 * @package  WooCommerce Nomba Payments Gateway
 * @since    1.0.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * WC Nomba Gateway Blocks Integration
 *
 * @since 1.0.0
 */
final class WC_Gateway_Nomba_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Nomba
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'nomba';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_nomba_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = WC_Nomba_Gateway::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => WC_Nomba_Gateway::WC_NOMBA_VERSION,
			);
		$script_url        = WC_Nomba_Gateway::plugin_url() . $script_path;

		wp_register_script(
			'wc-nomba-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-nomba-blocks', 'wc-nomba-gateway', WC_Nomba_Gateway::plugin_abspath() . 'languages/' );
		}

		return array( 'wc-nomba-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {

		$checkout_image_url = WC_HTTPS::force_https_url( WC_Nomba_Gateway::plugin_url() . '/assets/images/nomba.svg' );

		return array(
			'title'              => $this->get_setting( 'title' ),
			'description'        => $this->get_setting( 'description' ),
			'supports'           => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
			'checkout_image_url' => $checkout_image_url,
		);
	}
}
