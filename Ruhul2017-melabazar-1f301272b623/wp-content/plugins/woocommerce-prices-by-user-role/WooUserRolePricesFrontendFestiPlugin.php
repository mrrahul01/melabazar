<?php
if (!class_exists("FestiWooProductAdpter")) {
    $path = '/common/woocommerce/product/FestiWooCommerceProduct.php';
    require_once dirname(__FILE__).$path;
}

if (!class_exists("WooCommerceFacade")) {
    $path = '/common/woocommerce/WooCommerceFacade.php';
    require_once dirname(__FILE__).$path;
}

if (!class_exists("WooCommerceCartFacade")) {
    $path = '/common/woocommerce/WooCommerceCartFacade.php';
    require_once dirname(__FILE__).$path;
}

class WooUserRolePricesFrontendFestiPlugin extends WooUserRolePricesFestiPlugin
{
    protected $settings = array();
    protected $userRole;
    protected $products;
    protected $eachProductId = 0;
    protected $removeLoopList = array();
    protected $textInsteadPrices;
    protected $mainProductOnPage = 0;
    private $_listOfProductsWithRolePrice = array();
    
    protected function onInit()
    {
        if (!$this->_isSesionStarted()) {
            session_start();
        }
        
        $this->addActionListener('woocommerce_init', 'onInitFiltersAction');
        
        $this->addActionListener('wp', 'onHiddenAndRemoveAction');
        
        $this->addActionListener('wp_print_styles', 'onInitCssAction');
        $this->addActionListener('wp_enqueue_scripts', 'onInitJsAction');
        
        $this->addFilterListener(
            'woocommerce_get_variation_prices_hash',
            'onAppendDataToVariationPriceHashGeneratorFilter',
            10,
            3
        );
    } // end onInit
    
    protected function getSettings()
    {
        if (!$this->settings) {
            $this->settings = $this->getOptions('settings');
        }
        
        if (!$this->settings) {
            throw new Exception('The settings can not be empty.');
        }
        
        return $this->settings;
    } // end getSettings
    
    public function onAppendDataToVariationPriceHashGeneratorFilter(
        $productData, $product, $display
    )
    {
        $roles = $this->getAllUserRoles();
        
        $value = PRICE_BY_ROLE_HASH_GENERATOR_VALUE_FOR_UNREGISTRED_USER;
        $data = (!$roles) ? array($value) : $roles;

        $productData[PRICE_BY_ROLE_HASH_GENERATOR_KEY] = $data;
        
        return $productData;
    } // end onAppendDataToVariationPriceHashGeneratorFilter
    
    protected function getProductsInstances()
    {
        return new FestiWooCommerceProduct($this);
    } // end getProductsInstances
    
    public function onInitFiltersAction()
    {        
        $this->userRole = $this->getUserRole();
        
        $this->products = $this->getProductsInstances();
        
        $this->addActionListener('wp', 'onInitMainProductIdAction');
        
        if ($this->_hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            $this->onFilterPriceByDiscountOrMarkup();   
        } else {
            $this->onFilterPriceByRolePrice();
        }

        $this->onDisplayCustomerSavings();

        $this->onFilterPriceRanges();
    } // end onInitFiltersAction
    
    public function onHiddenAndRemoveAction()
    {
        $this->onHideAddToCartButton();
        
        $this->onHidePrice();
    } // end onHiddenAndRemoveAction
    
    public function onInitMainProductIdAction()
    {
        $this->getMainProductId();
    } // end onInitMainProductIdAction
    
    protected function onFilterPriceRanges()
    {
         $this->addFilterListener(
            'woocommerce_variable_price_html',
            'onProductPriceRangeFilter',
            10,
            4
        );

        $this->addFilterListener(
            'woocommerce_get_variation_price',
            'onVariationPriceFilter',
            10,
            4
        );
        
        $this->addFilterListener(
            'woocommerce_variable_empty_price_html',
            'onProductPriceRangeFilter',
            10,
            4
        );
        
        $this->addFilterListener(
            'woocommerce_get_price_html_from_to',
            'onSalePriceToNewPriceTemplateFilter',
            10,
            4
        );
        
        $this->addFilterListener(
            'woocommerce_grouped_price_html',
            'onProductPriceRangeFilter',
            10,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_grouped_empty_price_html',
            'onProductPriceRangeFilter',
            10,
            2
        );
    } // end onFilterPriceRanges
    
