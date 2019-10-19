<?php
/*
Plugin Name: Arkom - WooCommerce Gateway
Description: Extends WooCommerce by Adding the Arkom Gateway.
Version: 1.0
Author: Amjad Ata-Allah (ataallah.amjad@gmail.com)
License: 
*/

add_action('wp_enqueue_scripts', 'arkom_enqueue_scripts');
// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'spyr_arkom_init', 0 );
function spyr_arkom_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// If we made it this far, then include our Gateway Class
	include_once( 'woocommerce-arkom.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'spyr_add_arkom_gateway' );
	function spyr_add_arkom_gateway( $methods ) {
		$methods[] = 'SPYR_Arkom';
		return $methods;
	}
	
	
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'spyr_arkom_action_links' );
function spyr_arkom_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'spyr-arkom' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );	
}

		
add_shortcode( 'payment_page_iframe', 'payment_page_iframe_func' );

 function payment_page_iframe_func( $atts ){
 	if(!session_id()) {
		session_start();
	}
	$trans_id = $_SESSION['TransID'];
	$landing_url = $_SESSION['LandingPage'];
	
	
	ob_start();
		
	?>
	<iframe src="<?php echo  $landing_url . $trans_id ; ?>" width="100%" height="700px"  scrolling="yes" frameBorder="0" id="PaymentIframe"  onLoad="iframeChanged(this.contentWindow.location);">
		<p>Browser unable to load iFrame</p>
	</iframe>
	<?php
		return ob_get_clean();
	}


function done_payment(){
	global $woocommerce;
	//$order_id = $_GET['orderid'];
	//echo $order_id;
	//$customer_order = new WC_Order( $order_id );
	//var_dump($customer_order); die;
	
	if(!session_id()) {
			   session_start();
			}
	
	
	$TransID = $_SESSION['TransID'];
	$InvUrl = $_SESSION['InvUrl'];
	$TerminalNum = $_SESSION['TerminalNum'];
	$TerminalPassword = $_SESSION['TerminalPassword'];
	$ThankUrl = $_SESSION['ThankUrl'];
	
		
	$client = new SoapClient($InvUrl);

	$res = $client->MTS_Redirect_GetTransResult(
			array(
				'TerminalNum' => $TerminalNum,
				'TerminalPassword' => $TerminalPassword,
				'TransID' => $TransID
		 )
	);
	
	$trans_split = explode('#*', $res->TransResult);
	$order_id = $trans_split[1];
	$customer_order = new WC_Order( $order_id );
	
	
	//if((substr($res->TransResult, 0, 3) == '000' && substr($_SERVER["HTTP_REFERER"], 0, 26) == 'https://secure.arkom.co.il') || (substr($res->TransResult, 0, 3) == '000' && substr($_SERVER["HTTP_REFERER"], 0, 22) == 'https://cc.arkom.co.il')) {
	if(substr($res->TransResult, 0, 3) == '000' ) {
		
		$customer_order->add_order_note( __( 'Arkom payment completed.', 'spyr-arkom' ) );
		$customer_order->payment_complete();
		$woocommerce->cart->empty_cart();
		wp_redirect( $ThankUrl ); exit();

	} else {
		$_SESSION['payement_error'] = "Error";
		$customer_order->add_order_note( __( 'Arkom payment Failed.', 'spyr-arkom' ) );
		wc_add_notice( __('Error: Your payment did not go through, please chekout again using the correct credit card info.'), 'error' );
		wp_redirect( $woocommerce->cart->get_cart_url() ); exit();
	}
	
}
add_action( 'wp_ajax_done_payment', 'done_payment' );
add_action( 'wp_ajax_nopriv_done_payment', 'done_payment' );

function arkom_enqueue_scripts(){
if(!session_id()) {
			   session_start();
			}

//if($_SESSION['payement_error'] == "Error") {
if(isset($_SESSION['payement_error']) && $_SESSION['payement_error'] == "Error") {
	wc_add_notice( __('Notice: Your Last payment did not go through, please chekout again using the correct credit card info.'), 'notice' );
}
        wp_enqueue_script(
            'arkom-gateway-js',
            plugins_url('/js/script.js', __FILE__),
            array('jquery')
        );
}

