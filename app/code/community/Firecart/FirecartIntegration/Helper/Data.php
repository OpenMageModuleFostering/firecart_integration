<?php
class Firecart_FirecartIntegration_Helper_Data extends Mage_Core_Helper_Abstract
{
}

class Firecart_FirecartIntegration_Model_Observer
{
    public function placeCustomJs()
    {
 
        $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $role = Mage::getSingleton('customer/group')->load($roleId)->getData('customer_group_code');
        $role = strtolower($role);
 
        $headBlock = Mage::app()->getLayout()->getBlock('head');
        //if($headBlock && $role=='wholesale')
        if($headBlock)
        {
            $headBlock->addJs('test/myScript.js');
        }
        return $this;
    }
}
?>