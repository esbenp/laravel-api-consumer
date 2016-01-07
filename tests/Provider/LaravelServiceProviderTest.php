<?php

use Mockery as m;

class LaravelServiceProviderTest extends Orchestra\Testbench\TestCase {

    public function testServiceProviderIsWorking()
    {
        $appMock = m::mock('Illuminate\Foundation\Application');

        $appMock->shouldReceive('singleton')->with(
            'apiconsumer',
            m::type('Closure')
        );

        $provider = $this->app->make('Optimus\ApiConsumer\Provider\LaravelServiceProvider', [
            $appMock
        ]);

        $this->assertNull($provider->register());
        $provider->boot();
    }
    
}
