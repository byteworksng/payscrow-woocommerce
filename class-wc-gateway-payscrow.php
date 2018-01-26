<?php


/**
 * Created by Byteworks Limited.
 * Author: Chibuzor Ogbu
 * Date: 5/29/16
 * Time: 11:00 PM
 */

if ( !defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}


/**
 * PayScrow Premium Escrow Service Gateway
 *
 * Provides a PayScrow Premium Escrow Service Gateway.
 *
 * @class        WC_Payscrow
 * @extends        WC_Gateway_Payscrow
 * @version        2.0.0
 * @package        WooCommerce/Classes/Payment
 * @author        WooThemes
 */
class WC_Gateway_Payscrow extends WC_Payment_Gateway
{
    /**
     * configure gateway with required parameters using required constructs
     */

    public function __construct()
    {
        //PayScrow required constructs
        $this->live_url = 'https://www.payscrow.net';
        $this->test_url = "http://payscrow.w12.wh-2.com";
        $this->test_merchant_id = 'd1e5a982-fa05-414b-803c-b197307e4918';

        //woo required constructs
        $this->id = 'payscrow';

        $this->has_fields = false;
        $this->method_title = __( 'payscrow', 'woocommerce' );
        $this->method_description = __(
            'Provides safe payment processing with the PayScrow Premium Escrow Service', 'woocommerce'
        );

        //custom constructs
        $this->order_button_text = __( 'Pay with PayScrow', 'woocommerce' );
        $this->currency_code = '566';
        $this->notify_url = WC()->api_request_url( 'WC_Gateway_Payscrow' );
        $this->supports = [ 'products' ];

        // loading required settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option( 'title' );
        $this->merchant_id = $this->get_option( 'merchant_id' );
        $this->description = $this->get_option( 'description' );
        $this->test_mode = $this->get_option( 'test_mode' );
        $this->debug = $this->get_option( 'debug' );
        $this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );
        $this->payment_action = $this->get_option( 'payment_action', 'sale' );
        $this->show_payscrow_logo = $this->get_option( 'show_payscrow_logo' );
        $this->show_black_logo = $this->get_option( 'show_black_logo' );
        $this->delivery_duration = $this->get_option( 'delivery_duration' );

        $this->payscrow_address = "yes" == $this->test_mode
            ? $this->test_url
            : $this->live_url;


        $charges = $this->getGatewayCharges();

        if (  'yes' == $this->show_payscrow_logo )
        {
            if (  'yes' == $this->show_black_logo )
            {
                $this->icon = "https://payscrow.net/assets/logos/logo-black.png";//plugins_url() . '/fidelity-interswitch-payment-gateway/assets/images/fidelity-paygate.jpg';
            }
            else $this->icon = "https://payscrow.net/assets/logos/logo.png";//plugins_url() . '/fidelity-interswitch-payment-gateway/assets/images/fidelity-paygate.jpg';


//$this->icon =  $label;
        }

        // Logs
        if ( 'yes' == $this->debug  )
        {
            $this->log = new WC_Logger();
        }

