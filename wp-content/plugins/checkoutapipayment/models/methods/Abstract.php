<?php
	
abstract class models_methods_Abstract extends jigoshop_payment_gateway implements models_InterfacePayment
{
	public function __construct(){


	}

	public function getCode(){
        return $this->_code;
    }



	//public function admin_options(){}
	//public function init_form_fields(){}
	public function payment_fields(){}
	public function process_payment($order_id){}
    public function get_default_options(){}


	protected function _createCharge($config)
    {

        $Api = CheckoutApi_Api::getApi(array('mode'=>CHECKOUTAPI_MODE));

        return $Api->createCharge($config);
    }

    protected function _validateChrage($order,$respondCharge)
    {
        //CheckoutApi_Utility_Utilities::dump($respondCharge->printError()); die();


		if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())){

            $Api = CheckoutApi_Api::getApi(
                array('mode' 		=> CHECKOUTAPI_MODE,
                    'authorization' => CHECKOUTAPI_SECRET_KEY)
            );

            $chargeUpdated = $Api->updateTrackId($respondCharge,$order->id);

            $order->payment_complete( $respondCharge->getId() );

			$order->add_order_note( sprintf(__('Checkout.com Credit Card Payment Approved - ChargeID: %s with Response Code: %s', 'woocommerce'), 
				$respondCharge->getId(), $respondCharge->getResponseCode()));

            jigoshop_cart::empty_cart();
            return array(
                'result' 	=> 'success',
                'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('thanks'))))
            );
		}
		else {

			$order->add_order_note( sprintf(__('Checkout.com Credit Card Payment Declined - Error Code: %s, Decline Reason: %s', 'jigoshop'),
				$respondCharge->getErrorCode(), $respondCharge->getMessage()));

            $error_message = 'The transaction was declined. Please check your Payment Details / Card info';
            echo '<div class="jigoshop_error">'.$error_message.'</div>';

			return;
		}

    }

    protected function get_post( $name ) {
		if ( isset( $_POST[ $name ] ) ){
				return $_POST[ $name ];
			}
			return null;
	}

    protected function _captureConfig()
    {
        $to_return['postedParam'] = array (
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_CAPTURE,
            'autoCapTime' => CHECKOUTAPI_AUTOCAPTIME
        );

        return $to_return;
    }

    protected function _authorizeConfig()
    {
        $to_return['postedParam'] = array (
            'autoCapture' => CheckoutApi_Client_Constant::AUTOCAPUTURE_AUTH,
            'autoCapTime' => 0
        );

        return $to_return;
    }
}

