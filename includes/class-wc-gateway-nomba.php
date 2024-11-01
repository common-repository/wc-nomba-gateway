<?php
/**
 * WC_Gateway_Nomba class
 *
 * @author   Tunbosun Ayinla
 * @package  WooCommerce Nomba Payments Gateway
 * @since    1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC Nomba Gateway.
 *
 * @class    WC_Gateway_Nomba
 * @version  1.0.0
 */
class WC_Gateway_Nomba extends WC_Payment_Gateway_CC {

	public $id = 'nomba';

	public bool $test_mode;

	public bool $saved_cards;

	public string $account_id;

	public string $client_id;

	public string $private_key;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->icon       = apply_filters( 'woocommerce_nomba_gateway_icon', '' );
		$this->has_fields = true;
		$this->supports   = array(
			'products',
			'refunds',
			'tokenization',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions',
		);

		$this->method_title       = _x( 'Nomba', 'Nomba payment method', 'wc-nomba-gateway' );
		$this->method_description = __( 'Accept Payment via Nomba.', 'wc-nomba-gateway' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled     = $this->get_option( 'enabled' );
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->test_mode   = $this->get_option( 'test_mode' ) === 'yes';
		$this->saved_cards = $this->get_option( 'saved_cards' ) === 'yes';
		$this->account_id  = $this->test_mode ? $this->get_option( 'test_account_id' ) : $this->get_option( 'live_account_id' );
		$this->client_id   = $this->test_mode ? $this->get_option( 'test_client_id' ) : $this->get_option( 'live_client_id' );
		$this->private_key = $this->test_mode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'live_private_key' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_api_wc_gateway_nomba', array( $this, 'process_nomba_payment' ) );
		add_action( 'woocommerce_api_wc_nomba_webhook', array( $this, 'process_webhooks' ) );
	}

