<?php

use Mockery as m;

class LaravelServiceProviderTest extends Orchestra\Testbench\TestCase {

    public function testServiceProviderIsWorking()
    {
        $appMock = m::mock('Illuminate\Foundation\Application');

        $appMock->shouldReceive('bindShared')->with(
            'apiconsumer',
            m::on(function($closure){
                var_dump($closure);
                var_dump("YOP");
                return true;
            })
        );

        $provider = $this->app->make('Optimus\ApiConsumer\Provider\LaravelServiceProvider', [
            $appMock
        ]);

        $this->assertNull($provider->register());
        $provider->boot();
    }
    
}