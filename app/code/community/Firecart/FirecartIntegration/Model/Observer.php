<?php

class Firecart_FirecartIntegration_Helper_Data extends Mage_Core_Helper_Abstract {
    
}

class Firecart_FirecartIntegration_Model_Observer {

    public function logCartRemove(Varien_Event_Observer $observer) {
        $product = $observer->getQuoteItem()->getProduct();
        if (!$product->getId()) {
            return;
        }
        Mage::getModel('core/session')->setProductToShoppingCart(
                new Varien_Object(array(
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'product_url' => $product->getProductUrl(),
            'product_img' => $product->getSmallImageUrl(),
            'category_name' => Mage::getModel('catalog/category')->load($categories[0])->getName(),
                ))
        );
    }

    public function logCartAdd(Varien_Event_Observer $observer) {
        $product = Mage::getModel('catalog/product')
                ->load($observer->getData('controller_action')->getRequest()->getParam('product', 0));

        if (!$product->getId()) {
            return;
        }

        $categories = $product->getCategoryIds();
        Mage::getModel('core/session')->setProductToShoppingCart(
                new Varien_Object(array(
            'id' => $product->getId(),
            'qty' => $observer->getData('controller_action')->getRequest()->getParam('qty', 1),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'product_url' => $product->getProductUrl(),
            'product_img' => $product->getSmallImageUrl(),
            'category_name' => Mage::getModel('catalog/category')->load($categories[0])->getName(),
                ))
        );
    }

    public function placeCustomJs(Varien_Event_Observer $observer) {
        $event = $observer->getEvent();
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $customerData = Mage::getModel('customer/customer')->load($customer->getId())->getData();
            $name = $customer->getFirstname() . ' ' . $customer->getLastname();
            $isActive = $customer->getIsActive();
            $email = $customer->getEmail();
            $mobile = "";
            if ($customer->getPrimaryBillingAddress() != NULL) {
                $mobile = $customer->getPrimaryBillingAddress()->getTelephone();
            }
        }

        $layout = $observer->getEvent()->getLayout();
        $update = $layout->getUpdate();

        $action = $observer->getEvent()->getAction();
        $fullActionName = $action->getFullActionName();

        if ($action instanceof Mage_Checkout_OnepageController && $action->getRequest()->getRequestedActionName() == 'success') {
            $update->addHandle('conversion_tracking_handle');
        } else {
            $update->addHandle('non_conversion_tracking_handle');
        }

        return $this;
    }

}

?>