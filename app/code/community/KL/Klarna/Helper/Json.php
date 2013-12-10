<?php

class KL_Klarna_Helper_Json extends Mage_Core_Helper_Abstract {

    /**
     * Print error message in json format
     * This will also terminate any further processing of the calling script
     *
     * @author Robert Lord, Karlsson & Lord AB <robert@karlssonlord.com>
     *
     * @param $message
     *
     * @return void
     */
    public function error($message)
    {
        /**
         * Setup the array to be sent
         */
        $array = array(
            'error' => true,
            'message' => $message
        );

        $this->success($array);
    }

    /**
     * Build json response of some data
     * This will also terminate any further processing of the calling script
     *
     * @param $message
     *
     * @return void
     */
    public function success($message)
    {
        /**
         * Print it as json
         */
        print json_encode($message);

        /**
         * Terminate script
         */
        exit;
    }

}