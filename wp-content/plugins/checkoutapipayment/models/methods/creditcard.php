<?php

class models_methods_creditcard extends models_methods_Abstract{

 	protected $_code = 'creditcard';

    public function __construct()
    {
         $this ->id = 'checkoutapipayment';
         $this->has_fields = true;
         $this->pci_enable = 'no';
         //parent::__construct();

        add_action ( 'jigoshop_checkout_order_review' , array ( $this , 'generatePaymentToken' ) );
        //add_action ( 'jigoshop_checkout_order_review' , array ( $this , 'setJsInit' ) );
    }

 	public function _initCode(){

 	}

    public function setJsInit()
    {
        ?>
    <?php
    }

 	public function payment_fields()
    {

        $amount = (int)(jigoshop_cart::$total)*100;
        $current_user = wp_get_current_user();
        $currencyCode = Jigoshop_Base::get_options()->get('jigoshop_currency');

        $email = "Email@youremail.com";
        $name = "Card holder name";
        if(isset($current_user->data)){
            $email = $current_user->user_email;
            $name = $current_user->user_nicename;

        }

        $paymentToken = $this->generatePaymentToken();

        ?>
        <div style="" class="widget-container">

            <input type="hidden" name="cko_cc_paymenToken" id="cko-cc-paymenToken" value="<?php echo $paymentToken?>">

            <script type="text/javascript">
                if(window.CKOConfig) {
                    CheckoutIntegration.render(window.CKOConfig);
                }else {
                    window.CKOConfig = {
                        debugMode: false,
                        renderMode: 2,
                        namespace: 'CheckoutIntegration',
                        publicKey: '<?php echo CHECKOUTAPI_PUBLIC_KEY ?>',
                        paymentToken: "<?php echo $paymentToken ?>",
                        value: '<?php echo $amount ?>',
                        currency: '<?php echo $currencyCode ?>',
                        customerEmail: '<?php echo $email ?>',
                        customerName: '<?php echo $name ?>',
                        paymentMode: 'card',
                        title: '<?php  ?>',
                        subtitle: '<?php echo __('Please enter your credit card details') ?>',
                        widgetContainerSelector: '.widget-container',
                        ready: function (event) {
                            (function($){
                                $('#place_order').bind('click.cko submit.cko',function(event){
                                    if( $('#payment_method_checkoutapipayment:checked').length) {
                                        jQuery('.input-required').trigger('change');
                                        event.preventDefault();
                                        if(!$('.jigoshop-invalid').length) {
                                            CheckoutIntegration.open();
                                        }else {
                                            jQuery(window).scrollTop(jQuery('.jigoshop-invalid').position().top)

                                        }
                                    }

                                });

                            })(jQuery);
                        },
                        cardCharged: function (event) {
                            document.getElementById('cko-cc-paymenToken').value = event.data.paymentToken;
                            jQuery('form.checkout').trigger('submit');
                        }

                    };
                }

                (function($){
                    $(function(){
                        $('[name^="payment_methods"]').click(function(){
                            var $_self = $(this);
                            if($_self.attr('id') == 'payment_method_checkoutapipayment' ) {
                                $('#place_order').bind('click.cko submit.cko',function(event){
                                    jQuery('.input-required').trigger('change');
                                    event.preventDefault();
                                    if(!$('.jigoshop-invalid').length) {
                                        CheckoutIntegration.open();
                                    }else {
                                        jQuery(window).scrollTop(jQuery('.jigoshop-invalid').position().top)

                                    }
                                });
                            }else {

                                $('#place_order').unbind('click.cko').unbind( 'submit.cko');
                            }
                        });
                    });
                })(jQuery);
            </script>
            <script src="https://www.checkout.com/cdn/js/checkout.js" async ></script>
        </div>
        <?php


 	}

    public function generatePaymentToken()
    {
         $config = array();
         $amountCents = (int)(jigoshop_cart::$total)*100;
         $currencyCode = Jigoshop_Base::get_options()->get('jigoshop_currency');

         $config['authorization'] = CHECKOUTAPI_SECRET_KEY;
         $config['mode'] = CHECKOUTAPI_MODE;
         $config['timeout'] = CHECKOUTAPI_TIMEOUT;

         if (CHECKOUTAPI_PAYMENTACTION == 'Capture') {
             $config = array_merge($config, $this->_captureConfig());
         }
         else {

             $config = array_merge($config, $this->_authorizeConfig());
         }

         $config['postedParam'] = array_merge($config['postedParam'], array(
             'value' => $amountCents,
             'currency' => $currencyCode
         ));

         $Api = CheckoutApi_Api::getApi(array('mode' => CHECKOUTAPI_MODE));
         $paymentTokenCharge = $Api->getPaymentToken($config);

        $paymentToken    =   '';

        if($paymentTokenCharge->isValid()){
            $paymentToken = $paymentTokenCharge->getId();
        }

        if(!$paymentToken) {

            $error_message = $paymentTokenCharge->getExceptionState()->getErrorMessage().
                ' ( '.$paymentTokenCharge->getEventId().')';
            echo '<div class="jigoshop_error">'.$error_message.'</div>';
        }


        return $paymentToken;

    }

 	public function process_payment($order_id)
    {
        $order = new jigoshop_order($order_id);
        $grand_total = $order->order_total;
        $config['authorization'] = CHECKOUTAPI_SECRET_KEY;
        $config['paymentToken'] = parent::get_post('cko_cc_paymenToken');
        $Api = CheckoutApi_Api::getApi(array('mode'=>CHECKOUTAPI_MODE));
        $respondCharge = $Api->verifyChargePaymentToken($config);

        return parent::_validateChrage($order, $respondCharge);
 	}

 }
