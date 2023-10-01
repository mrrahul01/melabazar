<?php
	
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	
	/**
	 * Plugin Name: WooCommerce Walletmix Payment Gateway
	 * Plugin URI: https://epay.walletmix.com/modules/Woocommerce_WMX_Payment_Gateway_V_2.1.11.16.zip
	 * Description: Walletmix Payment Gateway for WooCommerce
	 * Version: 2.1.11.16
	 * Author: Walletmix Tech Team
	 * Author URI: https://walletmix.com/
	 * Developer: Walletmix Tech Team
	 * Developer URI: https://walletmix.com/
	 *
	 */
	 
	add_action( 'plugins_loaded', 'init_wc_walletmix' ); 
	
	add_action('init', 'wmxStartSession', 1);
	add_action('wp_logout', 'wmxEndSession',0);
	add_action('wp_login', 'wmxEndSession',0);
	
	FUNCTION wmxStartSession() {
	    if(!session_id()) {
	        session_start();
		}
	}
	
	FUNCTION wmxEndSession() {
	    session_destroy ();
	}
	
	FUNCTION init_wc_walletmix(){
		if(!class_exists('WC_Payment_Gateway'))
		return;
	
		class WC_Wall_Walletmix extends WC_Payment_Gateway {
			
			FUNCTION __construct(){
				$this->id = 'walletmix';
				$this->icon = plugins_url( '/assets/images/walletmix.png', __FILE__ );
				$this->has_fields = false;
				$this->order_button_text  = __( 'Proceed to Walletmix', 'woocommerce' );
				$this->method_title = __( 'Walletmix', 'woocommerce' );      
				$this->method_description = __( 'Walletmix payment gateway works by sending the user to Bank through Walletmix to enter their payment information.', 'woocommerce' );
				
				$this->description = __( 'Walletmix - secure online payment gateway. You can pay online using any visa, master, amex & mobile-banking.', 'woocommerce' );      
				
				$this->title = 'Visa, Master, Amex, Mobile Banking - Walletmix Gateway';
				
				$this->init_form_fields();
				$this->init_settings();
				
				//  Get values From admin
				$this->merchant_id = $this->settings['merchant_id'];     
				$this->wmx_username = $this->settings['wmx_username'];
				$this->wmx_password = $this->settings['wmx_password'];
				$this->wmx_app_key = $this->settings['wmx_app_key'];
				$this->wmx_app_name = $this->settings['wmx_app_name'];
				$this->default_order_status = $this->settings['default_order_status'];
				
				$getServerDetails = file_get_contents('https://epay.walletmix.com/check-server');
				$getServerDetails = json_decode($getServerDetails);
				
				$this->response_url  = $getServerDetails->url;
				$this->bank_payment_url = $getServerDetails->bank_payment_url;
				
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				
				add_action('woocommerce_receipt_'.$this->id, array($this, 'receipt_page'));
				
				add_action( 'woocommerce_api_'.strtolower(get_class($this)), array($this, 'check_walletmix_response' ) );
				
				
			}
			
			FUNCTION payment_fields(){
				if($this->description) 
				echo wpautop(wptexturize($this->description));
			}
			
			FUNCTION admin_options(){
				
				echo '<h3>'.__('Walletmix Payment Gateway', 'walletmix').'</h3>';
				echo '<p>'.__('Walletmix, Secure Online Payment Gateway.').'</p>';
				echo '<table class="form-table">';
				$this->generate_settings_html();
				echo '</table>';
			}
			
			FUNCTION init_form_fields(){
				
				$this->form_fields = array(
					'enabled' => array(
						'title' => __('Enable/Disable', 'walletmix'),
						'type' => 'checkbox',
						'label' => __('Enable Walletmix Payment Module.', 'walletmix'),
						'default' => 'no'
					),
					
					'merchant_id' => array(
						'title' => __('Merchant ID', 'walletmix'),
						'type' => 'text',
						'description' => __('Walletmix Merchant ID that is provided by walletmix support team.')
					),
					
					'wmx_username' => array(
						'title' => __('Walletmix API Access Username', 'walletmix'),
						'type' => 'text',
						'description' => __('Walletmix API Access Username that is provided by walletmix support team.')
					),
					
					'wmx_password' => array(
						'title' => __('Walletmix API Access Password', 'walletmix'),
						'type' => 'text',
						'description' => __('Walletmix API Access Password that is provided by walletmix support team.')
					),
					
					'wmx_app_key' => array(
						'title' => __('Walletmix App Key', 'walletmix'),
						'type' => 'text',
						'description' => __('Walletmix App Key that is provided by walletmix support team.')
					),
					
					'wmx_app_name' => array(
						'title' => __('Your Website/Application Name', 'walletmix'),
						'type' => 'text',
						'description' => __('Provided by walletmix support team.')
					),
					
					'default_order_status' => array(
						'title' => __('Default Order Status', 'walletmix'),
						'type' => 'select',
						'required'  => true,
						 'options' => array(

							  'pending'		=> __( 'Pending', 'woocommerce' ),

							  'processing'	=> __( 'Processing', 'woocommerce' ),

							  'on-hold'		=> __( 'On Hold', 'woocommerce' ),

							  'completed'	=> __( 'Completed', 'woocommerce' ),
							  
							  'cancelled'	=> __( 'Cancelled', 'woocommerce' ),
							  
							  'refunded'	=> __( 'Refunded', 'woocommerce' ),
							  
							  'failed'		=> __( 'Failed', 'woocommerce' ),

							),
						'description' => __('Walletmix Merchant ID that is provided by walletmix support team.')
					),
				);
			}
			
			FUNCTION receipt_page($order){
				echo '<p>'.__('Thank you for your order, please click the button below to pay with Walletmix.', 'walletmix').'</p>';
				echo 'here<br>';
				echo $this -> generate_walletmix_form($order);
				die();
			}
			
			FUNCTION generate_walletmix_form($order_id){
				
				global $woocommerce;
				
				$order = new WC_Order( $order_id );        
				
				$redirect_url = WC()->api_request_url(get_class($this));
				
				$currency = get_woocommerce_currency();	
				
				$products = "Order-$order_id"."_".date("Y-m-d h:i:s");
				$product_wtihquantity = ''; 
				$length = 0;
				$quantity = 0;
				
				$items = $order->get_items();
				
				foreach ( $items as $item ) {				
					$price = get_post_meta( (int)$item['product_id'], '_price', true);
					$t = $item['qty']*$price;
					$product_wtihquantity.='{'.$item['qty'] . 'x' . $item['name'] . '['.$price.']=['.$t.']}+';
					$quantity+=$item['qty'];
					$length++;				
				}
				
				$shippingTotal = $order->get_total_shipping() + $order->get_shipping_tax();
				$shipping = $shippingTotal > 0? round($shippingTotal,2):0;
				
				$couponTotal = $order->get_total_discount();
				$coupon = $couponTotal>0?$couponTotal:0;
				
				$product_wtihquantity.='{shipping rate:'.$shipping.'}-{coupon amount:'.$coupon.'}='.number_format( $order->get_total(), 2, '.', '' );
				
				$options = $this->get_options_value();
				
				$site_title = get_bloginfo( 'name' );
				$cart_info = $this->merchant_id.','.get_site_url().','.$order_id.':'.$site_title.','.$order->billing_email.','.$order_id;
				
				$cart_info_v2 = $this->merchant_id.','.get_site_url().','.$this->wmx_app_name; 
				
				$auth = $this->get_auth();
				
				$params = array(
					"wmx_id" => $this->merchant_id,
					"merchant_order_id" => $order_id,
					"merchant_ref_id" => uniqid(),
					"app_name" => $this->wmx_app_name,
					"cart_info" => $cart_info_v2,
					
					"customer_name" => $order->billing_first_name.' '.$order->billing_last_name,
					"customer_email" => $order->billing_email,
					"customer_add" => $order->billing_address_1,
					"customer_city" => $order->billing_city,
					"customer_country" => $order->billing_country,
					
					"customer_postcode" => $order->billing_postcode,
					"customer_phone" => $order->billing_phone,
					
					"shipping_name" => $order->shipping_first_name.' '.$order->shipping_last_name,
					"shipping_add" => $order->shipping_address_1,
					"shipping_city" => $order->shipping_city,
					"shipping_country" => $order->shipping_country,
					"shipping_postCode" => $order->shipping_postcode,
					
					"product_desc" => $product_wtihquantity,
					
					"amount" => $order->order_total,
					"currency" => $currency,
					"options" => $options,
					"callback_url" => $redirect_url ,
					"access_app_key" => $this->wmx_app_key,
					"authorization" => $auth,
				);
				
				$response = $this->curl_request($this->response_url,$params);
				
				$response_d = json_decode($response);
				$status_code = $response_d->statusCode;
				
				if($status_code === '1000'){
					$token = $response_d->token;
					$_SESSION['wmx_token'] = $token;
					$wmx_url = $this->bank_payment_url."/".$token;
					wp_redirect( $wmx_url );
					exit;
				}else{
					echo $response;
				}
			}
			
			FUNCTION curl_request($url,$params) {
				$postData = http_build_query($params);
				$ch = curl_init();
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch,CURLOPT_HEADER, false); 
				curl_setopt($ch,CURLOPT_POST, count($postData));
				curl_setopt($ch,CURLOPT_POSTFIELDS, $postData);
				$output = curl_exec($ch);
				curl_close($ch);
				return $output;
			}
			
			FUNCTION get_options_value() {
				$options  = base64_encode('s='.get_site_url().',i='.$_SERVER['SERVER_ADDR']);
				return $options;
			}
			
			FUNCTION get_cart_info() {
				$cart_info = $this->merchant_id.','.$_SERVER['HTTP_HOST'].','.$this->app_name;
				return $cart_info;
			}
			
			FUNCTION process_payment( $order_id ) {
				
				$order = new WC_Order( $order_id );
				
				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url( true )
				);
			}
			
			FUNCTION getSessionData($param){
				$value = '';
				if(isset($_SESSION[$param])){
					$value = $_SESSION[$param];
				}
					
				return $value;
			}
			
			FUNCTION get_auth() {
				$encodeValue = base64_encode($this->wmx_username.':'.$this->wmx_password);
				$auth = 'Basic '.$encodeValue;
				return $auth;
			}
			
			FUNCTION check_walletmix_response(){
				
				global $woocommerce;
					
				$recheck_url = "https://epay.walletmix.com/check-payment";
				
				$auth = $this->get_auth();
				
				$params = array(
					"wmx_id" => $this->merchant_id ,
					"authorization" => $auth ,
					"access_app_key" => $this->wmx_app_key,
				);
				
				$previous_token = $this->getSessionData('wmx_token');
				
				if(isset($_POST['merchant_txn_data'])){
					
					$posted =  wp_unslash($_POST['merchant_txn_data']);
					$merchant_txn_data = json_decode($posted);
					$token = $merchant_txn_data->token;
					$txn_status = $merchant_txn_data->txn_status;
					
					if(	$token === $previous_token) {
						$params['token'] = $token;
						$encodedResponse = $this->curl_request($recheck_url,$params);
						$response = json_decode($encodedResponse);
						
						$orderId = $response->merchant_order_id;
						$message = $response->txn_details;
						$merchat_currency = strtoupper($response->merchant_currency);
						
						$currency = strtoupper(get_woocommerce_currency());
						
						$order = new WC_Order( $orderId );

						$amount = $order->get_total();
						
						if(	($response->wmx_id == $params['wmx_id']) ){

							try{
								if( ($currency == $merchat_currency) &&	($response->txn_status == '1000') &&  ($response->merchant_req_amount >= $amount)){
									
									$order->update_status($this->default_order_status);
									
									$order->reduce_order_stock();
									
									$woocommerce->cart->empty_cart();
									
									$order->payment_complete();
									
									$order->add_order_note($message);
								
								}else{
									// failed order
									$order->update_status(Failed, __('Transaction Failed. Please Try Again. Thanks', 'woothemes'));
									
									// Remove cart
									$woocommerce->cart->empty_cart();
									
									$order->add_order_note($message);
								}
								wp_redirect( $this->get_return_url( $order ) );
								exit;
								
							}catch(Exception $e){
								wp_redirect( $this->get_return_url( $order ) );
								exit;
							}
						}else{
							echo 'Merchant ID Mismatch';
						}
					}else{
						echo 'Token Mismatch';
					}
				}else{
					echo 'Try to direct access';
				}
			}
		}
		
		FUNCTION add_wc_walletmix( $methods ) {
			$methods[] = 'WC_Wall_Walletmix'; 
			return $methods;
		}
		
		add_filter('woocommerce_payment_gateways', 'add_wc_walletmix' );
	}
	
	