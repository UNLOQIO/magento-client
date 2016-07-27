<?php
class Unloq_Login_Model_Options
{
    /**
     * Provides available options under the UNLOQ Administration settings.
     *
     * @return array
     */
    public function themes()
    {
        return array(
            'light' => 'Light',
            'dark' => 'Dark'
        );
    }

}