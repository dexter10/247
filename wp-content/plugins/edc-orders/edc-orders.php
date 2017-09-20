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
			wp_register_style( 'edc_custom_css', plugin_dir_url( __FILE__ ) . 'css/edc-orders.css', array(), '1.0');
			wp_enqueue_style( 'edc_custom_css' );
		}
	}

	public function edc_send_order( $order_id ){

		require EDC_ORDER_PATH . 'config.php';

		// get order object and order details
		$order				= wc_get_order( $order_id );
		$order_data 		= $order->get_data();
		$customer_email 	= $order_data['billing']['email'];
		$customer_phone 	= $order_data['billing']['phone'];
		$customer_country	= '1'; // Nederland
		$packing_slip_id	= '2576'; // Hard-coded packing slip - live
		//$packing_slip_id	= '23'; // Hard-coded packing slip - test
		$shipping_method 	= $order->get_shipping_method();
		$shipping_info 		= '';

		// set the address fields
		$address_fields = array('country',
			'title',
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode');

		$address = array();

		if (is_array($address_fields)) {
			foreach($address_fields as $field){
				$address['shipping_'.$field] 	= get_post_meta( $order_id, '_shipping_'.$field, true );
			}
		}

		$shipping_house_info 	= $address['shipping_address_2'];
		$shipping_house_number 	= intval(preg_replace('/[^0-9]+/', '', $shipping_house_info), 10);
		$shipping_house_ext		= preg_replace('/[^a-zA-Z]/', '', $shipping_house_info);

		// Shipping type & processing date
		switch($shipping_method){
			case 'Zondag levering':
				$processing_date = date('Y-m-d', strtotime('next Sunday'));
				break;
			case 'Maandag levering':
				$processing_date = date('Y-m-d', strtotime('next Monday'));
				break;
		}
		
		if ($shipping_method != 'Gratis verzending') {
			$shipping_info = '
				<processing_date>'.$processing_date.'</processing_date>
				<carrier_service>'.$shipping_method.'</carrier_service>
				<carrier>PostNL</carrier>
			';
		}
			
		$customerDetails = '
			<email>'.$email.'</email>
			<apikey>'.$apikey.'</apikey>
			<output>advanced</output>
		';

		$receiver = '
			<name>'.$address['shipping_first_name'].' '.$address['shipping_last_name'].'</name>
			<street>'.$address['shipping_address_1'].'</street>
			<house_nr>'.$shipping_house_number.'</house_nr>
			<house_nr_ext>'.$shipping_house_ext.'</house_nr_ext>
			<postalcode>'.$address['shipping_postcode'].'</postalcode>
			<city>'.$address['shipping_city'].'</city>
			<country>'.$customer_country.'</country>
			<packing_slip_id>23</packing_slip_id>
			<extra_email>'.$customer_email.'</extra_email>
			<phone>'.$customer_phone.'</phone>'.
			$shipping_info
		;

		// Get product details -> articles
		$items 		= $order->get_items();
		$item_qty 	= array();
		$item_sku 	= array();
		$products 	= array();

		foreach( $items as $key => $item){
			$item_id = $item['product_id'];
			$product = new WC_Product($item_id);
			
			for ($i = 0; $i < $item['qty']; $i++) {
				$products[] = '
				<artnr>'.$product->get_sku().'</artnr>';
			}
		}

		$xml = '<?xml version="1.0"?>
				<orderdetails>
					<customerdetails>'.$customerDetails.'</customerdetails>
					<receiver>'.$receiver.'</receiver>
					<products>'.implode($products, "\n").'</products>
				</orderdetails>
			';

		// Check whether the config vars are all set
		if(empty($email)){
			die('Please enter your config vars');
		}

		// Check whether the cURL module has been installed
		if(!function_exists('curl_init')){
			die('You do not have the cURL functions installed! Ask your host for more info.');
		} else {

			// Send the XML request
			$postfields = 'data='.$xml;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$apiurl);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$postfields);
			$result = curl_exec($ch);

			if($ch === false || $result === false){
				die('There was a problem with the connection to EDC');
			} else {
				$json = json_decode($result,true);

				// Success
				if($json['result'] == 'OK'){
					print_r( 'Success - Result= ' . $result);
					// Test results - shown in theme -> woocommerce -> checkout -> thankyou.php
					// $file	= plugin_dir_path( __FILE__ ) . '/results/results.txt'; 
					// $open	= fopen( $file, "w+" ); 
					// $write	= fputs( $open, $result . '' . $xml ); 
					// fclose( $open );
				// Failure
				} else {
					print_r( 'Failure - Result= ' . $result);
				}
			}
		}
	}
}

$edc_order = new EDCOrder();