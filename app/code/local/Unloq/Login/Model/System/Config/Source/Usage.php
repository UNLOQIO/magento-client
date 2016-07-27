<?php

class Unloq_Login_Model_System_Config_Source_Usage
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Unloq_Login_Model_Login::CUSTOMER_LOGIN,
                'label' => 'Customer login',
            ),
            array(
                'value' => Unloq_Login_Model_Login::ADMIN_LOGIN,
                'label' => 'Admin login',
            ),
            array(
                'value' => Unloq_Login_Model_Login::CUSTOMER_AND_ADMIN_LOGIN,
                'label' => 'Customer and admin login',
            ),
        );
    }
}