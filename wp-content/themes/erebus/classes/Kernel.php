<?php

namespace MarcinTest;

use NanoSoup\Zeus\Kernel as KernelBase;


class Kernel extends KernelBase
{
    public function __construct()
    {
        parent::__construct();
        $this->registerClasses();
    }

    /**
     * @return array
     */
    public function registerClasses()
    {
        return [

        ];
    }
}
