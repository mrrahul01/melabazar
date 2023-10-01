<?php
/**
 * Plugin Name: WooCommerce Prices By User Role
 * Plugin URI: http://festi.io/app/woocommerce-prices-by-user-role/
 * Description:  With this plugin  for WooCommerce  Products can be offered different prices for each customer group. Also you can do only product catalog without prices and show custom notification instead price.
 * Version: 2.20.3
 * Author: Festi 
 * Author URI: http://festi.io/
 * Copyright 2014  Festi  http://festi.io/
 */

require_once dirname(__FILE__).'/config.php';

if (!class_exists('WordpressDispatchFacade')) {
    require_once dirname(__FILE__).'/common/WordpressDispatchFacade.php';
}

if (!class_exists('WooCommerceCacheHelper')) {
    $path = dirname(__FILE__).'/common/woocommerce/WooCommerceCacheHelper.php';
    require_once $path;
}
 
if (!class_exists('FestiPlugin')) {
    require_once dirname(__FILE__).'/common/FestiPlugin.php';
}

if (!class_exists('WpmlCompatibleFestiPlugin')) {
    require_once dirname(__FILE__).'/common/WpmlCompatibleFestiPlugin.php';
}

if (!class_exists('FestiWpmlManager')) {
    require_once dirname(__FILE__).'/common/wpml/FestiWpmlManager.php';
}

if (!class_exists('StringManagerWooUserRolePrices')) {
    require_once dirname(__FILE__).'/StringManagerWooUserRolePrices.php';
}

if (!class_exists('WooUserRolePricesFestiPlugin')) {
    require_once dirname(__FILE__).'/WooUserRolePricesFestiPlugin.php';
}

$className = 'wooUserRolePricesFestiPlugin';
$GLOBALS[$className] = new WooUserRolePricesFestiPlugin(__FILE__);
