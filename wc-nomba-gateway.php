<?php
/**
 * Plugin Name: Nomba Payment Gateway for WooCommerce
 * Requires Plugins: woocommerce
 * Plugin URI: https://nomba.com
 * Description: Nomba payment gateway for WooCommerce.
 * Version: 1.0.0
 *
 * Author: Nomba
 *
 * Text Domain: wc-nomba-gateway
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 6.3
 * Tested up to: 6.5
 *
 * Requires PHP: 7.4
 *
 * WC requires at least: 8.0
 * WC tested up to: 8.7
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC Nomba Payment gateway plugin class.
 *
 * @class WC_Nomba_Gateway
 */
class WC_Nomba_Gateway {

	public const WC_NOMBA_VERSION = '1.0.0';

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		// Nomba Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		// Make the Nomba Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Add Settings plugin action link.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_nomba_woocommerce_block_support' ) );

	}

	/**
	 * Add the Nomba Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {
		if ( class_exists( 'WC_Subscriptions_Order' ) && class_exists( 'WC_Payment_Gateway_CC' ) ) {
			$gateways[] = 'WC_Gateway_Nomba_Subscriptions';
		} else {
			$gateways[] = 'WC_Gateway_Nomba';
		}

		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {
		// Make the WC_Gateway_Nomba class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/class-wc-gateway-nomba.php';
			require_once 'includes/class-wc-gateway-nomba-subscriptions.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Add plugin action links.
	 */
	public static function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="admin.php?page=wc-settings&tab=checkout&section=nomba">' . esc_html__( 'Settings', 'wc-nomba-gateway' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	public static function woocommerce_gateway_nomba_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-gateway-nomba-blocks-support.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_Nomba_Blocks_Support() );
				}
			);
		}
	}
}

add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

WC_Nomba_Gateway::init();
