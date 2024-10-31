<?php

interface Wc_Mp_Shipping_Method_Interface
{
    /**
     * @return void
     */
    public function init();

    /**
     * @return string
     */
    public function default_title();

    /**
     * @return string
     */
    public function method_description();
}