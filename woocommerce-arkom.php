<?php
/* Arkom Payment Gateway Class */
class SPYR_Arkom extends WC_Payment_Gateway {

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "spyr_arkom";
		$this->order_button_text  = __( 'המשך לתשלום', 'spyr-arkom' );

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "Arkom", 'spyr-arkom' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "Arkom Payment Gateway Plug-in for WooCommerce", 'spyr-arkom' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( "Arkom", 'spyr-arkom' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = apply_filters('woocommerce_arkom_icon', plugins_url( 'assets/img/arkom-payment-method-cards.png' , __FILE__ ) );

		// Bool. Can be set to true if you want payment fields to show on the checkout 
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = true;//true

		// Supports the default credit card form
		//$this->supports = array( 'default_credit_card_form' );

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// Lets check for SSL
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
		/////////////////////
		
			
			
			$payment_page_id = null;
			$payment_page_id = get_page_by_title('Pay Via Arkom GateWay');
			
			if(!$payment_page_id || $payment_page_id->ID == 0) {
				global $user_ID;
				$new_post = array(
				'post_title' => 'Pay Via Arkom GateWay',
				'post_name'		=>	'arkom_payment_gateway_page',
				'post_content' => '[payment_page_iframe][/payment_page_iframe]',
				'post_status' => 'publish',
				'post_author' => $user_ID,
				'post_type' => 'page',
				'post_category' => array(0)
				);
				$payment_page_id = wp_insert_post($new_post);
			}
			

		////////////////////
		
		
		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // End __construct()

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'spyr-arkom' ),
				'label'		=> __( 'Enable this payment gateway', 'spyr-arkom' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'spyr-arkom' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'spyr-arkom' ),
				'default'	=> __( 'Credit card', 'spyr-arkom' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'spyr-arkom' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'spyr-arkom' ),
				'default'	=> __( 'Pay securely using your credit card - Your data will NEVER be saved.', 'spyr-arkom' ),
				'css'		=> 'max-width:350px;'
			),
			'api_login' => array(
				'title'		=> __( 'Prod - TerminalNUM', 'spyr-arkom' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the Prod TerminalNUM provided by Arkom when you signed up for an account.', 'spyr-arkom' ),
			),
			'trans_key' => array(
				'title'		=> __( 'Prod - Password', 'spyr-arkom' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'This is the Prod - Password provided by Arkom when you signed up for an account.', 'spyr-arkom' ),
			),
			'test_api_login' => array(
				'title'		=> __( 'Test - TerminalNUM', 'spyr-arkom' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the Test TerminalNUM provided by Arkom when you signed up for an account.', 'spyr-arkom' ),
			),
			'test_trans_key' => array(
				'title'		=> __( 'Test - Password', 'spyr-arkom' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'This is the Test - Password provided by Arkom when you signed up for an account.', 'spyr-arkom' ),
			),
			'environment' => array(
				'title'		=> __( 'Arkom Test Mode', 'spyr-arkom' ),
				'label'		=> __( 'Enable Test Mode', 'spyr-arkom' ),
				'type'		=> 'checkbox',
				'description' => __( 'Place the payment gateway in test mode.', 'spyr-arkom' ),
				'default'	=> 'no',
			),
			'iframe' => array(
				'title'		=> __( 'Use iframe', 'spyr-arkom' ),
				'label'		=> __( 'Enable Using iframe', 'spyr-arkom' ),
				'type'		=> 'checkbox',
				'description' => __( 'Enabling using the iframe instead of redirect to the full page.', 'spyr-arkom' ),
				'default'	=> 'no',
			),
			'landing_page_header1' => array(
				'title'		=> __( 'Landing Page Header 1', 'spyr-arkom' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the text that will appear in the header of the landing page - Only exists on a full page.', 'spyr-arkom' ),
				'default'	=> '&nbsp;',
			),
			'landing_page_header2' => array(
				'title'		=> __( 'Landing Page Header 2', 'spyr-arkom' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the text that will appear in the header of the landing page - Only exists on a full page.', 'spyr-arkom' ),
				'default'	=> '&nbsp;',
			),
			'landing_page_footer' => array(
				'title'		=> __( 'Landing Page Footer', 'spyr-arkom' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the text that will appear in the footer of the landing page - Only exists on a full page.', 'spyr-arkom' ),
				'default'	=> '&nbsp;',
				)
		);		
	}
	
	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;
		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );
		
		// Are we testing right now or is it a real transaction
		$environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
		$iframe = ( $this->iframe == "yes" ) ? 'TRUE' : 'FALSE';
		
		$cLocale = get_locale();
		$lang = '';
		if($cLocale) {
			$lang = strtoupper($cLocale[0]);//'E';
		}
		
		$iframe_lang = "";
		if($lang == "H") {
			$iframe_lang = "";
		} else {
			$iframe_lang = $lang;
		}
		
		if("FALSE" == $environment) { // this is production
			if("FALSE" == $iframe) { // this is full landing page
				$landing_url = 'https://cc.arkom.co.il/landpage/GetPayment/GetPayment.aspx?TransID=';
			} else {
				$landing_url = 'https://cc.arkom.co.il/landpage/GetPay' . $iframe_lang . '/GetPayment2.aspx?TransID=';
			}
			$environment_url = 'https://cc.arkom.co.il/MTS_WebService.asmx?wsdl';
			$trans_key = $this->trans_key;
			$api_login = $this->api_login;
		} else {
			if("FALSE" == $iframe) {
				$landing_url = 'https://secure.arkom.co.il/GetPayment/GetPayment.aspx?TransID=~';
			} else {
				$landing_url = 'https://secure.arkom.co.il/GetPaymentMini' . $iframe_lang . '/GetPayment2.aspx?TransID=~';
			}
			$environment_url = 'https://secure.arkom.co.il/wsdev/MTS_WebService.asmx?wsdl';
			$trans_key = $this->test_trans_key;
			$api_login = $this->test_api_login;
		}

		
		// This is where the fun stuff begins
		$payload = array(
			// Credentials and API Info
			"x_tran_key"           	=> $trans_key,
			"x_login"              	=> $api_login,
			"x_version"            	=> "3.1",
			
			// Order total
			"x_amount"             	=> $customer_order->order_total,
			
			// Credit Card Information
			"x_card_num"           	=> str_replace( array(' ', '-' ), '', $_POST['spyr_arkom-card-number'] ),
			"x_card_code"          	=> ( isset( $_POST['spyr_arkom-card-cvc'] ) ) ? $_POST['spyr_arkom-card-cvc'] : '',
			"x_exp_date"           	=> str_replace( array( '/', ' '), '', $_POST['spyr_arkom-card-expiry'] ),
			
			"x_type"               	=> 'AUTH_CAPTURE',
			"x_invoice_num"        	=> str_replace( "#", "", $customer_order->get_order_number() ),
			"x_test_request"       	=> $environment,
			"x_delim_char"         	=> '|',
			"x_encap_char"         	=> '',
			"x_delim_data"         	=> "TRUE",
			"x_relay_response"     	=> "FALSE",
			"x_method"             	=> "CC",
			
			// Billing Information
			"x_first_name"         	=> $customer_order->billing_first_name,
			"x_last_name"          	=> $customer_order->billing_last_name,
			"x_address"            	=> $customer_order->billing_address_1,
			"x_city"              	=> $customer_order->billing_city,
			"x_state"              	=> $customer_order->billing_state,
			"x_zip"                	=> $customer_order->billing_postcode,
			"x_country"            	=> $customer_order->billing_country,
			"x_phone"              	=> $customer_order->billing_phone,
			"x_email"              	=> $customer_order->billing_email,
			
			// Shipping Information
			"x_ship_to_first_name" 	=> $customer_order->shipping_first_name,
			"x_ship_to_last_name"  	=> $customer_order->shipping_last_name,
			"x_ship_to_company"    	=> $customer_order->shipping_company,
			"x_ship_to_address"    	=> $customer_order->shipping_address_1,
			"x_ship_to_city"       	=> $customer_order->shipping_city,
			"x_ship_to_country"    	=> $customer_order->shipping_country,
			"x_ship_to_state"      	=> $customer_order->shipping_state,
			"x_ship_to_zip"        	=> $customer_order->shipping_postcode,
			
			// Some Customer Information
			"x_cust_id"            	=> $customer_order->user_id,
			"x_customer_ip"        	=> $_SERVER['REMOTE_ADDR'],
			
		);
		
		
		
		
		$client = new SoapClient($environment_url);
		$curren = get_woocommerce_currency();
		if($curren == 'ILS') {
			$curren_code = '376';
		} else if($curren == 'USD') {
			$curren_code = '840';
		}
		$res = $client->MTS_Redirect_GetTransID(
				array(
					'TerminalNum' => $api_login,
					'TerminalPassword' => $trans_key,
					'TransSUM' => $customer_order->order_total,
					'TransTASH' => '0', // Num Payments
					'TransREF' => "#*" . $order_id . "#*",
					'TransCurrency' => $curren_code,//'376',  // 376 = NIS, 840 = $ //get_woocommerce_currency //ILS
					'CreditType' => '0',
					'CustomerEmail' => $customer_order->billing_email,
					'ReturnURL' => get_site_url() . '/wp-admin/admin-ajax.php?action=done_payment&orderid='.$order_id,
					'Header_1' => $this->landing_page_header1,
					'Header_2' => $this->landing_page_header2,
					'Footer_1' => $this->landing_page_footer,
					'Language' => $lang,
					'TransID' => ''
			 )
		);
		
		
	
		
		if ($res->TransID != "")
		{
			if(!session_id()) {
			   session_start();
			}
			
			

			$_SESSION['payement_error'] = "none";
			
			$_SESSION['TransID'] = $res->TransID;
			$_SESSION['InvUrl'] = $environment_url;
			$_SESSION['LandingPage'] = $landing_url;
			$_SESSION['TerminalNum'] = $api_login;
			$_SESSION['TerminalPassword'] = $trans_key;
			$_SESSION['ThankUrl'] = $this->get_return_url( $customer_order );
			
			
			if("FALSE" == $iframe) {
				$uri = $landing_url . $res->TransID;
			} else {
				$payment_page_id = get_page_by_title('Pay Via Arkom GateWay');
				$uri = get_page_uri($payment_page_id);
				
				$uri = home_url("/".$uri);
				
			} 
			return array(
				'result'   => 'success',
				//'redirect' => $landing_url . $res->TransID, 
				'redirect' => $uri
			);
		} else {
			wc_add_notice( __('Error Getting transaction ID, Please contact the administrator.'), 'error' );
			$customer_order->add_order_note( __('Error: Error Getting transaction ID.') );
		}
	
	

	}
	
	

	
	// Validate fields
	public function validate_fields() {
		return true;
	}
	
	
	// Check if we are forcing SSL on checkout pages
	// Custom function not required by the Gateway
	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}
	
	
	
	
	
	
	
    
	
	
	
	
	
	

} 