        // import actions
        require_once( 'hooks.php' );

    }

    /**
     * retrieve payscrow charges from API
     *
     * @return string
     */
    private function getGatewayCharges()
    {
        if ( function_exists( 'curl_init' ) )
        {

            $curl = curl_init();
            curl_setopt_array(
                $curl, [
                         CURLOPT_URL => "https://payscrow.net/api/charges",
                         CURLOPT_RETURNTRANSFER => true,
                         CURLOPT_FOLLOWLOCATION => 1,
                         CURLOPT_HTTPHEADER => [
                             'Content-Type: application/json',
                             'Accept: application/json'
                         ],
                         CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9'
                     ]
            );

            $result = curl_exec( $curl );

            if ( $errno = curl_errno( $curl ) )
            {
                $error_message = curl_strerror( $errno );
                $error = "cURL error ({$errno}):\n {$error_message}";
            }
            curl_close( $curl );

        }
        else
        {
            $result = file_get_contents( 'https://payscrow.net/api/charges' );
        }
        if ( $result )
        {
            $result = json_decode( $result, true );
            $currency = isset( $result ) && $result[ 'currency' ] == 'NGN'
                ? html_entity_decode( "&#8358;" )
                : $result[ 'currency' ];
            $fee = "An Escrow Fee of {$currency}{$result['customerCharge']} will be applied";
        }
        else
        {
            $fee = "";
        }
        if ( isset( $error ) )
        {
            return $error;
        }

        return $fee;
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $charges = $this->getGatewayCharges();

        $label = <<<EOD
        <div id="payscrow_logo">
        <style>
.payscrow-body{
padding:10px 5px 10px;
position:relative;
top: 0;
width:100%;
background-color:#fff;
border:1px solid #f1efef
}


.payscrow-content p{
color: inherit;
line-height: 16px;
font-weight: 400;
padding-top:5px ;
font-size: 12px;
}
.payscrow-content p:nth-child(2){
color: #9a9a9a;
font-size: 10px;
line-height:inherit;
}
@media screen and (max-width: 600px){
.payscrow-body{
height: 110px
}
.payscrow-content{
float: left;
}
}
</style>
<div  class="payscrow-body">
<div class="payscrow-content">
<p>Secure your funds till items are delivered</p>
<p>{$charges}</p>
</div>
</div>
</div>
<!--<script>-->
<!--document.getElementById("payscrow_logo").previousSibling.remove();-->
<!--</script>-->
EOD;
        $this->form_fields = [
            'enabled' => [
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable PayScrow payment option', 'woocommerce' ),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __( 'Title', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'Pay with PayScrow', 'woocommerce' ),
                'desc_tip' => true,
            ],
            'show_payscrow_logo' => [
                'title' => __( 'PayScrow Logo', 'woocommerce' ),
                'type' => 'checkbox',
                'description' => __( 'This controls the visibility of PayScrow logo on checkout page.', 'woocommerce' ),
                'default' => 'yes',
                'desc_tip' => true,
                'label' => __( 'Show PayScrow Logo', 'woocommerce' ),
            ],
            'show_black_logo' => [
                'title' => __( 'PayScrow Black Logo', 'woocommerce' ),
                'type' => 'checkbox',
                'description' => __(
                    'This controls the color option used for the PayScrow logo on checkout page.', 'woocommerce'
                ),
                'default' => 'yes',
                'desc_tip' => true,
                'label' => __( 'Use Black PayScrow Logo', 'woocommerce' ),
            ],
            'description' => [
                'title' => __( 'Description', 'woocommerce' ),
                'type' => 'text',
                'desc_tip' => true,
                'description' => __(
                    'This controls the description which the user sees during checkout.', 'woocommerce'
                ),
                'default' => __( "$label", 'woocommerce' )
            ],
            'test_mode' => [
                'title' => __( 'payscrow sandbox', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable payscrow sandbox', 'woocommerce' ),
                'default' => 'no',
                'description' => sprintf( __( 'payscrow sandbox can be used to test payments', 'woocommerce' ) ),
            ],
            'debug' => [
                'title' => __( 'Debug Log', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging', 'woocommerce' ),
                'default' => 'false',
                'description' => sprintf(
                    __( 'Log payscrow events, such as requests, inside <code>%s</code>', 'woocommerce' ),
                    wc_get_log_file_path( 'payscrow' )
                )
            ],
            'advanced' => [
                'title' => __( 'Advanced options', 'woocommerce' ),
                'type' => 'title',
                'description' => '',
            ],
            'invoice_prefix' => [
                'title' => __( 'Order ID Prefix', 'woocommerce' ),
                'type' => 'text',
                'description' => __(
                    'Please enter a prefix for your Order IDs. If you use your PayScrow account for multiple stores ensure this prefix is unique as PayScrow will not allow orders with the same Order ID.',
                    'woocommerce'
                ),
                'default' => 'mystore-payscrow-',
                'desc_tip' => true,
            ],
            'merchant_id' => [
                'title' => __( 'Merchant Access Key', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'Get your Merchant Access Key from PayScrow.', 'woocommerce' ),
                'default' => '',
                'desc_tip' => true
            ],
            'delivery_duration' => [
                'title' => __( 'Max Delivery Duration', 'woocommerce' ),
                'type' => 'text',
                'description' => __(
                    'Specify the maximum period it takes to deliver an order in days.', 'woocommerce'
                ),
                'default' => '7',
                'desc_tip' => true
            ],
        ];
    }

    public function process_payment( $order_id )
    {
        $order = wc_get_order( $order_id );
        if ( 'yes' == $this->debug )
        {
            $this->log->add(
                'payscrow', 'Processing Payment for  ' . $order->get_order_number(
                          ) . '. Notify URL: ' . $this->notify_url
            );
        }

        // Return thankyou redirect
        return [
            'result' => 'success',
            // 'redirect' => $this->get_return_url( $order )
            'redirect' => $order->get_checkout_payment_url( $on_checkout = true )
            // add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(wc_get_page_id('pay'))))

        ];
    }

    /**
     * Check for payscrow Response
     */
    public function check_payscrow_response()
    {

        @ob_clean();
        global $woocommerce;


        $is_post = $_SERVER[ 'REQUEST_METHOD' ] === 'POST'
            ? true
            : false;
        $is_get = $_SERVER[ 'REQUEST_METHOD' ] === 'GET'
            ? true
            : false;


        if ( $is_post )
        {

            $payscrow_response = file_get_contents( 'php://input' );
            $params = json_decode( $payscrow_response, true );
            $response_object = $this->check_payscrow_return_is_valid( $params );


            if ( !$response_object )
            {
                // we typically do nothing here...
                echo "Confirmation Error";
                wp_die( "payscrow Request Failure", "payscrow", [ 'response' => 200 ] );

            }
            else
            {
                do_action( "valid-payscrow-standard-confirm-request", $response_object );
                return;
            }

        }
        if ( $is_get && $params = $_GET )
        {
            $ref = isset( $params[ 'ref' ] )
                ? $params[ 'ref' ]
                : null; // Generally sent by gateway
            $order_id = null;
            if ( is_numeric( $ref ) )
            {
                $order_id = (int) $ref;
            }
            elseif ( is_string( $ref ) )
            {
                //$invoice_prefix = strtolower( $this->invoice_prefix );
                $order_id = (int) str_replace( $this->invoice_prefix, '', $ref );
            }
            $order = wc_get_order( $order_id );
            if ( isset($params[ 'statusCode' ]) && $order->get_id() != "" )
            {

                switch ( $params[ 'statusCode' ] )
                {
                    case "00":
                    case "02":
//                        do_action( 'woocommerce_thankyou', $order_id );


                    if ( 'yes' == $this->debug )
                    {
                    $this->log->add(
                        'payscrow', 'Page redirected to success: '.$this->get_return_url($order)
                    );
                    }

                    wp_redirect($this->get_return_url($order));
                    exit;
                        break;
                    case "01":
                        break;
                    case "03":
                    default:
//                        do_action( 'woocommerce_thankyou', $order_id );

                    if ( 'yes' == $this->debug )
                    {
                    $this->log->add(
                        'payscrow', 'Page redirected to failed'
                    );
                    }

                    wp_redirect($this->get_return_url($order));
                    exit;
                        break;
                }
                return;
            }

            //	exit;
            else
            {
                $shop_url = wc_get_page_permalink( 'shop' );
                echo '<br>Click <a href = "' . $shop_url . '">here</a> to return to the shop.<br/>';
                if ( 'yes' == $this->debug )
                {
                    $this->log->add(
                        'payscrow', 'Aborting, statusCode or order ref doesnt exist in params supplied:' .var_export($params, true) . ' is unknown.'
                    );
                }
                exit;
            }
        }

        else
        {
            if ( 'yes' == $this->debug )
            {
                $this->log->add( 'payscrow', 'Aborting, request:' . $_SERVER[ 'REQUEST_METHOD' ] . ' is unknown.' );
            }

            $redirect = wc_get_page_permalink( 'shop' );
            if ( wp_safe_redirect( $redirect ) )
            {
                exit;
            }

        }

    }

    /**
     * Check payscrow response validity
     **/
    public function check_payscrow_return_is_valid( $params )
    {
        $ref = isset( $params[ 'ref' ] )
            ? $params[ 'ref' ]
            : null; // Generally sent by gateway

        if ( is_numeric( $ref ) )
        {
            $order_id = (int) $ref;
        }
        elseif ( is_string( $ref ) )
        {
            //$invoice_prefix = strtolower( $this->invoice_prefix );
            $order_id = (int) str_replace( $this->invoice_prefix, '', $ref );
        }

        //  lets validate the response is from payscrow
        if ( isset( $params[ 'transactionId' ] ) )
        {
            $gateway_url = "{$this->payscrow_address}/api/paymentconfirmation?transactionId={$params['transactionId']}";
            $result = $this->get_payscrow_response( $gateway_url );
            if ( 'yes' == $this->debug )
            {
                $this->log->add(
                    'payscrow',
                    'Checking PayScrow response for ref. #' . $ref . ' (order. # ' . $order_id . ')is valid via ' . $gateway_url . '...'
                );
                $this->log->add( 'payscrow', 'PayScrow Response: ' . print_r( $result, true ) );
            }
        }
        else
        {
            $result = false;
            $this->log->add(
                'payscrow', 'PayScrow made a post request with no transactionId: ' . print_r( $result, true )
            );
        }


        if ( !is_wp_error( $result ) )
        {
            if ( $result )
            {
                if ( ( isset( $params[ 'statusCode' ] ) && $result[ 'statusCode' ] ) && $params[ 'statusCode' ] == $result[ 'statusCode' ] )
                {
                    if ( 'yes' == $this->debug )
                    {
                        $this->log->add( 'payscrow', 'Received valid response from payscrow' );
                    }

                    return $result;

                }
                if ( 'yes' == $this->debug )
                {
                    $error_message = 'Security: PayScrow Illegal access attempt: %s Message: %s';
                    $this->log->add(
                        'payscrow', sprintf(
                        $error_message, 403, 'Response did not originate from PayScrow Webservice'
                    )
                    );
                }

            }


        }
        if ( is_wp_error( $result ) )
        {
            $this->log->add( 'payscrow', 'Error response: ' . $result->get_error_message() );
        }

        return false;
    }

    /**
     * @param array $response
     * process responses from PayScrow
     */

    public function process_payscrow_response( $response )
    {

        global $woocommerce;

        if ( isset( $response ) && !empty( $response ) )
        {

            // Backwards comp for IPN requests
            if ( is_numeric( $response[ 'ref' ] ) )
            {
                $order_id = (int) $response[ 'ref' ];
            }
            else
            {
                $order_id = (int) str_replace( $this->invoice_prefix, '', $response[ 'ref' ] );
            }

            // fetch order
            $order = wc_get_order( $order_id );

            if ( 'yes' == $this->debug )
            {
                $this->log->add( 'payscrow', 'Found order #' . $order->get_id() );
            }

            // Store PP Details
            if ( !empty( $transaction_ref ) )
            {
                update_post_meta( $order->get_id(), 'payscrow Txn. Ref', wc_clean( $response[ 'ref' ] ) );
            }
            if ( !empty( $amount ) )
            {
                update_post_meta( $order->get_id(), 'payscrow Txn. Amount', wc_clean( $response[ 'amountPaid' ] ) );
            }
            if ( !empty( $status ) )
            {
                update_post_meta(
                    $order->get_id(), 'payscrow Txn. Status', wc_clean( $response[ 'statusDescription' ] )
                );
            }
            if ( !empty( $status_code ) )
            {
                update_post_meta(
                    $order->get_id(), 'payscrow Txn. Status Code', wc_clean( $response[ 'statusCode' ] )
                );
            }
            if ( !empty( $order_id ) )
            {
                update_post_meta(
                    $order->get_id(), 'payscrow Txn. Transaction ID', wc_clean( $response[ 'transactionId' ] )
                );
            }

            // Validate amount
            if ( $order->get_total() > $response[ 'amountPaid' ] )
            {
                if ( 'yes' == $this->debug )
                {
                    $this->log->add(
                        'payscrow', 'Payment error: Amounts paid do not match (gross ' . $order->get_total() . ')'
                    );
                }
                $response[ 'statusCode' ] = '02';
                $response[ 'statusDescription' ] = sprintf(
                    'payscrow amounts paid do not match (gross %s).', $order->get_total()
                );
            }

            if ( isset( $response[ 'statusCode' ] ) )
            {
                $statusDescription = "Payscrow confirmed your order with ref {$order_id} as: {$response[ 'statusDescription' ]}";
                // Check order not already completed
                if ( ! $order->has_status( 'completed' ) )
                {
                    switch ( $response[ 'statusCode' ] )
                    {
                        case '00':
                            // Payment was successful, so update the order's state, send order email and move to the success page
                            if ( 'yes' == $this->debug )
                            {
                                $this->log->add( 'payscrow', 'Payment status: ' . $response[ 'statusDescription' ] );
                            }
                            $order->add_order_note( __( 'payscrow Payment Completed', 'woocommerce' ) );
                            $order->payment_complete( $order->get_transaction_id() );
                            $order->add_order_note( __( $statusDescription, 'woocommerce' ), 1 );
                            //	echo "Congratulations. Your payment was <strong style = 'color: green;'> Successful </strong> and your order is currently being processed.";
                            $woocommerce->cart->empty_cart();
                            if ( 'yes' == $this->debug )
                            {
                                $this->log->add( 'payscrow', 'Payment complete.' );
                            }
                            break;

                        case '01':
                            // refund
                            $order->add_order_note( __( $response[ 'statusDescription' ], 'woocommerce' ), 1 );
                            $order->update_status(
                                'refunded', sprintf(
                                              __( '%s: %s', 'woocommerce' ), $response[ 'statusCode' ],
                                              $response[ 'statusDescription' ]
                                          )
                            );
                          if ( 'yes' == $this->debug )
                            {
                                $this->log->add( 'payscrow', 'Refund requested.' );
                            }
                            break;

                        case '02' :
                            //pending or partially paid
                            $order->add_order_note( __( $response[ 'statusDescription' ], 'woocommerce' ), 1 );
                            $order->update_status(
                                'on-hold', sprintf(
                                             __( '%s: %s', 'woocommerce' ), $response[ 'statusCode' ],
                                             $response[ 'statusDescription' ]
                                         )
                            );
                            if ( 'yes' == $this->debug )
                            {
                                $this->log->add( 'payscrow', 'Payment pending or partially paid.' );
                            }
                            $woocommerce->cart->empty_cart();
                            break;

                        case '03' :
                        default:
                            $order->add_order_note( __( $response[ 'statusDescription' ], 'woocommerce' ), 1 );
                            $order->add_order_note(
                                __( 'payscrow Payment Failed - Transaction Declined', 'woocommerce' )
                            );
                            $order->update_status(
                                'failed', sprintf(
                                            __( 'Transaction Declined: %s.', 'woocommerce' ),
                                            strtolower( $response[ 'statusCode' ] )
                                        )
                            );
                        if ( 'yes' == $this->debug )
                        {
                            $this->log->add( 'payscrow', 'Payment failed.' );
                        }

                            break;

                    }
                    return true;
                }
            }

        }

        return false;
    }

    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    public function receipt_page( $order )
    {

        echo '<p>' . __(
                'Thank you for your order, please click the button below to pay with payscrow.', 'payscrow'
            ) . '</p>';

        echo $this->generate_payscrow_form( $order );

    }

    /**
     * get response confirmation from PayScrow
     */
    private function get_payscrow_response( $gatewayUrl )
    {
        if ( function_exists( 'curl_init' ) )
        {
            $curl = curl_init();
            curl_setopt_array(
                $curl, [
                         CURLOPT_URL => $gatewayUrl,
                         CURLOPT_RETURNTRANSFER => true,
                         CURLOPT_FOLLOWLOCATION => 1,
                         CURLOPT_HTTPHEADER => [
                             'Content-Type: application/json',
                             'Accept: application/json'
                         ],
                         CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9'
                     ]
            );

            $result = curl_exec( $curl );

            if ( $errno = curl_errno( $curl ) )
            {
                $error_message = curl_strerror( $errno );

                return "cURL error ({$errno}):\n {$error_message}";
            }

            curl_close( $curl );
        }
        else
        {
            $result = file_get_contents(
                $gatewayUrl
            );
        }

        if ( $result )
        {
            $result = json_decode( $result, true );
        }

        return $result;
    }


    /**
     * Generate the payscrow button link (POST method)
     *
     * @access public
     *
     * @param mixed $order_id
     *
     * @return string
     */
    public function generate_payscrow_form( $order_id )
    {
        global $woocommerce;

        $order = new WC_Order( $order_id );
//		$woocommerce->cart->empty_cart();

        $payscrow_args = $this->get_payscrow_args( $order );

        $payscrow_addr = $this->payscrow_address . "/customer/transactions/start";


        wc_enqueue_js(
            '
				jQuery("body").block({
						message: "<img src=\"' . esc_url(
                apply_filters(
                    'woocommerce_ajax_loader_url', $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif'
                )
            ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . __(
                'Thank you for your order. We are now redirecting you to CIPG to make payment.', 'payscrow'
            ) . '",
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						centerY: false,
						css: {
							top:			"20%",
							padding:        20,
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:		"32px"
						}
					});
				jQuery("#submit_payscrow_payment_form").click();
			'
        );

        $payscrow_args_array = [];
        foreach ( $payscrow_args as $key => $value )
        {
            $payscrow_args_array[] = '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }

        echo '<form id="payscrow_form" name="payscrow_form" action="' . $payscrow_addr . '" method="post" target="_top">' . implode(
                '', $payscrow_args_array
            ) . '<input type="submit" class="button-alt" id="submit_payscrow_payment_form" value="' . __(
                'Pay via PayScrow', 'payscrow'
            ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __(
                'Cancel order &amp; restore cart', 'payscrow'
            ) . '</a>
				</form>';

    }

    public function get_payscrow_args( $order )
    {
        require_once( 'util.php' );

        if ( 'yes' == $this->debug )
        {
            $this->log->add(
                'payscrow', 'Generating payment form for order ' . $order->get_order_number(
                          ) . '. Notify URL: ' . $this->notify_url
            );
        }

        // CIPG Args
        $payscrow_args = [
            'ResponseUrl' => $this->notify_url,
            'GrandTotal' => $order->get_total(),
            'ShippingAmount' => $order->get_shipping_total(),
            'TotalTax' => $order->get_total_tax(),
            'Ref' => $this->invoice_prefix . $order->id,
            'AccessKey' => 'yes' == $this->test_mode
                ? $this->test_merchant_id
                : $this->merchant_id,
            'DeliveryDurationInDays' => $this->delivery_duration,

        ];

        // Pass items - PayScrow  requires detail for items and respective tax total

        if ( sizeof( $order->get_items() ) > 0 )
        {

            $i = 0;
            foreach ( $order->get_items() as $item )
            {
                if ( $item[ 'qty' ] )
                {
                    $payscrow_args[ "Items{$i}.Name" ] = $item->get_name();
                    $payscrow_args[ "Items{$i}.Description" ] = $item->get_product()->get_short_description();
                    $payscrow_args[ "Items{$i}.Price" ] = $item->get_total();
                    $payscrow_args[ "Items{$i}.Quantity" ] = $item->get_quantity();
                    $payscrow_args[ "Items{$i}.Deliverable" ] = $item->get_product()->is_virtual();
                    $payscrow_args[ "Items{$i}.TaxAmount" ] = $item->get_total_tax();
                    $i++;
                }
            }
        }

        return $payscrow_args;
    }

}