    public function onSalePriceToNewPriceTemplateFilter(
        $price, $sale, $newPrice, $product
    )
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->products->isAvaliableToDisplaySaleRange($product)) {
            $price = $this->products->getFormatedPriceForSaleRange(
                $product,
                $newPrice
            );
        }
        
        return $price;
    } // end onSalePriceToNewPriceTemplateFilter

    public function onProductPriceRangeFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
        $priceRangeType = PRICE_BY_ROLE_MIN_PRICE_RANGE_TYPE;
        
        $from = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            true
        );
        
        $priceRangeType = PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE;
        $to = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            true
        );
        
        if (!$from && !$to) {
            return $price;
        }
        
        $from = $this->getFormattedPrice($from);
        $to = $this->getFormattedPrice($to);

        $displayPrice = $this->fetchProductPriceRange($from, $to);

        $price = $displayPrice.$product->get_price_suffix();
        
        return $price;
    } // end onProductPriceRangeFilter
    
    protected function fetchProductPriceRange($from, $to)
    {
        if ($from == $to) {
            $template = '%1$s';
        } else {
            $template = '%1$s&ndash;%2$s';
        }
        
        $content = _x($template, 'Price range: from-to', 'woocommerce');
        
        $content = sprintf($content, $from, $to);
        
        return $content;
    } // end fetchProductPriceRange
    
    protected function getMainProductId()
    {
        if ($this->mainProductOnPage) {
            return $this->mainProductOnPage;
        }
        
        if (!$this->isProductPage()) {
            return false;
        }
        
        $this->mainProductOnPage = get_the_ID();
        
        return $this->mainProductOnPage;
    } //end getMainProductId
    
    protected function onDisplayCustomerSavings()
    {
        if ($this->_isMarkupEnabledOrDiscountFromRolePrice()) {
            return false;
        }
        
        $this->products->onDisplayCustomerSavings();
        
        $this->addFilterListener(
            'woocommerce_cart_totals_order_total_html',
            'onDisplayCustomerTotalSavingsFilter',
            10,
            2
        );
    } // end onDisplayCustomerSavings
    
    private function _isMarkupEnabledOrDiscountFromRolePrice()
    {
        return !$this->_isDiscountTypeEnabled()
               && $this->_isRolePriceDiscountTypeEnabled();
    } // end _isMarkupEnabledOrDiscountFromRolePrice
    
    public function onDisplayCustomerTotalSavingsFilter($total)
    {
        if (!$this->_hasOptionInSettings('showCustomerSavings')
            || !$this->_isEnabledPageInCustomerSavingsOption('cartTotal')
            || !$this->_isRegisteredUser()) {
            return $total;
        }
        
        $cart = WooCommerceCartFacade::getInstance();

        $userTotal = $cart->getTotal();
        $retailTotal = $this->getRetailTotal();

        if (!$this->_isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)) {
            return $total;
        }

        $totalSavings = $this->getTotalSavings($retailTotal, $userTotal);

        $userTotal = $this->getFormattedPrice($userTotal);
        $retailTotal = $this->getFormattedPrice($retailTotal);

        $vars = array(
            'regularPrice' => $this->fetchPrice($retailTotal),
            'userPrice' => $this->fetchPrice($userTotal, 'user'),
            'userDiscount' => $this->fetchTotalSavings($totalSavings)
        );
        
        return $this->fetch('customer_total_savings_price.phtml', $vars);
    } // end onDisplayCustomerTotalSavingsFilter
    
    protected function getRetailTotal()
    {
        $retailSubTotal = $this->getRetailSubTotalWithTax();
        $shippingTotal = $this->getShippingTotalWithTax();
        $retailTotal = $retailSubTotal + $shippingTotal;
        return $retailTotal;
    } // end getRetailTotal
    
    protected function getShippingTotalWithTax()
    {
        $cart = WooCommerceCartFacade::getInstance();
        
        $shippingTotal = $cart->getShippingTotal();
        $shippingTaxTotal = $cart->getShippingTaxTotal();

        return $shippingTotal + $shippingTaxTotal;
    } // end getShippingTotalWithTax
    
    protected function getRetailSubTotalWithTax()
    {
        $cart = WooCommerceCartFacade::getInstance();
        
        $subtotal = $cart->getSubtotalExcludeTax();
        $taxTotal = $cart->getTaxTotal();
        $taxPersent = $this->getTaxTotalPersent($subtotal, $taxTotal);
        $retailSubTotal = $this->getRetailSubTotal();
        
        $retailSubTotalTax = $retailSubTotal / 100 * $taxPersent;
        
        $retailSubTotalWithTax = $retailSubTotal + $retailSubTotalTax;
        
        return $retailSubTotalWithTax;
    } // end getRetailSubTotalWithTax
    
    protected function getTaxTotalPersent($subtotal, $taxTotal)
    {
        $taxPersent = 100 * $taxTotal / $subtotal;
        return $taxPersent;
    } // end getTaxTotalPersent
    
    public function onVariationPriceFilter(
        $price, $product, $priceRangeType, $display
    )
    {
        $product = $this->getProductNewInstance($product);
        
        $userPrice = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            $display
        );
        
        if ($userPrice) {
            $price = $this->_getPriceWithFixedFloat($userPrice);
        }

        return $price;
    } // end onVariationPriceFilter
    
    public function getPriceByRangeType($product, $rangeType, $display)
    {
        if ($this->_isMaxPriceRangeType($rangeType)) {
            $price = $this->products->getMaxProductPice($product, $display);
        } else {
            $price = $this->products->getMinProductPice($product, $display);
        }
        
        return $price;
    } // end getPriceByRangeType
    
    private function _isMaxPriceRangeType($rangeType)
    {
        return $rangeType == PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE;
    } // end _isMaxPriceRangeType
    
    public function getRetailSubTotal()
    {
        $cart = WooCommerceCartFacade::getInstance();
        $products = $cart->getProducts();

        $total = 0;
        $displayMode = ($cart->isPricesIncludeTax()) ? true : false;

        foreach ($products as $key => $product) {
            if ($this->_isVariableProduct($product)) {
                $productId = $product['variation_id'];
            } else {
                $productId = $product['product_id'];
            }
            
            $productInstance = $this->createProductInstance($productId);
            $price = $this->products->getRegularPrice(
                $productInstance,
                $displayMode
            );
            
            $total += $price * $product['quantity'];
        }
        
        return $total;
    } // end getRetailSubTotal
    
    private function _isVariableProduct($product)
    {
        return array_key_exists('variation_id', $product)
               && !empty($product['variation_id']);
    } // end _isVariableProduct
    
    public function fetchTotalSavings($totalSavings)
    {
        $vars = array(
            'discount' => $totalSavings
        );

        return $this->fetch('discount.phtml', $vars);
    } // end fetchTotalSavings
    
    public function fetchPrice($price, $type = 'regular')
    {
        $vars = array(
            'price' => $price,
            'type'  => $type
        );
        
        return $this->fetch('price.phtml', $vars);
    } // end fetchRegularPrice
    
    protected function getTotalSavings($retailTotal, $userTotal)
    {        
        $savings = round(100 - ($userTotal/$retailTotal * 100), 2);
        
        return $savings;
    } // end getTotalSavings
    
    private function _isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)
    {
        return $retailTotal > $userTotal;
    } // end _isRetailTotalMoreThanUserTotal
    
    public function onDisplayCustomerSavingsFilter(
        $price, $product
    )
    {
        $product = $this->getProductNewInstance($product);
        
        $result = $this->_hasConditionsForDisplayCustomerSavingsInProduct(
            $product
        );

        if (!$result) {
            return $price;
        }
        
        $regularPrice = $this->products->getRegularPrice($product, true);

        $userPrice = $this->products->getUserPrice($product, true);
        
        $result = $this->_isAvaliablePricesToDisplayCustomerSavings(
            $regularPrice,
            $userPrice   
        );
        
        if (!$result) {
            return $price;
        }
        
        $userDiscount = $this->fetchUserDiscount(
            $regularPrice,
            $userPrice,
            $product
        );
        
        $regularPrice = $this->getFormattedPrice($regularPrice);
        $userPrice = $this->getFormattedPrice($userPrice);

        $vars = array(
            'regularPrice' => $this->fetchPrice($regularPrice),
            'userPrice'    => $this->fetchPrice($userPrice, 'user'),
            'userDiscount' => $userDiscount,
            'priceSuffix'  => $product->get_price_suffix()
        );
        
        return $this->fetch('customer_product_savings_price.phtml', $vars);
    } // end onDisplayPriceContentForSingleProductFilter
    
    private function _isAvaliablePricesToDisplayCustomerSavings(
        $regularPrice, $userPrice
    )
    {
        return $userPrice !== false && $userPrice < $regularPrice;
    } // end _isAvaliablePricesToDisplayCustomerSavings
    
    public function fetchUserDiscount($regularPrice, $userPrice, $product)
    {
        $discount = round(100 - ($userPrice/$regularPrice * 100), 2);
        $vars = array(
            'discount' => $discount
        );

        return $this->fetch('discount.phtml', $vars);
    } // end fetchRegularPrice
    
    protected function getFormattedPrice($price)
    {
        return wc_price($price);
    } // end getFormattedPrice
    
    private function _hasConditionsForDisplayCustomerSavingsInProduct(
        $product
    )
    {
        return $this->_hasOptionInSettings('showCustomerSavings')
               && $this->_isRegisteredUser()
               && $this->_isAllowedPageToDisplayCustomerSavings($product)
               && $this->_isAvaliableProductTypeToDispalySavings($product);
    } // end _hasConditionsForDisplayCustomerSavingsInProduct
    
    private function _isAvaliableProductTypeToDispalySavings($product)
    {
        $result =  $this->products->isAvaliableProductTypeToDispalySavings(
            $product
        );
        
        return $result;
    } // end _isAvaliableProductTypeToDispalySavings
    
    private function _isAllowedPageToDisplayCustomerSavings($product)
    {
        $isEnabledProductPage = $this->_isEnabledPageInCustomerSavingsOption(
            'product'
        );
        
        $isEnabledArchivePage = $this->_isEnabledPageInCustomerSavingsOption(
            'archive'
        );
        
        $mainProduct = $this->_isMainProductInSimpleProductPage($product);
        
        $isProductPage = $this->isProductPage();
        
        if ($isProductPage && $isEnabledProductPage && $mainProduct) {
            return true;
        }

        if (!$isProductPage && $isEnabledArchivePage) {
            return true;
        }
        
        if ($this->_isProductParentMainproduct($product, $mainProduct)) {
            return true;
        }

        return false;
    } // end _isAllowedPageToDisplayCustomerSavings
    
    private function _isProductParentMainproduct($product)
    {
        if (!$product->post->post_parent) {
            return false;
        }

        return $product->post->post_parent == $this->mainProductOnPage;
    } // end _isProductParentMainproduct
    
    private function _isMainProductInSimpleProductPage($product)
    {
        return $product->id == $this->mainProductOnPage;
    } // end _isMainProductInSimpleProductPage
    
    private function _isEnabledPageInCustomerSavingsOption($page)
    {
        $settings = $this->getSettings();
        return in_array($page, $settings['showCustomerSavings']);
    } // end _isEnabledPageInCustomerSavingsOption
    
    protected function onHidePrice()
    {
        if (!$this->_hasAvailableRoleToViewPricesInAllProducts()) {
            $this->products->replaceAllPriceToText();
            $this->removeFilter(
                'woocommerce_get_price_html',
                'onDisplayCustomerSavingsFilter'
            );
        } else {
            $this->products->replaceAllPriceToTextInSomeProduct();
        }
    } // end onHidePrice
    
    protected function removeFilter($hook, $methodName, $priority = 10)
    {
        remove_filter($hook, array($this, $methodName), $priority);
    } // end removeFilter
    
    protected function onHideAddToCartButton()
    {
        if ($this->_isEnabledHideAddToCartButtonOptionInAllProducts()) {
            $this->removeAllAddToCartButtons();
        } else {
            $this->removeAddToCartButtonsInSomeProduct();
        }
    } // end onHideAddToCartButton
    
    protected function onFilterPriceByRolePrice()
    {
        $this->products->onFilterPriceByRolePrice();
    } // end onFilterPriceByRolePrice
    
    public function onDisplayPriceByRolePriceFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);

        if (!$this->_isRegisteredUser()) {
            return $price;
        }
        
        $this->userPrice = $price;

        if (!$this->_hasUserRoleInActivePLuginRoles()) {
            return $this->_getPriceWithFixedFloat($this->userPrice);
        }
        
        $newPrice = $this->getPrice($product);

        if ($newPrice) {
            $productId = $this->products->getProductId($product);
            $this->addIdToListOfPruductsWithRolePrice($productId);
            $this->userPrice = $newPrice;
            return $this->_getPriceWithFixedFloat($this->userPrice);
        }
        
        return $this->userPrice;
    } // end onDisplayPriceByRolePriceFilter
    
    protected function onFilterPriceByDiscountOrMarkup()
    {
        $this->products->onFilterPriceByDiscountOrMarkup();
    } // end onFilterPriceByDiscountOrMarkup
    
    public function onDisplayPriceByDiscountOrMarkupFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->_isRegisteredUser()) {
            return $price;
        }

        $this->userPrice = $price;
        
        $newPrice = $this->getPriceWithDiscountOrMarkUp($product);

        if ($newPrice) {
            $productId = $this->products->getProductId($product);
            $this->addIdToListOfPruductsWithRolePrice($productId);
            $this->userPrice = $this->_getPriceWithFixedFloat($newPrice);
        }

        return $this->userPrice;
    } // end onDisplayPriceByDiscountOrMarkupFilter
    
    protected function addIdToListOfPruductsWithRolePrice($productId)
    {
        if (in_array($productId, $this->_listOfProductsWithRolePrice)) {
            return false;
        }
        
        $this->_listOfProductsWithRolePrice[] = $productId;
    } // end addIdToListOfPruductsWithRolePrice
    
    public function getListOfPruductsWithRolePrice()
    {
        return $this->_listOfProductsWithRolePrice;
    } // end getListOfPruductsWithRolePrice
    
    private function _hasUserRoleInActivePLuginRoles()
    {
        $roles = $this->getAllUserRoles();
        
        if (!$roles) {
            return false;
        }
        
        $activeRoles = $this->getActiveRoles();

        if (!$activeRoles) {
            return false;
        }
        
        
        $result =  $this->_hasOneOfUserRolesInActivePLuginRoles(
            $activeRoles,
            $roles
        );
        
        return $result;
    } // end _hasUserRoleInActivePLuginRoles
    
    private function _hasOneOfUserRolesInActivePLuginRoles($activeRoles, $roles)
    {
        $result = false;

        foreach ($roles as $key => $role) {
            $result = array_key_exists($role, $activeRoles);
            
            if ($result) {
                return $result;
            }
        }
    } // end _hasOneOfUserRolesInActivePLuginRoles
    
    private function _getPriceWithFixedFloat($price)
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);
        return strval($price);
    } // end _getPriceWithFixedFloat
    
    public function getPriceWithDiscountOrMarkUp($product)
    {
        $amount = $this->getAmountOfDiscountOrMarkUp();
        $isNotRoleDiscountType = false;
        $price = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;
        
        if ($this->_isRolePriceDiscountTypeEnabled()) {
            $price = $this->getPrice($product);
            
            if (!$price) {
                $isNotRoleDiscountType = true;
            }
        }

        if (!$price) {
            $price = $this->products->getRegularPrice($product);
        }
        
        if ($isNotRoleDiscountType) {
            return $price;
        }
        
        if ($this->_isPercentDiscountType()) {
            $amount = $this->getAmountOfDiscountOrMarkUpInPercentage(
                $price,
                $amount
            );
        }

        if ($this->_isDiscountTypeEnabled()) {
            $minimalPrice = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;
            $newPrice = ($amount > $price) ? $minimalPrice : $price - $amount;
        } else {
            $newPrice = $price + $amount;
        }
        
        $wooFacade = WooCommerceFacade::getInstance();
        $numberOfDecimals = $wooFacade->getNumberOfDecimals();
        
        if (!$numberOfDecimals) {
            $newPrice = round($newPrice);
        }
                
        return $newPrice;
    } // end getPriceWithDiscountOrMarkUp
    
    public function getAmountOfDiscountOrMarkUpInPercentage($price, $discount)
    {
        $discount = $price / 100 * $discount;
        
        return $discount;
    } // end getAmountOfDiscountOrMarkUpInPercentage
    
    private function _isDiscountTypeEnabled()
    {
        $settings = $this->getSettings();
        return $settings['discountOrMakeUp'] == 'discount';
    } // end _isDiscountTypeEnabled
    
    private function _isPercentDiscountType()
    {
        $settings = $this->getSettings();
        $discountType = $settings['discountByRoles'][$this->userRole]['type'];
        return $discountType == PRICE_BY_ROLE_PERCENT_DISCOUNT_TYPE;
    } // end _isPercentDiscountType
    
    public function getPrice($product)
    {
        return $this->products->getRolePrice($product);
    } // end getPrices
    
    public function getRolePrice($id)
    {
        $roles = $this->getAllUserRoles();

        if (!$roles) {
            return false;
        }

        $priceList = $this->getMetaOptions($id, PRICE_BY_ROLE_PRICE_META_KEY);
        
        if (!$priceList) {
            return false;
        }
        
        $prices = $this->getAllRolesPrices($priceList, $roles);
        
        if (!$prices) {
            return false;
        }

        return min($prices);
    } // end getRolePrice
    
    protected function getAllRolesPrices($priceList, $roles)
    {
        $prices = array();

        foreach ($roles as $key => $role) {
            if (!$this->_hasRolePriceInProductOptions($priceList, $role)) {
                continue;
            }
            
            $prices[]= $this->_getPriceWithFixedFloat($priceList[$role]);
        }

        return $prices;
    } // end getAllRolesPrices
    
    private function _hasRolePriceInProductOptions($priceList, $role)
    {        
        return array_key_exists($role, $priceList) && $priceList[$role];
    } // end _hasRolePriceInProductOptions
    
    private function _isRolePriceDiscountTypeEnabled()
    {
        $settings = $this->getSettings();
        $userRole = $this->userRole;
        
        if (!$settings) {
            return false;
        }
        
        if (!isset($settings['discountByRoles'][$userRole]['priceType'])) {
            return false;
        }
        
        $priceType = $settings['discountByRoles'][$userRole]['priceType'];
        
        return $priceType == PRICE_BY_ROLE_DISCOUNT_TYPE_ROLE_PRICE;
    } // end _isRolePriceDiscountTypeEnabled
    
    public function getAmountOfDiscountOrMarkUp()
    {
        $settings = $this->getSettings();
        
        return $settings['discountByRoles'][$this->userRole]['value'];
    } // end getAmountOfDiscountOrMarkUp
    
    private function _hasDiscountOrMarkUpForUserRoleInGeneralOptions()
    {
        if (!$this->userRole) {
            return false;
        }
        
        $role = $this->userRole;
        $settings = $this->getSettings();

        return array_key_exists('discountByRoles', $settings)
               && array_key_exists($role, $settings['discountByRoles'])
               && $settings['discountByRoles'][$role]['value'] != false;
    } // end _hasDiscountOrMarkUpForUserRoleInGeneralOptions
    
    public function onReplaceAllPriceToTextInSomeProductFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            return $this->fetchContentInsteadOfPrices();
        }

        return $price;
    } // end onReplaceAllPriceToTextInSomeProductFilter
    
    protected function removeAddToCartButtonsInSomeProduct()
    {
        $this->products->removeLoopAddToCartLinksInSomeProducts();
        $this->removeAddToCartButtonInProductPage();
    } // end removeAddToCartButtonsInSomeProduct
    
    protected function removeAddToCartButtonInProductPage()
    {
        if (!$this->isProductPage()) {
            return false;
        }
        
        $productId = get_the_ID();
        $product = $this->createProductInstance($productId);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            $type = $product->product_type;     
            $this->products->removeAddToCartButton($type);
        }
    } // end removeAddToCartButtonInProductPage
    
    public function createProductInstance($productId)
    {
        $wooFactory = new WC_Product_Factory();
        $product = $wooFactory->get_product($productId);
        return $product;
    } // end createProductInstance
    
    protected function getProductNewInstance($product)
    {
        $productId = $this->getProductIdFromProductInstance($product);
        
        if (!$productId) {
            throw new Exception('Undefinde product Id');
        }

        return $this->createProductInstance($productId);
    } // end getProductNewInstance
    
    protected function getProductIdFromProductInstance($product)
    {
        if ($this->_hasVariationIdInProductInstance($product)) {
            $producId = $product->variation_id;
        } else {
            $producId = $product->id;
        }
        
        return $producId;
    } // end getProductIdFromProductInstance
    
    private function _hasVariationIdInProductInstance($product)
    {
        return isset($product->variation_id);
    } // end _hasVariationIdInProductInstance
    
    public function isProductPage()
    {
        return is_product();
    } // end isProductPage
    
    public function onRemoveAddToCartButtonInSomeProductsFilter(
        $button, $product
    )
    {
        $product = $this->getProductNewInstance($product);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            return '';
        }

        return $button;
    } // end onRemoveAddToCartButtonInSomeProductsFilter
    
    private function _hasAvailableRoleToViewPricesInProduct($product)
    {
        if ($this->_isChildProduct($product)) {
            $parentID = $product->post->post_parent;
            $product = $this->createProductInstance($parentID);
        }

        if (!$this->_isAvailablePriceInProductForUnregisteredUsers($product)) {
            $this->setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }

        if (!$this->_isAvailablePriceInProductForRegisteredUsers($product)) {
            $this->setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
        }
        
        return true;
    } // end _hasAvailableRoleToViewPricesInProduct
    
    private function _isChildProduct($product)
    {
        return isset($product->post->post_parent) 
               && $product->post->post_parent != false;
    } // end _isChildProduct
    
    private function _isAvailablePriceInProductForUnregisteredUsers($product)
    {
        return $this->_isRegisteredUser() || (!$this->_isRegisteredUser()
               && !$this->_hasOnlyRegisteredUsersInProductSettings($product));
    } // end _isAvailablePriceInProductForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInProductSettings($product)
    {
        $produtcId = $product->id;
        
        if (!$produtcId) {
            return false;
        }

        $options = $this->getMetaOptions(
            $produtcId,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
        
        if (!$options) {
            return false;
        }

        return array_key_exists(
            'onlyRegisteredUsers',
            $options
        );
    } // end _hasOnlyRegisteredUsersInProductSettings
    
    private function _isAvailablePriceInProductForRegisteredUsers($product)
    {
        return !$this->_isRegisteredUser() || ($this->_isRegisteredUser()
           && !$this->_hasHidePriceOptionForRoleInProductSettings($product));
    } // end _isAvailablePriceInProductForRegisteredUsers
    
    public function onReplaceAllPriceToTextInAllProductFilter()
    {
        return $this->fetchContentInsteadOfPrices();
    } //end onReplaceAllPriceToTextInAllProductFilter
    
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
    
    private function _hasAvailableRoleToViewPricesInAllProducts()
    {
        if (!$this->_isAvailablePriceInAllProductsForUnregisteredUsers()) {
            $this->setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }

        if (!$this->_isAvailablePriceInAllProductsForRegisteredUsers()) {
            $this->setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
        }

        return true;
    } // end _hasAvailableRoleToViewPricesInAllProducts
    
    private function _isAvailablePriceInAllProductsForRegisteredUsers()
    {
        return !$this->_isRegisteredUser() || ($this->_isRegisteredUser()
               && !$this->_hasHidePriceOptionForRoleInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForRegisteredUsers
    
    public function setValueForContentInsteadOfPrices($optionName)
    {
        $settings = $this->getSettings();
        
        $this->textInsteadPrices = $settings[$optionName];
    } // end getContentInsteadOfPrices
    
    private function _isAvailablePriceInAllProductsForUnregisteredUsers()
    {
        return $this->_isRegisteredUser() || (!$this->_isRegisteredUser()
               && !$this->_hasOnlyRegisteredUsersInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInGeneralSettings()
    {
        $settings = $this->getSettings();
        return array_key_exists('onlyRegisteredUsers', $settings);
    } // end _hasOnlyRegisteredUsersInGeneralSettings
    
    public function removeAllAddToCartButtons()
    {
        $this->products->removeAllLoopAddToCartLinks();
        $this->products->removeAddToCartButton();
    } //end removeAllAddToCartButtons
    
    public function removeGroupedAddToCartLinkAction()
    {
        echo $this->fetch('hide_grouped_add_to_cart_buttons.phtml');
    } // end removeGroupedAddToCartLinkAction
    
    public function removeVariableAddToCartLinkAction()
    {
        echo $this->fetch('hide_variable_add_to_cart_buttons.phtml');
    } // end removeVariableAddToCartLinkAction
    
    public function onRemoveAllAddToCartButtonFilter($button, $product)
    {
        return '';
    } // end onRemoveAddToCartButtonFilter
    
    private function _isEnabledHideAddToCartButtonOptionInAllProducts()
    {
        return (!$this->_isRegisteredUser() 
                  && $this->_hasHideAddToCartButtonOptionInSettings())
               || ($this->_isRegisteredUser() 
                  && ($this->_hasHideAddToCartButtonOptionForUserRole()
                     || $this->_hasHidePriceOptionForRoleInGeneralSettings()));
    } // end _isEnabledHideAddToCartButtonOptionInAllProducts
    
    private function _hasHidePriceOptionForRoleInProductSettings($product)
    {
        $produtcId = $product->id;
        
        if (!$produtcId) {
            return false;
        }
        
        $options = $this->getMetaOptions(
            $produtcId,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
        
        if (!$options) {
            return false;
        }
        
        if (!array_key_exists('hidePriceForUserRoles', $options)) {
            return false;
        }

        return $options && array_key_exists(
            $this->userRole,
            $options['hidePriceForUserRoles']
        );
    } // end _hasHidePriceOptionForRoleInProductSettings
    
    private function _hasHidePriceOptionForRoleInGeneralSettings()
    {
        $settings = $this->getSettings();
        $role = $this->userRole;
           
        return array_key_exists('hidePriceForUserRoles', $settings)
               && array_key_exists($role, $settings['hidePriceForUserRoles']);
    } // end _hasHidePriceOptionForRoleInGeneralSettings
    
    private function _hasHideAddToCartButtonOptionForUserRole()
    {
        $key = 'hideAddToCartButtonForUserRoles';
        $settings = $this->getSettings();
        
        return array_key_exists($key, $settings)
               && array_key_exists($this->userRole, $settings[$key]);
    } //end _hasHideAddToCartButtonOptionForUserRole
    
    private function _hasHideAddToCartButtonOptionInSettings()
    {
        $settings = $this->getSettings();
        
        return array_key_exists('hideAddToCartButton', $settings);
    } //end _hasHideAddToCartButtonOptionInSettings
    
    private function _isRegisteredUser()
    {
        return $this->userRole;
    } // end _isRegisteredUser
    
    public function getUserRole()
    {
        $roles = $this->getAllUserRoles();

        return $roles[0];
    } // end getUserRole
    
    public function getAllUserRoles()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            return false;    
        }
        
        $userData = get_userdata($userId);

        return $userData->roles;
    } // end getAllUserRoles
    
    public function getUserId()
    {
        if (defined('DOING_AJAX') && $this->_hasUserIdInSessionArray()) {
            return $_SESSION['userIdForAjax'];
        }

        $userId = get_current_user_id();
        
        return $userId;
    } // end getUserId
    
    private function _hasUserIdInSessionArray()
    {
        return isset($_SESSION['userIdForAjax']);
    } // end _hasUserIdInSessionArray
    
    private function _isSesionStarted()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE;
            } else {
                return session_id() === '';
            }
        }
        return false;
    } // end _isSesionStarted

    public function getPluginTemplatePath($fileName)
    {
        return $this->_pluginTemplatePath.'frontend/'.$fileName;
    } // end getPluginTemplatePath
    
    public function getPluginJsUrl($fileName)
    {
        return $this->_pluginJsUrl.'frontend/'.$fileName;
    } // end getPluginJsUrl
    
    public function getPluginCssUrl($path) 
    {
        return $this->_pluginUrl.$path;
    } // end getPluginCssUrl
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-general',
            'general.js',
            'jquery',
            $this->_version
        );
    } // end onInitJsAction
    
    public function onInitCssAction()
    {
        $this->addActionListener(
            'wp_head',
            'appendCssToHeaderForCustomerSavingsCustomize'
        );

        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-styles',
            'static/styles/frontend/style.css',
            array(),
            $this->_version
        );
    } // end onInitCssAction
    
    public function appendCssToHeaderForCustomerSavingsCustomize()
    {
        if (!$this->_hasOptionInSettings('showCustomerSavings')) {
            return false;
        }
        
        $vars = array(
            'settings' => $this->getSettings(),
        );

        echo $this->fetch('customer_savings_customize_style.phtml', $vars);
    } // end appendCssToHeaderForPriceCustomize
    
    private function _hasOptionInSettings($option)
    {
        $settings = $this->getSettings();
        
        return array_key_exists($option, $settings);
    } // end _hasOptionInSettings
    
    public function isWoocommerceMultiLanguageActive()
    {
        $pluginPath = 'woocommerce-multilingual/wpml-woocommerce.php';
        
        return $this->isPluginActive($pluginPath);
    } // end isWoocommerceMultiLanguageActive
    
    public function onDisplayOnlyProductStockStatusAction()
    {
        echo $this->fetch('stock_status_for_simple_type_product.phtml');
    } // end onDisplayOnlyProductStockStatusAction
}