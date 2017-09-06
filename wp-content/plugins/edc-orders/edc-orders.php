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

class EDCOrder {

    public function __construct() {
	   	add_action('woocommerce_payment_complete', array( $this, 'edc_send_order' ), 10, 1);
	    add_action( 'wp_enqueue_scripts', array( $this, 'edc_custom_js_enqueue' ) );
    }

	public function edc_custom_js_enqueue() {
				
        if ( is_checkout()) {
			wp_register_script( 'edc_custom_js', plugin_dir_url( __FILE__ ) . 'js/edc-custom.js', array( 'jquery' ) );
			wp_enqueue_script( 'edc_custom_js' );
		}
	}

	public function edc_send_order( $order_id ){
		
		require_once ( EDC_ORDER_PATH . 'config.php' );

		// get order object and order details
		$order 				= new WC_Order( $order_id ); 
		$customer_email 	= $order->billing_email;
		$customer_phone 	= $order->billing_phone;
		$shipping_type 		= $order->get_shipping_method();
		$shipping_cost 		= $order->get_total_shipping();

		// set the address fields
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
		
		if (is_array($address_fields)) {
			foreach($address_fields as $field){
				$address['billing_'.$field] 	= get_post_meta( $order_id, '_billing_'.$field, true );
				$address['shipping_'.$field] 	= get_post_meta( $order_id, '_shipping_'.$field, true );
			}
		}

		// Get custom field value (huisnummer) from Woocommerce Checkout Manager
		$shipping_address_house = get_post_meta( $order_id, '_shipping_address_house', true );

		$customerDetails = '
			<email>'.$email.'</email>
			<apikey>'.$apikey.'</apikey>
			<output>advanced</output>
		';

		$receiver = '
			<name>'.$address['shipping_first_name'].' '.$address['shipping_last_name'].'</name>
			<street>'.$address['shipping_address_1'].'</street>
			<house_nr>'.$shipping_address_house.'</house_nr>
			<postalcode>'.$address['shipping_postcode'].'</postalcode>
			<city>'.$address['shipping_city'].'</city>
			<country>Nederland</country>
			<extra_email>'.$customer_email.'</extra_email>
			<phone>'.$customer_phone.'</phone>
			<carrier>PostNL</carrier>
			<carrier_service>'.$shipping_type.'</carrier_service>
			<packing_slip_id>2576</packing_slip_id>
		';

		// get product details
		$items 		= $order->get_items();
		$item_qty 	= array();
		$item_sku 	= array();
		$products 	= array();

		foreach( $items as $key => $item){
			$item_qty[] = $item['qty'];
			$item_id 	= $item['product_id'];
			$product 	= new WC_Product($item_id);
			$item_sku[] = $product->get_sku();			
			
			for ($i = 0; $i < $item['qty']; $i++) {
				$products[] = '
					<artnr>'.$product->get_sku().'</artnr>';
			}
		}

		$xml = '
		<?xml version="1.0"?>
			<orderdetails>
				<customerdetails>'.$customerDetails.'</customerdetails>
				<receiver>'
				.$receiver.'</receiver>
				<products>'.implode($products, '').'
				</products>
			</orderdetails>
		';

		error_log( $xml );

		// Check whether the config vars are all set
		if(empty($email) || empty($password)){
			die('Please enter your config vars');
		}

		// Check whether the cURL module has been installed
		if(!function_exists('curl_init')){
			die('You do not have the cURL functions installed! Ask your host for more info.');
		} else {

			// Send the XML request
			$postfields = 'data='.$xml;
			$ch = curl_init($apiurl);
			curl_setopt($ch,CURLOPT_HEADER,0);
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postfields);
			$result = curl_exec($ch);
			curl_close($ch);

			if($ch === false || $result === false){
				die('There was a problem with the connection to EDC');
			} else {
				$json = json_decode($result,true);

				// Success
				if($json['result'] == 'OK'){

					echo '<pre>';
					echo 'The order was successful. The following output was received from EDC:'.PHP_EOL;
					print_r($json);
					echo '</pre>';

				// Failure
				} else {
					echo '<pre>';
					echo 'There was a problem with the order request. The following output was received from EDC:'.PHP_EOL;
					print_r($json);
					echo '</pre>';
				}
			}
		}	

	}

}

$edc_order = new EDCOrder();