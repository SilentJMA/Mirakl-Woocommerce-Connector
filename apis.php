<?php
/**
 * Project Mirakl to Woocommerce.
 * User: Mohamed Ayoub Jabane <ayoub@ospeks.com>
 * Date: 15/11/2022
 * Time: 18:07
 */


function get_orders_from_api(){

    $base_api = 'https://xxx.mirakl.net';
    $api_key = '';//api key

	$timestamp = new DateTime();
	$timestamp->sub(new DateInterval('P5D')); // should be 'PT4H' => 4 hours
	$date = $timestamp->format('Y-m-d\TH:i:s\Z');
	$order_state_codes = 'SHIPPING'; // commat separated states


    $response = wp_remote_get( $base_api.'/api/orders/?api_key='.$api_key.'&order_state_codes='.$order_state_codes.'&start_date='.$date.'&max=50', []); //&order_state_codes=SHIPPING,CANCELED


    if ( is_array( $response ) && ! is_wp_error( $response ) ) {

        $body    = json_decode( $response['body'], false); // use the content

	    if(isset( $body->status )){
	    	echo $body->message;
	    	return false;
	    }

        $orders = $body->orders;

//        echo '<pre>';

        foreach ( $orders as $order ) {

//        	$data = [
//        		'order_id' => $order->order_id,
//        		'created_date' => $order->created_date,
//        		'order_state' => $order->order_state,
//        		'commercial_id' => $order->commercial_id,
//		        ];
//				var_dump( $data );

            $shipping_address = array(
                'first_name' => $order->customer->firstname,
                'last_name'  => $order->customer->lastname,
                'company'    => isset($order->customer->company) ? $order->customer->company : '',
                'email'      => $order->customer_notification_email, //$order->customer->customer_id ,
                'phone'      => '',
                'address_1'  => isset($order->customer->shipping_address->street_1) ? $order->customer->shipping_address->street_1 : '',
                'address_2'  => isset($order->customer->shipping_address->street_2) ? $order->customer->shipping_address->street_2 : '',
                'city'       => isset($order->customer->shipping_address->city) ? $order->customer->shipping_address->city : '',
                'state'      => isset($order->customer->shipping_address->state) ? $order->customer->shipping_address->state : '',
                'postcode'   => isset($order->customer->shipping_address->zip_code) ? $order->customer->shipping_address->zip_code : '',
                'country'    => isset($order->shipping_zone_code) ? $order->shipping_zone_code : '',
            );
            $billing_address = array(
                'first_name' => 'Manor',
                'last_name' => $order->order_id,
                'company' => 'Manor Market Place',
                'email' => 'test@test.com',
                'phone' => '777-777-777-777',
                'address_1' => 'Rebgasse 34',
                'address_2' => '',
                'city' => 'Basel',
                'state' => 'CH',
                'postcode' => '4058',
                'country' => 'CH'
            );
            $products = [];

            foreach ( $order->order_lines as $order_line ) {
//					var_dump( $order_line );
                $products[] = [
                    'id' =>  get_product_by_meta_sku( $order_line->product_sku), //$order_line->product_sku, //
                    'qty' => $order_line->quantity,
                ];
            }

            $params = [
                'shipping_address' => $shipping_address,
                'billing_address' => $billing_address,
                'products' => $products,
                'order_id' => $order->order_id,
            ];

            insert_order_to_wc($params);
        }

    }
}

