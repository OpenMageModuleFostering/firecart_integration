<?php

/*
 * @author Rohit Shinde. http://firecart.io
 */

class Firecart_FirecartIntegration_Model_Api2_Customer_Rest_Admin_V1 extends Mage_Customer_Model_Api2_Customer_Rest {

    /**
     * Retrieve information about customer
     * Add last logged in datetime
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve() {
        /** @var $log Mage_Log_Model_Customer */
        $log = Mage::getModel('log/customer');
        $log->loadByCustomer($this->getRequest()->getParam('id'));

        $data = parent::_retrieve();
        $data['is_confirmed'] = (int) !(isset($data['confirmation']) && $data['confirmation']);

        $lastLoginAt = $log->getLoginAt();
        if (null !== $lastLoginAt) {
            $data['last_logged_in'] = $lastLoginAt;
        }
        return $data;
    }

    /**
     * Get customers list
     *
     * @return array
     */
    protected function _retrieveCollection() {
//        $data = $this->_getCollectionForRetrieve()->load()->toArray();
        $data = $this->_getCollectionForRetrieve();


        $customersData = array();
        foreach ($data->getItems() as $customer) {
            if ($customer) { //check if the customer exists or is not guest
                $gender = $customer->getGender(); //this will return some id
                $genderText = $customer->getResource()
                        ->getAttribute('gender')
                        ->getSource()
                        ->getOptionText($customer->getData('gender')); //you get the label of the gender
                $customer['gender'] = $genderText;
            }
            $customersData[$customer->getId()] = $customer->toArray();
        }

        $cust_arr = array();
        foreach ($data as $custId => $customer) {
            array_push($cust_arr, $custId);
        }
        $orderCollection = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('customer_id', array('in' => $cust_arr))
                ->addAttributeToSelect('customer_id');

        $orderCollection->getSelect()
                ->columns('SUM(grand_total) as total_amount, COUNT(*) as total_orders, MAX(entity_id) as last_order_id')
                ->group('customer_id');
        $orderCollection->load();
        $order_arr = $orderCollection->toArray();

        $ordersData = array();
        foreach ($order_arr['items'] as $order) {
            $ordersData[$order['customer_id']] = $order;
        }

        if ($customersData) {
            foreach ($customersData as $custId => $customer) {
                foreach ($ordersData as $ord_cust_id => $order) {
                    if ($custId == $ord_cust_id):
                        $customersData[$custId]['total_amount_spent'] = $order['total_amount'];
                        $customersData[$custId]['orders_count'] = $order['total_orders'];
                        $customersData[$custId]['last_order_id'] = $order['last_order_id'];
                    endif;
                }
            }
        }
        
        return $customersData;
    }

}
