<?php
/**
 * WC_Gateway_Nomba_Subscriptions class
 *
 * @author   Tunbosun Ayinla
 * @package  WooCommerce Nomba Payments Gateway
 * @since    1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC Nomba Gateway Woo Subscriptions Integration
 *
 * @since 1.0.0
 */
class WC_Gateway_Nomba_Subscriptions extends WC_Gateway_Nomba {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {

			add_action(
				'woocommerce_scheduled_subscription_payment_' . $this->id,
				array(
					$this,
					'scheduled_subscription_payment',
				),
				10,
				2
			);

		}
	}

	/**
	 * Process a trial subscription order with 0 total.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Check for trial subscription order with 0 total.
		if ( $this->order_contains_subscription( $order ) && $order->get_total() == 0 ) { // @codingStandardsIgnoreLine.

			$order->payment_complete();

			$order->add_order_note( __( 'This subscription has a free trial, reason for the 0 amount', 'wc-nomba-gateway' ) );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);

		}

		return parent::process_payment( $order_id );
	}

	/**
	 * Process a subscription renewal.
	 *
	 * @param float $amount_to_charge Subscription payment amount.
	 * @param WC_Order $renewal_order Renewal Order.
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {

		$response = $this->process_subscription_payment( $renewal_order, $amount_to_charge );

		if ( is_wp_error( $response ) ) {
			if ( $renewal_order->has_status( 'failed' ) ) {
				$renewal_order->add_order_note( $response->get_error_message() );
			} else {
				$renewal_order->update_status( 'failed', $response->get_error_message() );
			}
		}
	}

	/**
	 * Process a subscription renewal payment.
	 *
	 * @param WC_Order $order Subscription renewal order.
	 * @param float $amount Subscription payment amount.
	 *
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order, $amount ) {

		$token = $order->get_meta( '_nomba_token' );

		if ( empty( $token ) ) {
			return new WP_Error( 'nomba_error', __( 'This subscription can&#39;t be renewed automatically. The customer will have to login to their account to renew their subscription', 'wc-nomba-gateway' ) );
		}

		$api_url = 'https://api.nomba.com/v1/checkout/tokenized-card-payment';

		if ( $this->test_mode ) {
			$api_url = 'https://sandbox.nomba.com/v1/checkout/tokenized-card-payment';
		}

		$merchant_ref = 'WC-' . $order->get_id() . '-' . time();
		$merchant_ref = strtolower( $merchant_ref );

		$callback_url = WC()->api_request_url( 'wc_gateway_nomba' );

		$payment_token  = explode( '###', $token );
		$token_key      = $payment_token[0];
		$customer_email = $payment_token[1];

		$body = array(
			'order'    => array(
				'orderReference' => $merchant_ref,
				'callbackUrl'    => $callback_url,
				'customerEmail'  => $customer_email,
				'amount'         => round( $amount, 2 ),
				'currency'       => $order->get_currency(),
			),
			'tokenKey' => $token_key,
		);

		$access_token = $this->getAccessToken();

		if ( false === $access_token ) {
			return new WP_Error( 'nomba_error', __( 'Unable to process subscription renewal payment.', 'wc-nomba-gateway' ) );
		}

		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $access_token,
			'accountId'     => $this->account_id,
		);

		$args = array(
			'body'    => wp_json_encode( $body ),
			'headers' => $headers,
			'timeout' => 60,
		);

		$request = wp_remote_post( $api_url, $args );

		if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {

			$response = json_decode( wp_remote_retrieve_body( $request ) );

			if ( isset( $response->code, $response->data->status ) && ( '00' === $response->code ) && ( true === $response->data->status ) ) {

				$order->update_meta_data( '_nomba_order_id', $response->data->orderId );
				$order->update_meta_data( '_nomba_order_reference', $response->data->orderReference );
				$order->save();

				$nomba_order_id = $response->data->orderId;

				$order->payment_complete( $nomba_order_id );

				/* translators: %s Nomba Online Checkout Order ID */
				$message = sprintf( __( 'Payment via Nomba successful (Nomba Order ID: %s)', 'wc-nomba-gateway' ), $nomba_order_id );

				$order->add_order_note( $message );

				return true;
			}

			$gateway_response = __( 'Nomba payment failed.', 'wc-nomba-gateway' );

			if ( ! empty( $response->data->message ) ) {
				/* translators: %s transaction failure reason */
				$gateway_response = sprintf( __( 'Nomba payment failed. Reason: %s.', 'wc-nomba-gateway' ), $response->data->message );
			}

			return new WP_Error( 'nomba_error', $gateway_response );
		}

		return new WP_Error( 'nomba_error', __( 'Nomba payment failed', 'wc-nomba-gateway' ) );
	}

}
