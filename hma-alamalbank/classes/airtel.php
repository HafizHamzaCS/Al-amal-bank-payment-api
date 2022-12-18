<?php
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'airtel_add_gateway_class');
function airtel_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Airtel_Gateway'; // your class name is here
    return $gateways;
}
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'airtel_init_gateway_class');


function airtel_init_gateway_class()
{
    class WC_Airtel_Gateway extends WC_Payment_Gateway
    {
        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {

            $this->id = 'airtel'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom Airtel form
            $this->method_title = 'Airtel Gateway';
            $this->method_description = 'Description of Airtel payment gateway'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->client_id = $this->testmode ? $this->get_option('test_client_id') : $this->get_option('live_client_id');
            $this->client_secret = $this->testmode ? $this->get_option('test_client_secret') : $this->get_option('live_client_secret');
            $this->msisdn = $this->testmode ? $this->get_option('test_msisdn') : $this->get_option('live_msisdn');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
        }
        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Airtel Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Airtel',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your Airtel via our super-cool payment gateway.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),

                'test_msisdn' => array(
                    'title'       => 'Test msisdn',
                    'type'        => 'text'
                ),
                'test_client_id' => array(
                    'title'       => 'Test client_id',
                    'type'        => 'text'
                ),
                'test_client_secret' => array(
                    'title'       => 'Test client_secret',
                    'type'        => 'text'
                ),
                'live_msisdn' => array(
                    'title'       => 'Live msisdn',
                    'type'        => 'text'
                ),
                'live_client_id' => array(
                    'title'       => 'Live client_id',
                    'type'        => 'text'
                ),
                'live_client_secret' => array(
                    'title'       => 'Live client_secret',
                    'type'        => 'text'
                ),
            );
        }
        /**
         * You will need it if you want your custom Airtel form, Step 4 is about it
         */
        public function payment_fields()
        {

            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ($this->testmode) {
                    $this->description .= ' Use ' . $this->msisdn . ' as Mobile Number for testing';
                    $this->description  = trim($this->description);
                }
                // display the description with <p> tags etc.
                echo wpautop(wp_kses_post($this->description));
            }

            // I will echo() the form, but you can close PHP tags and print it directly in HTML
            echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

            // Add this action hook if you want your custom payment gateway to support it
            do_action('woocommerce_credit_card_form_start', $this->id);

            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc

?>
            <div class="form-row form-row-wide">
                <label>Mobile Number <span class="required">*</span></label>
                <input name="msisdn" type="text" autocomplete="off" style="width: 100%;" required="required">
            </div>
            <div class="clear"></div>';
<?php
            do_action('woocommerce_credit_card_form_end', $this->id);

            echo '<div class="clear"></div></fieldset>';
        }
        /*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom Airtel form
		 */
        public function payment_scripts()
        {

            // we need JavaScript to process a token only on cart/checkout pages, right?
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }

            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ('no' === $this->enabled) {
                return;
            }

            // no reason to enqueue JavaScript if API keys are not set
            if (empty($this->private_key) || empty($this->publishable_key)) {
                return;
            }

            // do not work with card detailes without SSL unless your website is in a test mode
            if (!$this->testmode && !is_ssl()) {
                return;
            }

            // let's suppose it is our payment processor JavaScript that allows to obtain a token
            // wp_enqueue_script('airtel_js', 'https://www.airtelpayments.com/api/token.js');

            // and this is our custom JS in your plugin directory that works with token.js
            wp_register_script('woocommerce_airtel', plugins_url('airtel.js', __FILE__), array('jquery', 'airtel_js'));

            // in most payment processors you have to use PUBLIC KEY to obtain a token
            wp_localize_script('woocommerce_airtel', 'airtel_params', array(
                'publishableKey' => $this->publishable_key
            ));

            wp_enqueue_script('woocommerce_airtel');
        }

        /*
		 * We're processing the payments here, everything about it is in Step 5
		 */
        public function process_payment($order_id)
        {

            global $woocommerce;

            // we need it to get any order detailes
            $order = wc_get_order($order_id);



            /*
              * Array with parameters for API interaction
             */
            $args = array();

            /*
             * Your API interaction could be built with wp_remote_post()
              */

            $response =  $this->airtel_payment([
                'header' => [
                    'X-Currency' => 'CFA',
                    'X-Country' => 'GA',
                ],
                'reference' => 'Payment for order : ' . $order_id,
                'subscriber' => [
                    'country' => 'GA',
                    'currency' => 'CFA',
                    'msisdn' => $_POST['msisdn'],
                ],
                'transaction' => [
                    'amount' => $order->get_total(),
                    'country' => 'GA',
                    'currency' => 'CFA',
                    'id' => $order_id,
                ],
            ]);

            $response = json_decode($response);




            // it could be different depending on your payment processor
            if ($response->status->success == true) {
                update_post_meta($order_id, 'airtel_payment_response', $response);
                // we received the payment
                $order->payment_complete();
                $order->reduce_order_stock();

                // some notes to customer (replace true with false to make it private)
                $order->add_order_note('Hey, your order is paid! Thank you!', true);

                // Empty cart
                $woocommerce->cart->empty_cart();

                // Redirect to the thank you page
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                wc_add_notice($response->status->message, 'error');
                return;
            }
        }

        /*
		 * In case you need a webhook, like PayPal IPN etc
		 */
        public function webhook()
        {

            $order = wc_get_order($_GET['id']);
            $order->payment_complete();
            $order->reduce_order_stock();

            update_option('webhook_debug', $_GET);
        }

        function generate_airtel_token()
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://openapiuat.airtel.africa/auth/oauth2/token?client_id=' . $this->client_id . '&client_secret=' . $this->client_secret . '&grant_type=client_credentials',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        }
        function airtel_payment($parameter)
        {
            $token = json_decode($this->generate_airtel_token());
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://openapiuat.airtel.africa/merchant/v1/payments/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    "reference" => $parameter['reference'],
                    "subscriber" => array(
                        "country" => $parameter['subscriber']['country'],
                        "currency" => $parameter['subscriber']['currency'],
                        "msisdn" => $parameter['subscriber']['msisdn']
                    ),
                    'transaction' => array(
                        'amount' => $parameter['transaction']['amount'],
                        'country' => $parameter['transaction']['country'],
                        'currency' => $parameter['transaction']['currency'],
                        'id' => $parameter['transaction']['id'],
                    ),
                )),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $token->access_token,
                    'Content-Type: application/json',
                    'X-Country: ' . $parameter['header']['X-Country'],
                    'X-Currency: ' . $parameter['header']['X-Currency']
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        }
    }
}
