<?php

if ( ! class_exists( 'jigoshop_payment_gateway' ) ) {
    return;
};

abstract class models_Checkoutapi extends jigoshop_payment_gateway implements models_InterfacePayment
{
    protected $_code;
    protected $_methodType;
    protected $_methodInstance;

    public function __construct()
    {
        parent::__construct();

        $this->_init();
        $this->_setInstanceMethod();

        define("CHECKOUTAPI_SECRET_KEY", $this->secret_key);
        define("CHECKOUTAPI_PUBLIC_KEY", $this->public_key);
        define("CHECKOUTAPI_PAYMENTACTION", $this ->payment_action);
        define("CHECKOUTAPI_AUTOCAPTIME", $this->auto_capture );
        define("CHECKOUTAPI_TIMEOUT", $this->gateway_timeout);
        define("CHECKOUTAPI_ISPCI", $this->pci_enable);
        define("CHECKOUTAPI_MODE", $this->mode);
        add_action ( 'valid-checkoutapipayment-webhook' , array ( $this , 'valid_webhook' ) );

    }

    private function _init()
    {
        $options = Jigoshop_Base::get_options();
        $this->id = 'checkoutapipayment';
        $this->has_fields = false;
        $this->enabled = $options->get('jigoshop_checkoutapipayment_enabled');
        $this->title = $options->get('jigoshop_checkoutapipayment_title');
        $this->description = $options->get('jigoshop_checkoutapipayment_descriptions');
        $this->mode = $options->get('jigoshop_checkoutapipayment_mode');
        $this->secret_key = $options->get('jigoshop_checkoutapipayment_secret_key');
        $this->public_key = $options->get('jigoshop_checkoutapipayment_public_key');
        $this->local_payment = $options->get('jigoshop_checkoutapipayment_local_payment');
        $this->pci_enable = $options->get('jigoshop_checkoutapipayment_pci_enable');
        $this->payment_action = $options->get('jigoshop_checkoutapipayment_payment_action');
        $this->auto_capture = $options->get('jigoshop_checkoutapipayment_auto_capture');
        $this->gateway_timeout = $options->get('jigoshop_checkoutapipayment_gateway_timeout');
    }

    public function get_default_options()
    {
        $defaults = array();

        // Define the Section name for the Jigoshop_Options
        $defaults[] = array(
            'name' => sprintf(__('<img style="vertical-align:middle;margin-top:-4px;margin-left:10px;"
                            src="https://www.checkout.com/wp-content/uploads/2014/08/logo.png" alt="checkout.com">')),
            'type' => 'title',
            'desc' => __('- Checkout develops and operates our own payment gateway technology. No outsourcing, less risk <br>
                          - With PCI-Level 1 certification, we ensure the highest level of protection for merchants, consumers and data <br>
                          - Checkout technology supports many of the services we offer, including hosted payment pages, fraud
                            management systems, etc, helping you connect securely with your consumers ', 'jigoshop')
        );

        $defaults[] = array(
            'name' => __('Enable Checkout.com', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_enabled',
            'std' => 'yes',
            'type' => 'checkbox',
            'choices' => array(
                'no' => __('No', 'jigoshop'),
                'yes' => __('Yes', 'jigoshop')
            )
        );

        $defaults[] = array(
            'name' => __('Method Title', 'jigoshop'),
            'desc' => '',
            'tip' => __('This controls the title which the user sees during checkout.', 'jigoshop'),
            'id' => 'jigoshop_checkoutapipayment_title',
            'std' => __('Checkout.com', 'jigoshop'),
            'type' => 'midtext'
        );

        $defaults[] = array(
            'name' => __('Customer Message', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_descriptions',
            'std' => 'Secure Credit/Debit card payment with Checkout.com',
            'type' => 'midtext'
        );

        $defaults[] = array(
            'name' => __('Production mode', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_mode',
            'std' => '',
            'type' => 'select',
            'choices' => array(
                'test' => __('Test', 'jigoshop'),
                'preprod' => __('Preprod', 'jigoshop'),
                'live' => __('Live', 'jigoshop')
            )
        );

        $defaults[] = array(
            'name' => __('Secret Key', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_secret_key',
            'std' => '',
            'type' => 'midtext'
        );

        $defaults[] = array(
            'name' => __('Public Key', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_public_key',
            'std' => '',
            'type' => 'midtext'
        );

        $defaults[] = array(
            'name' => __('Local Payment Enable', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_local_payment',
            'std' => 'no',
            'type' => 'checkbox',
            'choices' => array(
                'no' => __('No', 'jigoshop'),
                'yes' => __('Yes', 'jigoshop')
            )
        );

        $defaults[] = array(
            'name' => __('PCI Enable', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_pci_enable',
            'std' => 'yes',
            'type' => 'select',
            'choices' => array(
                'yes' => __('Yes', 'jigoshop'),
                'no' => __('No', 'jigoshop'),
            )
        );

        $defaults[] = array(
            'name' => __('Payment Action', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_payment_action',
            'std' => '',
            'type' => 'select',
            'choices' => array(
                'authorization' => __('Authotize only', 'jigoshop'),
                'capture' => __('Authorize and Capture', 'jigoshop'),
            )
        );

        $defaults[] = array(
            'name' => __('Auto Cap Time', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_auto_capture',
            'std' => '0',
            'type' => 'midtext'
        );

        $defaults[] = array(
            'name' => __('Gateway Timeout', 'jigoshop'),
            'desc' => '',
            'tip' => '',
            'id' => 'jigoshop_checkoutapipayment_gateway_timeout',
            'std' => '0',
            'type' => 'midtext'
        );


        return $defaults;
    }

    public function payment_fields()
    {
        return $this->_methodInstance->payment_fields();
    }

    public function process_payment($order_id)
    {
        return $this->_methodInstance->process_payment($order_id);
    }

    protected function _setInstanceMethod()
    {
        $configType =  $this->pci_enable;

       // print_r($configType); die();

        if($configType) {
            switch ($configType) {
                case 'yes':
                    $this->_methodType = 'models_methods_creditcardpci';
                    break;
                case 'no':
                    $this->_methodType = 'models_methods_creditcard';
                    break;
                default:
                    $this->_methodType = 'models_methods_creditcard';
                    break;
            }
        } else {
            throw new Exception('Invalid method type');
            exit;
        }

        if(!$this->_methodInstance) {
            $this->_methodInstance =  models_FactoryInstance::getInstance( $this->_methodType );
        }

        return  $this->_methodInstance;

    }
}