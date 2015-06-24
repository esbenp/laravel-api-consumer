<?php

use Optimus\ApiConsumer\Facade\ApiConsumer as ApiConsumerFacade;

class StubFacade extends ApiConsumerFacade {
    public function getAccessor()
    {
        return parent::getFacadeAccessor();
    }
}

class ApiConsumerTest extends Orchestra\Testbench\TestCase {

    public function testFacadeIsWorking()
    {
        $facade = new StubFacade;

        $this->assertEquals('apiconsumer', $facade->getAccessor());
    }
    
}