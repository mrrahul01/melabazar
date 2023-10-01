<?php

class FestiWooCommerceVariationProduct extends AbstractFestiWooCommerceProduct
{
    public function removeAddToCartButton()
    {
    } // end removeAddToCartButton
    
    public function getProductId($product)
    {
        $productId = $product->variation_id;
        
        if (!$this->adapter->isWoocommerceMultiLanguageActive()) {
            return $productId;
        }
        
        $originalProduct = $this->_getOriginalProduct($productId);
        
        if (!$originalProduct) {
            return $productId;
        }

        return $originalProduct->variation_id;
    } // end getProductId
    
    private function _getOriginalProduct($productId)
    {
        $originalId = $this->_getOriginalProductId($productId);
        
        if (!$originalId) {
            return false;
        }
        
        $product = new WC_Product_Variation($originalId);
        
        return $product;
    } // end _getOriginalProduct
    
    private function _getOriginalProductId($productId)
    {
        $originalId = $this->adapter->getPostMeta(
            $productId,
            '_wcml_duplicate_of_variation'
        );
        
        return $originalId;
    } // end _getOriginalProductId
    
    public function isAvaliableToDispalySavings($product)
    {
        return true;
    } // end isAvaliableToDispalySavings
}
