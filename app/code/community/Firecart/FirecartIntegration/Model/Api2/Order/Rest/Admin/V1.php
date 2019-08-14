<?php

/*
 * @author Rohit Shinde. http://firecart.io
 */

class Firecart_FirecartIntegration_Model_Api2_Order_Rest_Admin_V1 extends Mage_Sales_Model_Api2_Order_Rest {

    /**
     * Retrieve information about specified order item
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        $orderId = $this->getRequest()->getParam('id');
        $collection = $this->_getCollectionForSingleRetrieve($orderId);

        if ($this->_isPaymentMethodAllowed()) {
            $this->_addPaymentMethodInfo($collection);
        }
        if ($this->_isGiftMessageAllowed()) {
            $this->_addGiftMessageInfo($collection);
        }
        $this->_addTaxInfo($collection);

        $order = $collection->getItemById($orderId);

        if (!$order) {
            $this->_critical(self::RESOURCE_NOT_FOUND);
        }
        $orderData = $order->getData();
        $addresses = $this->_getAddresses(array($orderId));
        $items = $this->_getItems(array($orderId));
        $comments = $this->_getComments(array($orderId));

        if ($addresses) {
            $orderData['addresses'] = $addresses[$orderId];
        }
        if ($items) {
            $itemsArray = array();
            $modifiedItems = array();
            foreach ($items as $_items) {
                foreach ($_items as $item) {
                    $product = Mage::getModel('catalog/product')->load($item['item_id']);
                    if ($product):
                        $url = $product->getProductUrl();
                        $imageUrl = $product->getSmallImageUrl();
                        $item['product_url'] = $url;
                        $item['image_url'] = $imageUrl;
                    endif;
                    array_push($modifiedItems, $item);
                }
            }
            $itemsArray = array($orderId => $modifiedItems);
            $orderData['order_items'] = $itemsArray[$orderId];
        }
        if ($comments) {
            $orderData['order_comments'] = $comments[$orderId];
        }
        return $orderData;
    }

    /**
     * Retrieve list of coupon codes.
     *
     * @return array
     */
    protected function _retrieveCollection() {
        $cust_type = $this->getRequest()->getParam('cust_type');
        if ($cust_type == "GUEST") {
            $collection = Mage::getModel('sales/order')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('customer_group_id', Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
            $this->_applyCollectionModifiers($collection);
        } else {
            $collection = $this->_getCollectionForRetrieve();
        }

        if ($this->_isPaymentMethodAllowed()) {
            $this->_addPaymentMethodInfo($collection);
        }
        if ($this->_isGiftMessageAllowed()) {
            $this->_addGiftMessageInfo($collection);
        }
        $this->_addTaxInfo($collection);

        $ordersData = array();

        foreach ($collection->getItems() as $order) {
            $ordersData[$order->getId()] = $order->toArray();
        }
        if ($ordersData) {
            foreach ($this->_getAddresses(array_keys($ordersData)) as $orderId => $addresses) {
                $ordersData[$orderId]['addresses'] = $addresses;
            }

            foreach ($this->_getItems(array_keys($ordersData)) as $orderId => $items) {
                $modifiedItems = array();
                foreach ($items as $item) {
                    $product = Mage::getModel('catalog/product');
                    $product->load($product->getIdBySku($item['sku']));
                    if ($product):
                        $url = $product->getProductUrl();
                        $imageUrl = $product->getSmallImageUrl();
                        $item['product_url'] = $url;
                        $item['image_url'] = $imageUrl;
                    endif;
                    array_push($modifiedItems, $item);
                }
                $ordersData[$orderId]['order_items'] = $modifiedItems;
            }

        }
        return $ordersData;
    }

}
