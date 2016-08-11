<?php

/**
 * Login model class
 *
 * @author Diana Botean <diana.botean@evozon.com>
 * */
class Unloq_Login_Model_Login extends Mage_Core_Model_Abstract
{
    /**
     * Will code the module usage methods
     * */
    const CUSTOMER_LOGIN           = 1;
    const ADMIN_LOGIN              = 2;
    const CUSTOMER_AND_ADMIN_LOGIN = 3;

    /**
     * Admin and frontend area codes
     * */
    const ADMIN_AREA    = "admin";
    const CUSTOMER_AREA = "customer";
}