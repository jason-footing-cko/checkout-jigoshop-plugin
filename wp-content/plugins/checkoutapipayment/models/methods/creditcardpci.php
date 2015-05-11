<?php

 class models_methods_creditcardpci extends models_methods_Abstract{

 	protected $_code = 'creditcardpci';
     //var $has_fields;

 	public function __construct()
    {
 		$this ->id = 'checkoutapipayment';
 		$this->has_fields = true;
        $this->pci_enable = 'yes';
 		parent::__construct();
 	}

 	public function _initCode(){ }

    public function payment_fields()
    {
    ?>
        <table style="border:0 ">
            <!-- Credit card number -->
            <tr>
                <td style="border:0 ">
                    <label for="ccnum"><?php echo __( 'Credit Card number', 'jigoshop' ) ?></label>
                </td>
                <td style="border:0 " >
                    <input type="text" class="input-text" id="ccnum" name="ccnum" maxlength="16" style="height:auto;font-family: inherit;border: 1px solid #d3ced3;" />
                </td>
            </tr>

            <!-- Credit card expiration -->
            <tr>
                <td style="border:0 ">
                    <label for="cc-expire-month"><?php echo __( 'Expiration date', 'jigoshop') ?></label>
                </td>
                <td style="border:0 ">
                    <select name="expmonth" id="expmonth" class="jigoshop-select jigoshop-cc-month"
                            style="height:28px;border: 1px solid #d3ced3;-webkit-border-radius: 3px;font-family: inherit;">
                        <option value=""><?php _e( 'Month', 'jigoshop' ) ?></option><?php
                        $months = array();
                        for ( $i = 1; $i <= 12; $i ++ ) {
                        $timestamp = mktime( 0, 0, 0, $i, 1 );
                        $months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
                        }
                        foreach ( $months as $num => $name ) {
                            printf( '<option value="%u">%s</option>', $num, $name );
                        } ?>
                    </select>
                    <select name="expyear" id="expyear" class="jigoshop-select jigoshop-cc-year"
                            style="height:28px;border: 1px solid #d3ced3;-webkit-border-radius: 3px;font-family: inherit;">
                        <option value=""><?php _e( 'Year', 'jigoshop' ) ?></option><?php
                        $years = array();
                        for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
                            printf( '<option value="20%u">20%u</option>', $i, $i );
                        } ?>
                    </select>
                </td>
            </tr>

            <!-- CVV-->
            <tr>
                <td style="border:0">
                    <label for="cvv"><?php _e('Card security code', 'jigoshop') ?></label>
                </td>
                <td style="border:0">
                    <input type="text" class="input-text" id="cvv" name="cvv" maxlength="4"
                           style="width:55px;height:auto;font-family: inherit;border: 1px solid #d3ced3;"/>
                </td>
            </tr>
        </table>
    <?php
    }

 	public function process_payment($order_id)
    {
 		$order = new jigoshop_order($order_id);
		$grand_total = $order->order_total;
		$amount = $grand_total*100;
        $currencyCode = Jigoshop_Base::get_options()->get('jigoshop_currency');
		$config['authorization'] = CHECKOUTAPI_SECRET_KEY;
		$config['timeout'] = CHECKOUTAPI_TIMEOUT;
		$config['postedParam'] = array('email' =>$order->billing_email,
			'value'=> $amount,
			'currency' => $currencyCode,
			'description'=>"Order number::$order->id"
		);
		$extraConfig = array();
		if(CHECKOUTAPI_PAYMENTACTION == 'Capture'){
			$extraConfig = parent::_captureConfig();
		}
		else {
			$extraConfig= parent::_authorizeConfig();
		}

		$config['postedParam'] = array_merge($config['postedParam'],$extraConfig);

		$config['postedParam']['card'] = array(
			'name' => $order->billing_first_name .' '.$order->billing_last_name,
			'number' => (string)$this->get_post('ccnum'),
			'expiryMonth' => $this->get_post('expmonth'),
            'expiryYear' => (int)$this->get_post('expyear'),
            'cvv' => $this->get_post('cvv'),
		);

		$config['postedParam']['card']['billingdetails'] = array(
			'addressline1' => $order->billing_address_1,
			'addressline2' => $order->billing_address_2,
			'city'=>$order->billing_city,
			'country' => $order->billing_country,
			'phone' => array('number' => $order->billing_phone),
			'postcode' => $order->billing_postcode,
			'state'=>$order->billing_state
		);

		$config['postedParam']['shippingdetails'] = array(
			'addressline1' => $order->shipping_address_1,
			'addressline2' => $order->shipping_address_2,
			'city'=>$order->shipping_city,
			'country' => $order->shipping_country,
			'phone' => array('number' => $order->shipping_phone),
			'postcode' => $order->shipping_postcode,
			'state'=>$order->shipping_state
		);

		$respondCharge = parent::_createCharge($config);

		return parent::_validateChrage($order, $respondCharge, $order_id);

 	}

 }
