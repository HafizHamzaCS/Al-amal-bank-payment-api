<?php

/*
  Plugin Name: Al Amal Bank
  Plugin URI: https://github.com/HafizHamzaCS
  Description: Integrate Al Amal Bank payment geteway with woocommerce
  Author: HafizM.Hamza
  Author URL: https://github.com/HafizHamzaCS
  Version: 0.0.1

 */

add_filter('woocommerce_payment_gateways', 'alamalbank_add_gateway_class');
function alamalbank_add_gateway_class($gateways)
{
    $gateways[] = 'WC_AlAmalBankGateway'; // your class name is here
    return $gateways;
}
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'alamalbank_init_gateway_class');


function alamalbank_init_gateway_class()
{
    class WC_AlAmalBankGateway extends WC_Payment_Gateway
    {
        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {

            $this->id = 'alamalbank'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom Al Amal Bank form
            $this->method_title = 'Al Amal Bank Gateway';
            $this->method_description = 'Description of Al Amal Bank payment gateway'; // will be displayed on the options page

            // gateways can support subscriptions, refunds, saved payment methods,
   6322         // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Method with all the options fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->apiurl = "http://rem.alamalbank.com:7103/ws";
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');


            $this->agentID = $this->testmode ? $this->get_option('test_agentID') : $this->get_option('live_agentID');
            $this->userName = $this->testmode ? $this->get_option('test_userName') : $this->get_option('live_userName');
            $this->userPWD = $this->testmode ? $this->get_option('test_userPWD') : $this->get_option('live_userPWD');
            $this->agent_CAT = $this->testmode ? $this->get_option('test_agent_CAT') : $this->get_option('live_agent_CAT');
            $this->to_ACCOUNT_ID = $this->testmode ? $this->get_option('test_to_ACCOUNT_ID') : $this->get_option('live_to_ACCOUNT_ID');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
988
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
                    'label'       => 'Enable Al Amal Bank Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Al Amal Bank',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay with your Al Amal Bank via our super-cool payment gateway.',
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),

                'test_agentID' => array(
                    'title'       => 'Test Agent ID',
                    'type'        => 'text'
                ),
                'test_userName' => array(
                    'title'       => 'Test User Name',
                    'type'        => 'text'
                ),
                'test_userPWD' => array(
                    'title'       => 'Test User Password',
                    'type'        => 'text'
                ),
                'test_agent_CAT' => array(
                    'title'       => 'Test Customer Catigories',
                    'type'        => 'text'
                ),
                'test_to_ACCOUNT_ID' => array(
                    'title'       => 'Test Account ID',
                    'type'        => 'text'
                ),
                'live_agentID' => array(
                    'title'       => 'Live Agent ID',
                    'type'        => 'text'
                ),
                'live_userName' => array(
                    'title'       => 'Live User Name',
                    'type'        => 'text'
                ),
                'live_userPWD' => array(
                    'title'       => 'Live User Password',
                    'type'        => 'text'
                ),
                'live_agent_CAT' => array(
                    'title'       => 'Live Customer Catigories',
                    'type'        => 'text'
                ),
                'live_to_ACCOUNT_ID' => array(
                    'title'       => 'Test Account ID',
                    'type'        => 'text'
                ),
            );
        }
        /**
         * You will need it if you want your custom Al Amal Bank form, Step 4 is about it
         */
        public function payment_fields()
        {

            // ok, let's display some description before the payment form
            if ($this->description) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ($this->testmode) {
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
                <label>Al-Amal Bank Account Number <span class="required">*</span></label>
                <input name="p_FROM_ACCOUNT_ID" id="p_form_account_id" type="text" autocomplete="off" style="width: 100%;" required="required">
                <img src="<?php echo plugin_dir_url(__FILE__) ?>images/loader.gif" class="hma_confirm_account_ajax_loader">
                <span class="hma_confirm_account_msg"></span>
            </div>
            <div class="clear"></div>
            <div class="form-row form-row-wide form-row-p_conferm_code">
                <label>Confirm Code <span class="required">*</span></label>
                <input name="p_conferm_code" id="p_conferm_code" value="0" type="text" autocomplete="off" style="width: 100%;" required="required">
            </div>
            <div class="clear"></div>
<?php
            do_action('woocommerce_credit_card_form_end', $this->id);

            echo '<div class="clear"></div></fieldset>';
        }
        /*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom Al Amal Bank form
		 */
        public function payment_scripts()
        {
            wp_enqueue_script('alamal', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], wp_rand(), true);
            wp_localize_script('alamal', 'alamal', [
                'ajax_url' => admin_url('admin-ajax.php'),
            ]);
            wp_enqueue_style('alamal', plugin_dir_url(__FILE__) . 'css/style.css', [], wp_rand(), 'all');
        }

        /*
		 * We're processing the payments here, everything about it is in Step 5
		 */
        public function process_payment($order_id)
        {
            if (isset($_POST['p_conferm_code']) && $_POST['p_conferm_code'] == "") {
                wc_add_notice("Confirm code is required", 'error');
            }
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
            $generate_token = json_decode($this->generate_token());





            if ($generate_token->is_error == 'no') {

                $payment_query = [
                    'p_conferm_code' => $_POST['p_conferm_code'],
                    'amount' => $order->get_total(),
                    'p_FROM_ACCOUNT_ID' => $_POST['p_FROM_ACCOUNT_ID'],
                    'ref_id' => $order->get_id(),
                    'token' => $generate_token->code
                ];

                $payment = json_decode($this->hma_amb_pos_slt($payment_query));



                // it could be different depending on your payment processor
                if ($payment->is_error == "no") {
                    $body = json_decode($payment->body);
                    if ($body->Code !== "004") {
                        wc_add_notice($body->Message, 'error');   
                    } else {

                        update_post_meta($order_id, 'alamalbank_payment_response', $payment);
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
                    }
                } else {
                    wc_add_notice($payment->msg, 'error');
                    return;
                }
            } else if ($generate_token->is_error == 'yes') {

                wc_add_notice($generate_token->msg, 'error');
            }
        }
        function hma_amb_pos_slt($parameters)
        {
            $p_conferm_code = $parameters['p_conferm_code'];
            $headers = array(
                'Authorization' => 'Bearer ' . $parameters['token'],
            );
            $body = array(
                "agentID"   => $this->agentID,
                "userName"  => $this->userName,
                "userPWD"   => $this->userPWD,
                "agent_CAT" => $this->agent_CAT,
                "to_ACCOUNT_ID" => $this->to_ACCOUNT_ID,
                "amount"    => $parameters['amount'],
                "p_FROM_ACCOUNT_ID" => $parameters['p_FROM_ACCOUNT_ID'],
                "p_conferm_code" => $p_conferm_code,
                "ref_id" => $parameters['ref_id'],
            );
            $response =  wp_remote_post($this->apiurl . '/amb_pos_slt', array(
                "method" => "POST",
                "sslverify" => false,
                "headers" => $headers,
                "body" => $body,
            ));

            if (wp_remote_retrieve_response
            _code($response) == "200") {
                return json_encode([
                    'is_error' => 'no',
                    'body' => wp_remote_retrieve_body($response),
                ]);
            } else {
                $return = [
                    'is_error' => 'yes',
                    'msg' => 'Erro in Payment'
                ];
                return json_encode($return);
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

        function generate_token()
        {

            $headers = array();
            $body = array(
                "agentID" => $this->agentID,
                "userName" => $this->userName,
                "userPWD" => $this->userPWD,
            );
            $response =  wp_remote_post($this->apiurl . '/amp_pos_login', array(
                "method" => "POST",
                "sslverify" => false,
                "headers" => $headers,
                "body" => $body,
            ));
            if (wp_remote_retrieve_response_code($response) == "200") {
                return json_encode([
                    'is_error' => 'no',
                    'code' => json_decode(wp_remote_retrieve_body($response)),
                ]);
            } else {
                return json_encode([
                    'is_error' => 'yes',
                    'msg' => 'Token for Payment is not generated',
                ]);
            }
        }
    }
}
add_action('wp_ajax_nopriv_hma_account_id_action',  'hma_account_id_action');
add_action('wp_ajax_hma_account_id_action', 'hma_account_id_action');

function hma_account_id_action()
{

    $WC_AlAmalBankGateway = new WC_AlAmalBankGateway();
    $generate_token = json_decode($WC_AlAmalBankGateway->generate_token());
    $initial_payment_query = [
        'p_conferm_code' => 0,
        'amount' => WC()->cart->total,
        'p_FROM_ACCOUNT_ID' => $_POST['p_FROM_ACCOUNT_ID'],
        'ref_id' => '0',
        'token' => $generate_token->code
    ];
    $initial_payment = json_decode($WC_AlAmalBankGateway->hma_amb_pos_slt($initial_payment_query));
    $initial_payment_response_body = json_decode($initial_payment->body);
    $messege = explode(':', $initial_payment_response_body->Message);
    $return  = [
        'is_error' => 'no',
        'msg_code' => $messege[0],
        'msg' => $messege[1],
        'initial_payment_query' => $initial_payment_query,
    ];
    if ($return['msg'] == null) {
        $return['msg'] = $return['msg_code'];
    }
    wp_send_json($return);
    exit();
}
