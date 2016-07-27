<?php

/**
 * Source model class
 *
 * @author Diana Botean <diana.botean@evozon.com>
 * */
class Unloq_Login_Model_System_Config_Source_Usage
{
    /**
     * Will retrieve the array of options available
     * for the admin user to choose from when configuring
     * the UNLOQ application usage method
     *
     * @author Diana Botean <diana.botean@evozon.com>
     * @return array
     * */
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