	public function admin_options() {
		$webhook_url = WC()->api_request_url( 'wc_nomba_webhook' );
		?>

		<h3><?php __( 'Nomba', 'wc-nomba-gateway' ); ?></h3>

		<h4>Required: To avoid situations where bad network makes it impossible to verify transactions, set your webhook
			URL <a href="https://dashboard.nomba.com/dashboard/control/settings/webhook/" target="_blank"
				   rel="noopener noreferrer">here</a> to the URL below and ensure the "Payment success" event is selected<strong style="color: red">
				<pre><code><?php echo esc_url( strtolower( $webhook_url ) ); ?></code></pre>
			</strong></h4>

		<?php

		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	/**
	 * Display the payment icon on the checkout page
	 */
	public function get_icon() {
		$icon = '<img src="' . \WC_HTTPS::force_https_url( WC_Nomba_Gateway::plugin_url() . '/assets/images/nomba.svg' ) . '" alt="Nomba payment methods" />';

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'          => array(
				'title'   => __( 'Enable/Disable', 'wc-nomba-gateway' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Nomba', 'wc-nomba-gateway' ),
				'default' => 'yes',
			),
			'title'            => array(
				'title'       => __( 'Title', 'wc-nomba-gateway' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-nomba-gateway' ),
				'default'     => _x( 'Accept Secure Payment via Nomba', 'Nomba payment method title', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'description'      => array(
				'title'       => __( 'Description', 'wc-nomba-gateway' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-nomba-gateway' ),
				'default'     => _x( 'Accept Card (Mastercard, Visa, Verve & Amex) and Bank Transfer payments via Nomba', 'Nomba payment method description', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'saved_cards'      => array(
				'title'       => __( 'Payment via Saved Cards', 'wc-nomba-gateway' ),
				'label'       => __( 'Enable Payment via Saved Cards', 'wc-nomba-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Allow customers to make payment using their saved cards. Card details are saved securely on Nomba servers.', 'wc-nomba-gateway' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'test_mode'        => array(
				'title'       => __( 'Test Mode', 'wc-nomba-gateway' ),
				'label'       => __( 'Enable Test Mode', 'wc-nomba-gateway' ),
				'type'        => 'checkbox',
				'description' => __( 'Test mode enables you to test payments before going live. <br />Once you are live uncheck this.', 'wc-nomba-gateway' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'api_details'      => array(
				'title'       => __( 'API credentials', 'wc-nomba-gateway' ),
				'type'        => 'title',
				/* translators: %s: Nomba Dashboard URL */
				'description' => sprintf( __( 'Enter your Nomba API credentials. You can get your Nomba API credentials on your <a href="%s" target="_blank">Nomba Dashboard</a>.', 'wc-nomba-gateway' ), 'https://dashboard.nomba.com/dashboard/control/settings/apikeys' ),
			),
			'test_client_id'   => array(
				'title'       => __( 'Test Client ID', 'wc-nomba-gateway' ),
				'type'        => 'text',
				'description' => __( 'Test Client ID.', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'test_private_key' => array(
				'title'       => __( 'Test Private Key', 'wc-nomba-gateway' ),
				'type'        => 'password',
				'description' => __( 'Test Private Key.', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'test_account_id'  => array(
				'title'       => __( 'Test Account ID', 'wc-nomba-gateway' ),
				'type'        => 'text',
				'description' => __( 'Test Account ID.', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'live_client_id'   => array(
				'title'       => __( 'Live Client ID', 'wc-nomba-gateway' ),
				'type'        => 'text',
				'description' => __( 'Live Client ID.', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'live_private_key' => array(
				'title'       => __( 'Live Private Key', 'wc-nomba-gateway' ),
				'type'        => 'password',
				'description' => __( 'Live Private Key.', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
			'live_account_id'  => array(
				'title'       => __( 'Live Account ID', 'wc-nomba-gateway' ),
				'type'        => 'text',
				'description' => __( 'Live Account ID.', 'wc-nomba-gateway' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Check if Nomba gateway is enabled and configured.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled ) {
			if ( empty( $this->client_id ) || empty( $this->account_id ) || empty( $this->private_key ) ) {
				return false;
			}
		}

		if ( strtoupper( get_woocommerce_currency() ) !== 'NGN' ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( $this->description ) );
		}

		if ( ! $this->saved_cards ) {
			return;
		}

		if ( ! is_ssl() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->supports( 'tokenization' ) ) {
			return;
		}

		$this->tokenization_script();
		$this->saved_payment_methods();
		$this->save_payment_method_checkbox();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array|void
	 */
	public function process_payment( $order_id ) {

		$token_field = "wc-{$this->id}-payment-token";

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_POST[ $token_field ] ) && 'new' !== $_POST[ $token_field ] ) {

            // phpcs:ignore WordPress.Security.NonceVerification
			$token_id = wc_clean( $_POST[ $token_field ] );
			$token    = WC_Payment_Tokens::get( $token_id );

			if ( ! $token || $token->get_user_id() !== get_current_user_id() ) {
				wc_add_notice( 'Invalid token', 'error' );
				return;
			}

			$status = $this->process_token_payment( $token->get_token(), $order_id );

			if ( $status ) {
				$order = wc_get_order( $order_id );

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		} else {

			$new_token_field = "wc-{$this->id}-new-payment-method";

			$order = wc_get_order( $order_id );

			// phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_POST[ $new_token_field ] ) && is_user_logged_in() && true === (bool) $_POST[ $new_token_field ] ) {
				$order->update_meta_data( '_wc_nomba_save_card', true );

				$order->save();
			}

			$checkout_url = $this->process_standard_checkout_payment( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => $checkout_url,
			);
		}
	}

	/**
	 * @param int $order_id Order ID.
	 *
	 * @return string|void
	 * @throws Exception
	 */
	public function process_standard_checkout_payment( $order_id ) {

		$checkout_order_api_url = 'https://api.nomba.com/v1/checkout/order';

		if ( $this->test_mode ) {
			$checkout_order_api_url = 'https://sandbox.nomba.com/v1/checkout/order';
		}

		$order = wc_get_order( $order_id );

		$merchant_ref = 'WC-' . $order->get_id() . '-' . time();
		$merchant_ref = strtolower( $merchant_ref );

		$callback_url = WC()->api_request_url( 'wc_gateway_nomba' );

		$should_tokenize_card = false;
		if ( $this->saved_cards || $this->order_contains_subscription( $order->get_id() ) ) {
			$should_tokenize_card = true;
		}

		$body = array(
			'order'        => array(
				'orderReference' => strtolower( $merchant_ref ),
				'callbackUrl'    => strtolower( $callback_url ),
				'customerEmail'  => strtolower( $order->get_billing_email() ),
				'amount'         => round( $order->get_total(), 2 ),
				'currency'       => $order->get_currency(),
			),
			'tokenizeCard' => $should_tokenize_card,
		);

		$access_token = $this->getAccessToken();

		if ( false === $access_token ) {
			throw new \Exception( esc_html__( 'Unable to process payment try again', 'wc-nomba-gateway' ) );
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

		$request = wp_remote_post( $checkout_order_api_url, $args );

		$response = json_decode( wp_remote_retrieve_body( $request ) );

		if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {
			if ( isset( $response->code, $response->data->success, $response->data->checkoutLink ) && ( '00' === $response->code ) && $response->data->success ) {
				$order->update_meta_data( '_nomba_order_id', $response->data->orderReference );
				$order->update_meta_data( '_nomba_order_reference', $merchant_ref );
				$order->save();

				return $response->data->checkoutLink;
			}
		}

		throw new \Exception( esc_html__( 'Unable to process payment try again.', 'wc-nomba-gateway' ) );
	}

	/**
	 * Update order details.
	 *
	 * @param WC_Order $order
	 * @param object $nomba_txn
	 *
	 * @return void
	 */
	private function update_order( WC_Order $order, $nomba_txn ) {

		$order_id        = $order->get_id();
		$order_total     = round( $order->get_total(), 2 );
		$order_currency  = $order->get_currency();
		$currency_symbol = get_woocommerce_currency_symbol( $order_currency );
		$amount_paid     = $nomba_txn->data->amount;

		// check if the amount paid is equal to the order amount.
		if ( $amount_paid < $order_total ) {

			$order->update_status( 'on-hold' );

			$order->set_transaction_id( $nomba_txn->data->id );

			$notice      = __( 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on hold.<br />Kindly contact us for more information regarding your order and payment status.', 'wc-nomba-gateway' );
			$notice_type = 'notice';

			// Add Customer Order Note
			$order->add_order_note( $notice, 1 );

			// Add Admin Order Note
			/* translators: 1: Amount paid currency symbol 2: Amount paid 3: Order total currency symbol 4: Order total 5: Nomba transaction ID */
			$admin_order_note = sprintf( __( '<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>%1$s%2$s</strong> while the total order amount is <strong>%3$s%4$s</strong><br /><strong>Nomba Transaction ID: </strong> %5$s', 'wc-nomba-gateway' ), $currency_symbol, $amount_paid, $currency_symbol, $order_total, $nomba_txn->data->id );
			$order->add_order_note( $admin_order_note );

			wc_add_notice( $notice, $notice_type );

		} else {
			$order->payment_complete( $nomba_txn->data->id );
			/* translators: Nomba transaction ID. */
			$order->add_order_note( sprintf( __( 'Payment via Nomba successful.<br /><strong>Nomba Transaction ID:</strong> %s.', 'wc-nomba-gateway' ), $nomba_txn->data->id ) );
		}

		$order->save();

		WC()->cart->empty_cart();

		$this->save_card_details( $nomba_txn, $order->get_user_id(), $order_id );
	}

	protected function getAccessToken() {
		$nomba_credentials = get_transient( 'wc_nomba_credentials' );

		if ( false !== $nomba_credentials && ( $this->account_id === $nomba_credentials['account_id'] ) && ( $this->test_mode === $nomba_credentials['is_test_mode'] ) ) {
			return $nomba_credentials['access_token'];
		}

		$api_url = 'https://api.nomba.com/v1/auth/token/issue';

		if ( $this->test_mode ) {
			$api_url = 'https://sandbox.nomba.com/v1/auth/token/issue';
		}

		$body = array(
			'grant_type'    => 'client_credentials',
			'client_id'     => $this->client_id,
			'client_secret' => $this->private_key,
		);

		$headers = array(
			'Content-Type' => 'application/json',
			'accountId'    => $this->account_id,
		);

		$args = array(
			'body'    => wp_json_encode( $body ),
			'headers' => $headers,
			'timeout' => 60,
		);

		$request = wp_remote_post( $api_url, $args );

		if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );

			if ( isset( $response->code ) && '00' === $response->code ) {

				$nomba_credentials = array(
					'access_token'  => $response->data->access_token,
					'refresh_token' => $response->data->refresh_token,
					'account_id'    => $response->data->businessId,
					'is_test_mode'  => $this->test_mode,
				);

				$expiration = strtotime( $response->data->expiresAt ) - ( time() + 300 );

				set_transient( 'wc_nomba_credentials', $nomba_credentials, $expiration );

				return $response->data->access_token;
			}
		}

		return false;
	}

	public function process_webhooks() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			exit;
		}

		sleep( 10 );

		$event = file_get_contents( 'php://input' );
		$event = json_decode( $event );

		if ( ! isset( $event->event_type ) ) {
			return;
		}

		if ( 'payment_success' !== $event->event_type ) {
			http_response_code( 200 );
			return;
		}

		$nomba_txn = $this->get_nomba_transaction( $event->data->order->orderId );

		if ( false === $nomba_txn ) {
			return;
		}

		if ( empty( $nomba_txn->data->onlineCheckoutOrderReference ) ) {
			return;
		}

		$order_details = explode( '-', $nomba_txn->data->onlineCheckoutOrderReference ); // @codingStandardsIgnoreLine.

		$order_id = (int) $order_details[1];

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$nomba_order_id        = $order->get_meta( '_nomba_order_id' );
		$nomba_order_reference = $order->get_meta( '_nomba_order_reference' );

		$is_valid_order_id        = strtolower( $nomba_order_id ) === strtolower( $nomba_txn->data->onlineCheckoutOrderId );
		$is_valid_order_reference = strtolower( $nomba_order_reference ) === strtolower( $nomba_txn->data->onlineCheckoutOrderReference );

		if ( ! ( $is_valid_order_id || $is_valid_order_reference ) ) {
			return;
		}

		http_response_code( 200 );

		if ( in_array( strtolower( $order->get_status() ), array( 'processing', 'completed', 'on-hold' ), true ) ) {
			exit;
		}

		$this->update_order( $order, $nomba_txn );

		exit;
	}

	public function process_nomba_payment() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$reference = sanitize_text_field( $_REQUEST['orderId'] );

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@ob_clean();

		if ( ! $reference ) {
			wp_safe_redirect( wc_get_page_permalink( 'cart' ) );

			exit;
		}

		$nomba_txn = $this->get_nomba_transaction( $reference );

		if ( false === $nomba_txn || ! isset( $nomba_txn->data->id ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'checkout' ) );

			exit;
		}

		if ( 'success' === strtolower( trim( $nomba_txn->data->status ) ) ) {

			$order_details = explode( '-', $nomba_txn->data->onlineCheckoutOrderReference );
			$order_id      = (int) $order_details[1];
			$order         = wc_get_order( $order_id );

			// Bail if unable to retrieve order details
			if ( ! $order ) {
				wp_safe_redirect( wc_get_page_permalink( 'cart' ) );

				exit;
			}

			if ( in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ), true ) ) {
				wp_safe_redirect( $this->get_return_url( $order ) );
				exit;
			}

			$this->update_order( $order, $nomba_txn );

			wp_safe_redirect( $this->get_return_url( $order ) );

			exit;
		}

		$order_details = explode( '-', $nomba_txn->data->onlineCheckoutOrderReference );
		$order_id      = (int) $order_details[1];
		$order         = wc_get_order( $order_id );

		/* translators: Nomba transaction ID */
		$error_message = sprintf( __( 'Payment via Nomba failed.<br />Transaction ID: %s', 'wc-nomba-gateway' ), $nomba_txn->data->id );

		if ( ! empty( $nomba_txn->data->gatewayMessage ) ) {
			/* translators: 1: Nomba transaction ID. 2: Failed payment reason */
			$error_message = sprintf( __( 'Payment via Nomba failed.<br />Transaction ID: %1$s.<br />Reason: %2$s.<br />', 'wc-nomba-gateway' ), $nomba_txn->data->id, $nomba_txn->data->gatewayMessage );
		}

		if ( $order->has_status( 'failed' ) ) {
			$order->add_order_note( $error_message );
		} else {
			$order->update_status( 'failed', $error_message );
		}

		wp_safe_redirect( wc_get_checkout_url() );

		exit;
	}

	/**
	 * Process a token payment.
	 *
	 * @param $token
	 * @param $order_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function process_token_payment( $token, $order_id ) {

		if ( ! ( $token && $order_id ) ) {
			throw new \Exception( esc_html__( 'Payment failed', 'wc-nomba-gateway' ) );
		}

		$api_url = 'https://api.nomba.com/v1/checkout/tokenized-card-payment';

		if ( $this->test_mode ) {
			$api_url = 'https://sandbox.nomba.com/v1/checkout/tokenized-card-payment';
		}

		$order = wc_get_order( $order_id );

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
				'amount'         => round( $order->get_total(), 2 ),
				'currency'       => $order->get_currency(),
			),
			'tokenKey' => $token_key,
		);

		$access_token = $this->getAccessToken();

		if ( false === $access_token ) {
			throw new \Exception( esc_html__( 'Unable to process payment try again', 'wc-nomba-gateway' ) );
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

			/* translators: %s: reason for failed payment */
			$error_message = isset( $response->data->message ) ? sprintf( __( 'Payment Failed. Reason: %s', 'wc-nomba-gateway' ), $response->data->message ) : __( 'Payment Failed', 'wc-nomba-gateway' );

			throw new \Exception( esc_html( $error_message ) );
		}

		return false;
	}

	public function get_nomba_transaction( $transaction_ref ) {
		$api_url = 'https://api.nomba.com/v1/transactions/accounts/single';
		if ( $this->test_mode ) {
			$api_url = 'https://sandbox.nomba.com/v1/transactions/accounts/single';
		}

		$access_token = $this->getAccessToken();

		if ( false === $access_token ) {
			return false;
		}

		$body = array(
			'orderId' => $transaction_ref,
		);

		$headers = array(
			'Authorization' => $access_token,
			'accountId'     => $this->account_id,
		);

		$args = array(
			'body'    => $body,
			'headers' => $headers,
			'timeout' => 60,
		);

		$request = wp_remote_get( $api_url, $args );

		if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $request ) ) {
			return json_decode( wp_remote_retrieve_body( $request ) );
		}

		return false;
	}

	/**
	 * Show new card can only be added when placing an order notice.
	 */
	public function add_payment_method() {
		wc_add_notice( __( 'You can only add a new card when placing an order.', 'wc-nomba-gateway' ), 'error' );
	}

	/**
	 * Save Customer Card Details.
	 *
	 * @param $nomba_txn
	 * @param $user_id
	 * @param $order_id
	 */
	public function save_card_details( $nomba_txn, $user_id, $order_id ) {

		$this->save_subscription_payment_token( $order_id, $nomba_txn );

		$order = wc_get_order( $order_id );

		$save_card = $order->get_meta( '_wc_nomba_save_card' );

		if ( ! $user_id ) {
			return;
		}

		if ( ! $save_card ) {
			return;
		}

		if ( 'card_payment' !== $nomba_txn->data->onlineCheckoutPaymentMethod ) {
			return;
		}

		if ( empty( $nomba_txn->data->onlineCheckoutTokenKey ) || empty( $nomba_txn->data->onlineCheckoutCustomerEmail ) ) {
			return;
		}

		$gateway_id = $order->get_payment_method();

		$last4          = $nomba_txn->data->onlineCheckoutCardPanLast4Digits;
		$exp_year       = $nomba_txn->data->onlineCheckoutTokenExpiryYear;
		$brand          = $nomba_txn->data->onlineCheckoutCardType;
		$exp_month      = $nomba_txn->data->onlineCheckoutTokenExpiryMonth;
		$auth_code      = $nomba_txn->data->onlineCheckoutTokenKey;
		$customer_email = $nomba_txn->data->onlineCheckoutCustomerEmail;

		$payment_token = "$auth_code###$customer_email";

		$token = new \WC_Payment_Token_CC();
		$token->set_token( $payment_token );
		$token->set_gateway_id( $gateway_id );
		$token->set_card_type( strtolower( $brand ) );
		$token->set_last4( $last4 );
		$token->set_expiry_month( $exp_month );
		$token->set_expiry_year( $exp_year );
		$token->set_user_id( $user_id );
		$token->save();

		$order->delete_meta_data( '_wc_nomba_save_card' );
		$order->save();
	}

	/**
	 * Save payment token to the order for automatic renewal for further subscription payment.
	 *
	 * @param $order_id
	 * @param $nomba_txn
	 */
	public function save_subscription_payment_token( $order_id, $nomba_txn ) {

		if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return;
		}

		if ( ! $this->order_contains_subscription( $order_id ) ) {
			return;
		}

		if ( 'card_payment' !== $nomba_txn->data->onlineCheckoutPaymentMethod ) {
			return;
		}

		if ( empty( $nomba_txn->data->onlineCheckoutTokenKey ) || empty( $nomba_txn->data->onlineCheckoutCustomerEmail ) ) {
			return;
		}

		$auth_code      = $nomba_txn->data->onlineCheckoutTokenKey;
		$customer_email = $nomba_txn->data->onlineCheckoutCustomerEmail;

		$payment_token = "$auth_code###$customer_email";

		// Also store it on the subscriptions being purchased or paid for in the order
		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order_id ) ) {

			$subscriptions = wcs_get_subscriptions_for_order( $order_id );

		} elseif ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order_id ) ) {

			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );

		} else {

			$subscriptions = array();

		}

		if ( empty( $subscriptions ) ) {
			return;
		}

		foreach ( $subscriptions as $subscription ) {
			$subscription->update_meta_data( '_nomba_token', $payment_token );
			$subscription->save();
		}
	}

	/**
	 * Check if an order contains a subscription.
	 *
	 * @param int $order_id WC Order ID.
	 *
	 * @return bool
	 */
	public function order_contains_subscription( $order_id ) {
		return function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) );
	}
}