function insert_order_to_wc($params){

	if(check_mirakl_order_id_exists($params['order_id'])){
		return false;
	}

//		$myProduct = new WC_Product(24568); //new wc_get_product(3078);

//		WC_Helper_Shipping::create_simple_flat_rate();
//		$shipping_taxes = WC_Tax::calc_shipping_tax('10', WC_Tax::get_shipping_tax_rates());
//		var_dump( wc_get_product_id_by_sku( 'FC-RHV001' ));

    $order = wc_create_order(['status' => 'on-hold', 'customer_id' => 1, 'customer_note' => '', 'total' => '']);

//		$order->add_shipping(new WC_Shipping_Rate('flat_rate_shipping', 'Flat rate shipping', '10', $shipping_taxes, 'flat_rate'));
//		$payment_gateways = WC()->payment_gateways->payment_gateways();
//		$order->set_payment_method($payment_gateways['bacs']);

    foreach ($params['products'] as $product){
        $product_id =  new WC_Product($product['id']);
        $product_qty = $product['qty'];
        $order->add_product( $product_id, $product_qty );
    }

//		$order->add_product( $myProduct, 1 ); //(get_product with id and next is for quantity)
    $order->set_address( $params['billing_address'], 'billing' );
    $order->set_address( $params['shipping_address'], 'shipping' );
    $order->set_payment_method('creditcard');
	$order->update_meta_data( 'mirakl_order_id', $params['order_id'] );
//		$order->add_coupon('Fresher','10','2'); // accepted param $couponcode, $couponamount,$coupon_tax
    $order->calculate_totals();
//		$order->save();
//		$order->update_status("pending", 'Imported order', TRUE);

    return $order;

}

function check_mirakl_order_id_exists($order_id){

	$orders = wc_get_orders( array(
		'limit'        => -1, // Query all orders
		'orderby'      => 'date',
		'order'        => 'DESC',
		'meta_key'     => 'mirakl_order_id', // The postmeta key field
		'meta_value'     => $order_id, // The postmeta key field
		'meta_compare' => '=', // The comparison argument
	));

	return count($orders) > 0;

}

function get_product_by_meta_sku($sku){
	$args = array (
		'post_type'  => 'product',
		'posts_per_page'  => -1,
		'meta_query' => array(
			array(
				'value' => $sku,
				'compare' => '='
			),
		),
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts()  ) {
		return $query->get_posts()[0]->ID;
	}

	return null;

}


// define the woocommerce_order_status_changed callback
	function action_woocommerce_order_status_changed( $this_get_id, $this_status_transition_from, $this_status_transition_to, $instance ) {
		// make action magic happen here...
		$mirakl_order_id = get_post_meta( $this_get_id, 'mirakl_order_id' , true);
		$dhl = get_post_meta( $this_get_id, '_pr_shipment_dhl_label_tracking' , true);
		update_order_tracking_number($mirakl_order_id, $dhl['tracking_number']);
		if($this_status_transition_to === "completed"){
			validate_shippment_of_order($mirakl_order_id);
		}
//		add_post_meta(931, 'test', 'status_changed' . $this_get_id . '--'. $this_status_transition_to.'-mid'. $mirakl_order_id);
	};

// add the action
	add_action( 'woocommerce_order_status_changed', 'action_woocommerce_order_status_changed', 10, 4 );


	function update_order_tracking_number($order_id, $traking_number){
		$base_api = 'https://manor6-dev.mirakl.net';
		$api_key = '';//api key

		$body = [
			'carrier_code' => "dhl",
			'carrier_name' => "DHL",
			'carrier_url' => " https://www.logistics.dhl",
			'tracking_number' => $traking_number,
		];

		$args = array(
			'headers' => array(
				'Content-Type'   => 'application/json',
			),
			'body'      => json_encode($body),
			'method'    => 'PUT'
		);

		$response = wp_remote_request( $base_api.'/api/orders/'.$order_id.'/tracking/?api_key='.$api_key.'', $args );

	}

	function validate_shippment_of_order($order_id){
		$base_api = 'https://manor6-dev.mirakl.net';
		$api_key = '';//api key

		$body = [
			'order_id' => $order_id,
		];

		$args = array(
			'headers' => array(
				'Content-Type'   => 'application/json',
			),
			'body'      => json_encode($body),
			'method'    => 'PUT'
		);

		$response = wp_remote_request( $base_api.'/api/orders/'.$order_id.'/ship/?api_key='.$api_key.'', $args );

	}
