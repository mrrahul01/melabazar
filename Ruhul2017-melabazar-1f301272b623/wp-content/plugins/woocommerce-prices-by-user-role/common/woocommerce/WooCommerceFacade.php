<?php

if (!interface_exists("IWooCommerce")) {
    require_once dirname(__FILE__).'/IWooCommerce.php';
}

class WooCommerceFacade implements IWooCommerce
{
    private static $_instance = null;

    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function __construct()
    {
         if (isset(self::$_instance)) {
            $message = 'Instance already defined ';
            $message .= 'use WooCommerceFacade::getInstance';
            throw new Exception($message);
        }
    } // end __construct
    
    public function getNumberOfDecimals()
    {
        return get_option('woocommerce_price_num_decimals');
    } // end getNumberOfDecimals
    
    public function getWooCommerceInstance()
    {
        if (!function_exists("WC")) {
            throw new Exception("Not Found WooCommerce Instance", 1);
        }
        
        return WC();
    } // end getWooComerceInstance
}
