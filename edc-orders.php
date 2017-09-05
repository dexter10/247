<?php
/*
Plugin Name:	EDC Order
Description:	Send order to EDC after Woocommerce payment
Version:		0.1
License:		GPL
Author:			Jason Thane Jeffers
Author URI:		ordnung.nl
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! defined('EDC_ORDER_PATH'))
	define( 'EDC_ORDER_PATH', plugin_dir_path(__FILE__) );

if ( ! defined('EDC_ORDER_APP_VERSION'))
	define( 'EDC_ORDER_APP_VERSION', '1.0' );

//class edc_order {
//
//	public static function edc_order_payment_complete() {

		function edc_send_order_to_ext( $order_id ){
			// Configuration
			$email 	= 'testaccount@edc-internet.nl';
			$apikey = '7651320RK8RD972HR966Z40752DDKZKK';
			$apiurl = 'https://www.erotischegroothandel.nl/ao/';
			
			
			// get order object and order details
			$order = new WC_Order( $order_id ); 
			$customer_email = $order->billing_email;
			$phone = $order->billing_phone;
			$shipping_type = $order->get_shipping_method();
			$shipping_cost = $order->get_total_shipping();

			// set the address fields
			$user_id = $order->user_id;
			$address_fields = array('country',
				'title',
				'first_name',
				'last_name',
				'company',
				'address_1',
				'address_2',
				'address_3',
				'address_4',
				'city',
				'state',
				'postcode');

			$address = array();
			if(is_array($address_fields)){
				foreach($address_fields as $field){
					$address['billing_'.$field] = get_user_meta( $user_id, 'billing_'.$field, true );
					$address['shipping_'.$field] = get_user_meta( $user_id, 'shipping_'.$field, true );
				}
			}
			
			$house_number = get_post_meta( $order->id, ‘myfield1c’, true );

			// get coupon information (if applicable)
//			$cps = array();
//			$cps = $order->get_items( 'coupon' );
//
//			$coupon = array();
//			foreach($cps as $cp){
//					// get coupon titles (and additional details if accepted by the API)
//					$coupon[] = $cp['name'];
//			}

			// get product details
			$items = $order->get_items();

			//$item_name = array();
			$item_qty = array();
			//$item_price = array();
			$item_sku = array();

			foreach( $items as $key => $item){
//				$item_name[] = $item['name'];
//				$item_qty[] = $item['qty'];
//				$item_price[] = $item['line_total'];
//
//				$item_id = $item['product_id'];
				$product = new WC_Product($item_id);
				$item_sku[] = $product->get_sku();
			}

			//for online payments, send across the transaction ID/key. If the payment is handled offline, you could send across the order key instead
//			$transaction_key = get_post_meta( $order_id, '_transaction_id', true );
//			$transaction_key = empty($transaction_key) ? $_GET['key'] : $transaction_key;   
//
//			// set the username and password
//			$api_username = 'testuser';
//			$api_password = 'testpass';
//
//			// to test out the API, set $api_mode as ‘sandbox’
//			$api_mode = 'sandbox';
//			if($api_mode == 'sandbox'){
//				// sandbox URL example
//				$endpoint = "http://sandbox.example.com/"; 
//			}
//			else{
//				// production URL example
//				$endpoint = "http://example.com/"; 
//			}

			// setup the data which has to be sent
//			$data = array(
//					'apiuser' => $api_username,
//					'apipass' => $api_password,
//					'customer_email' => $email,
//					'customer_phone' => $phone,
//					'bill_firstname' => $address['billing_first_name'],
//					'bill_surname' => $address['billing_last_name'],
//					'bill_address1' => $address['billing_address_1'],
//					'bill_address2' => $address['billing_address_2'],
//					'bill_city' => $address['billing_city'],
//					'bill_state' => $address['billing_state'],
//					'bill_zip' => $address['billing_postcode'],
//					'ship_firstname' => $address['shipping_first_name'],
//					'ship_surname' => $address['shipping_last_name'],
//					'ship_address1' => $address['shipping_address_1'],
//					'ship_address2' => $address['shipping_address_2'],
//					'ship_city' => $address['shipping_city'],
//					'ship_state' => $address['shipping_state'],
//					'ship_zip' => $address['shipping_postcode'],
//					'shipping_type' => $shipping_type,
//					'shipping_cost' => $shipping_cost,
//					'item_sku' => implode(',', $item_sku), 
//					'item_price' => implode(',', $item_price), 
//					'quantity' => implode(',', $item_qty), 
//					'transaction_key' => $transaction_key,
//					'coupon_code' => implode( ",", $coupon )
//				);
			
			//print_r($data);
			
//			function test_print($value, $key) {
//				echo sprintf( "%s: %s\n" , $key , $value );
//			}
			
			//error_log(array_walk($data, 'test_print'));
			//error_log( "Payment has been received for order $order_id" );
			//error_log($data);


			
			
			
			$xml = '<?xml version="1.0"?>
			<orderdetails>
			<customerdetails>
				<email>'.$email.'</email>
				<apikey>'.$apikey.'</apikey>
				<output>advanced</output>
			</customerdetails>
			<receiver>
				<name>'.$address['shipping_first_name'].' '.$address['shipping_last_name'].'</name>
				<street>'.$address['shipping_address_1'].'</street>
				<house_nr>24</house_nr>
				<postalcode>'.$address['shipping_postcode'].'</postalcode>
				<city>'.$address['shipping_city'].'</city>
				<country>Nederland</country>
				<extra_email>'.$customer_email.'</extra_email>
				<phone>'.$phone.'</phone>
				<packing_slip_id>2576</packing_slip_id>
			</receiver>
			<products>
				<artnr>'.implode(',', $item_sku).'</artnr>
			</products>
			</orderdetails>';

			
			if (extension_loaded('simplexml')) {

				$loaded = 'loaded';

			}   
			
			error_log( $xml . $loaded );	


//			// Check whether the config vars are all set
//			if(empty($email) || empty($password)){
//				die('Please enter your config vars');
//			}
//
//			// Check whether the cURL module has been installed
//			if(!function_exists('curl_init')){
//				die('You do not have the cURL functions installed! Ask your host for more info.');
//			} else {
//
//				// Send the XML request
//				$postfields = 'data='.$xml;
//				$ch = curl_init($apiurl);
//				curl_setopt($ch,CURLOPT_HEADER,0);
//				curl_setopt($ch,CURLOPT_POST,1);
//				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
//				curl_setopt($ch,CURLOPT_POSTFIELDS,$postfields);
//				$result = curl_exec($ch);
//				curl_close($ch);
//
//				if($ch === false || $result === false){
//					die('There was a problem with the connection to EDC');
//				} else {
//					$json = json_decode($result,true);
//
//					// Success
//					if($json['result'] == 'OK'){
//
//						echo '<pre>';
//						echo 'The order was successful. The following output was received from EDC:'.PHP_EOL;
//						print_r($json);
//						echo '</pre>';
//
//					// Failure
//					} else {
//						echo '<pre>';
//						echo 'There was a problem with the order request. The following output was received from EDC:'.PHP_EOL;
//						print_r($json);
//						echo '</pre>';
//					}
//				}
//			}	

			
			
			
			
			
			
//			// send API request via cURL
//			$ch = curl_init();
//
//			/* set the complete URL, to process the order on the external system. Let’s consider http://example.com/buyitem.php is the URL, which invokes the API */
//			curl_setopt($ch, CURLOPT_URL, $endpoint."buyitem.php");
//			curl_setopt($ch, CURLOPT_POST, 1);
//			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
//			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//
//			$response = curl_exec ($ch);
//
//			curl_close ($ch);
//
//			// the handle response    
//			if (strpos($response,'ERROR') !== false) {
//					print_r($response);
//			} else {
//					// success
//			}
		}

		add_action('woocommerce_payment_complete', 'edc_send_order_to_ext', 10, 1); 

//	}
//}

//$edc_order = new edc_order();