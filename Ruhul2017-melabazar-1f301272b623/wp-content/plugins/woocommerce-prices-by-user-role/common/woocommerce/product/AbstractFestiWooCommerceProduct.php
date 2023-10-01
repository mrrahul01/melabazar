<?php

class AbstractFestiWooCommerceProduct
{
    protected $adapter;
    protected $productMinimalQuantity = 1;
    protected $minimalPricesCount = 1;
    
    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    } // end __construct
    
    public function onInit()
    {
    } //end onIni
    
    public function getMaxProductPice($product, $display)
    {
        throw new Exception("Undefined methode getMaxProductPice");
    } // end getMaxProductPice
    
    public function getMinProductPice($product, $display)
    {
        throw new Exception("Undefined methode getMinProductPice");
    } // end getMinProductPice
    
    public function getPriceRange($product)
    {
        return false;
    } // end getPriceRange

    public function getChildren($product)
    {
        return $product->get_children();
    } // end getChildren
    
    public function getUserPrice($product, $display)
    {
        if (!$display) {
            return $product->get_price();
        }
        
        $taxDisplayMode = $this->getTaxDisplayMode();
        
        if ($this->isPriceIncludeTax()) {
            return $product->get_price_including_tax();
        }
        
        return $product->get_price_excluding_tax();
    } // end getUserPrice
    
    protected function isPriceIncludeTax()
    {
        $taxDisplayMode = $this->getTaxDisplayMode();
        
        return $taxDisplayMode == 'incl';
    } // end isPriceIncludeTax
    
    protected function getTaxDisplayMode()
    {
        return get_option('woocommerce_tax_display_shop');
    } // end getTaxDisplayMode
    
    public function getRegularPrice($product, $display)
    {
        $price = $product->get_regular_price();
        
        if (!$display) {
            return $price;
        }
        
        $taxDisplayMode = $this->getTaxDisplayMode();
        $quantity = $this->productMinimalQuantity;
        
        if ($this->isPriceIncludeTax()) {
            return $product->get_price_including_tax($quantity, $price);
        }
        
        return $product->get_price_excluding_tax($quantity, $price);
    } // end getRegularPrice
    
    public function isAvaliableToDisplaySaleRange($product)
    {
        throw new Exception("Undefined methode isAvaliableToDisplaySaleRange");
    }
    
    public function getFormatedPriceForSaleRange($product, $userPrice)
    {
        throw new Exception("Undefined methode getFormatedPriceForSaleRange");
    } // end getFormatedPriceForSaleRange
}
