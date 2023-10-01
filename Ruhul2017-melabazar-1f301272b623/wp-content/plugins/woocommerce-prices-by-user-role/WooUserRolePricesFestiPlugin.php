<?php

class WooUserRolePricesFestiPlugin extends WpmlCompatibleFestiPlugin
{
    public $_languageDomain = PRICE_BY_ROLE_LANGUAGE_DOMAIN;
    protected $_optionsPrefix = PRICE_BY_ROLE_OPTIONS_PREFIX;
    public $_version = PRICE_BY_ROLE_VERSION;
    
    protected function onInit()
    {
        $this->addActionListener('plugins_loaded', 'onLanguagesInitAction');

        if ($this->_isWoocommercePluginNotActiveWhenFestiPluginActive()) {
            $this->addActionListener(
                'admin_notices',
                'onDisplayInfoAboutDisabledWoocommerceAction' 
            );
            
            return false;
        }
        
        $this->onInitCompatibilityManager();

        $this->oInitWpmlManager();
   
        $this->addActionListener('wp_loaded', 'onInitStringHelperAction');

        parent::onInit();
        
        if (defined('DOING_AJAX')) {
            $this->onBackendInit();
        }
    } // end onInit
    
    protected function onInitCompatibilityManager()
    {
        $fileName = 'CompatibilityManagerWooUserRolePrices.php';
        require_once $this->_pluginPath.'libs/'.$fileName;
        $pluginMainFile = $this->_pluginMainFile;
        $backend = new CompatibilityManagerWooUserRolePrices($pluginMainFile);
    } // end onInitCompatibilityManager
    
    protected function oInitWpmlManager()
    {
        new FestiWpmlManager(PRICE_BY_ROLE_WPML_KEY, $this->_pluginMainFile);
    } // end oInitWpmlManager
    
    public function onInitStringHelperAction()
    {
        StringManagerWooUserRolePrices::start();
    } // end onInitStringHelperAction
    
    public function onInstall()
    {
        if (!$this->_isWoocommercePluginActive()) {
            $message = 'WooCommerce not active or not installed.';
            $this->displayError($message);
            exit();
        } 

        $plugin = $this->onBackendInit();
        
        $plugin->onInstall();
    } // end onInstall
    
    public function onBackendInit()
    {
        $fileName = 'WooUserRolePricesBackendFestiPlugin.php';
        require_once $this->_pluginPath.$fileName;
        $pluginMainFile = $this->_pluginMainFile;
        $backend = new WooUserRolePricesBackendFestiPlugin($pluginMainFile);
        return $backend;
    } // end onBackendInit
    
    protected function onFrontendInit()
    {
        $fileName = 'WooUserRolePricesFrontendFestiPlugin.php';
        require_once $this->_pluginPath.$fileName;
        $pluginMainFile = $this->_pluginMainFile;
        $frontend = new WooUserRolePricesFrontendFestiPlugin($pluginMainFile);
        return $frontend;
    } // end onFrontendIn
    
    private function _isWoocommercePluginNotActiveWhenFestiPluginActive()
    {
        return $this->_isFestiPluginActive()
               && !$this->_isWoocommercePluginActive();
    } // end _isWoocommercePluginNotActiveWhenFestiPluginActive
    
    private function _isFestiPluginActive()
    {        
        return $this->isPluginActive('woocommerce-woocartpro/plugin.php'); 
    } // end _isFestiPluginActive
    
    private function _isWoocommercePluginActive()
    {        
        return $this->isPluginActive('woocommerce/woocommerce.php');
    } // end _isWoocommercePluginActive
    
    public function onLanguagesInitAction()
    {
        load_plugin_textdomain(
            $this->_languageDomain,
            false,
            $this->_pluginLanguagesPath
        );
    } // end onLanguagesInitAction
    
    public function getMetaOptions($id, $optionName)
    {
        $value = $this->getPostMeta($id, $optionName);
        
        if (!$value) {
            return false;
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        $value = json_decode($value, true);
        
        return $value;
    } // end getMetaOptions
    
    public function getActiveRoles()
    {
        $options = $this->getOptions('settings');
        
        if (!$this->_hasActiveRoleInOptions($options)) {
            return false;
        }

        $wordpressRoles = $this->getUserRoles();
        
        $diff = array_diff_key($wordpressRoles, $options['roles']);
        $roles = array_diff_key($wordpressRoles, $diff);
        
        return $roles;
    } // end getActiveRoles
    
    private function _hasActiveRoleInOptions($options)
    {
        return array_key_exists('roles', $options);
    } // end _hasActiveRoleInOptions
    
    public function getUserRoles()
    {
        if (!$this->_hasRolesInGlobals()) {
            return false;
        }
        
        $roles = $GLOBALS['wp_roles'];

        return $roles->roles; 
    } // getUserRoles
    
    private function _hasRolesInGlobals()
    {
        return array_key_exists('wp_roles', $GLOBALS);   
    } // end _hasWordpessPostTypeInGlobals
    
    public function onDisplayInfoAboutDisabledWoocommerceAction()
    {        
        $message = 'WooCommerce Prices By User Role: ';
        $message .= 'WooCommerce not active or not installed.';
        $this->displayError($message);
    } //end onDisplayInfoAboutDisabledWoocommerceAction
    
    public function getPostMeta($postId, $key, $single = true)
    {
        return get_post_meta($postId, $key, $single);
    } // end getPostMeta
}