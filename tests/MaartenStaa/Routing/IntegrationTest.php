<?php namespace MaartenStaa\Routing;

/**
 * Copyright (c) 2015 by Maarten Staa.
 *
 * Some rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above
 *       copyright notice, this list of conditions and the following
 *       disclaimer in the documentation and/or other materials provided
 *       with the distribution.
 *
 *     * The names of the contributors may not be used to endorse or
 *       promote products derived from this software without specific
 *       prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        if ($this->app === null) {
            $this->refreshApplication();
        }
    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();

        $this->app['env'] = 'testing';

        $loader = $this->getMockBuilder('Illuminate\Config\LoaderInterface')
        	->setMethods(array('load', 'exists', 'getNamespaces', 'cascadePackage'))
        	->getMockForAbstractClass();

        $loader->method('load')
        	->will($this->returnValue(array()));

        $loader->method('exists')
        	->will($this->returnValue(true));

        $loader->method('getNamespaces')
        	->will($this->returnValue(array()));

        $loader->method('cascadePackage')
        	->will($this->returnValue(array()));

        $this->app['config'] = new Repository($loader, $this->app['env']);

        $this->app['cache'] = new CacheManager($this->app);
        $this->app['cache']->setDefaultDriver('array');

        $this->app->boot();
    }

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected function createApplication()
    {
        return new Application;
    }

    /**
     * Create a router.
     *
     * @return Router
     */
    protected function getRouter()
    {
    	return new Router($this->app['events'], $this->app);
    }

	public function testCacheRoutes()
	{
		$router = $this->getRouter();

		$key = $router->cache(
			__FILE__,
			function () use ($router) {
				$router->get('/', 'HomeController@actionIndex');
			}
		);

		$this->assertTrue($this->app->cache->has($key), 'Routes must be in cache');
		$this->assertEquals(1, $router->getRoutes()->count(), 'Routes must be in collection');

		$cachedRoutes = unserialize($this->app->cache->get($key));
		$this->assertArrayHasKey('routes', $cachedRoutes);
		$this->assertArrayHasKey('GET', $cachedRoutes['routes']);
		$this->assertCount(1, $cachedRoutes['routes']['GET']);
	}
}
