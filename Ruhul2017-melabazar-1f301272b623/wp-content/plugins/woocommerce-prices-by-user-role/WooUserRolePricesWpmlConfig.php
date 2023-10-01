<?php

class WooUserRolePricesWpmlConfig
{
    protected $wpmlKey = PRICE_BY_ROLE_WPML_KEY;
    
    public function getWpmlKey()
    {
        return $this->wpmlKey;
    } // end getWpmlKey
    
    public function getTranslateList()
    {
        $list = array(
            PRICE_BY_ROLE_OPTIONS_PREFIX.'settings' => array(
                'textForUnregisterUsers',
                'textForRegisterUsers'
            ),
        );
        
        return $list;
    }
}
