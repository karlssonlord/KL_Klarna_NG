<?php

class KL_Klarna_Helper_Json extends KL_Klarna_Helper_Abstract {

    /**
     * Print error message in json format
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

        return $this->success($array);
    }

    /**
     * Build json response of some data
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
        return json_encode($message);
    }

}