<?php

declare(strict_types=1);

namespace Framework\Bootloader\Queue;

use Spiral\Config\ConfigManager;
use Spiral\Config\LoaderInterface;
use Spiral\Queue\Bootloader\QueueBootloader;
use Spiral\Queue\Config\QueueConfig;
use Spiral\Queue\Failed\FailedJobHandlerInterface;
use Spiral\Queue\Failed\LogFailedJobHandler;
use Spiral\Queue\HandlerRegistryInterface;
use Spiral\Queue\Interceptor\Handler;
use Spiral\Queue\QueueConnectionProviderInterface;
use Spiral\Queue\QueueManager;
use Spiral\Queue\QueueRegistry;
use Spiral\Tests\Framework\BaseTest;

final class QueueBootloaderTest extends BaseTest
{
    public const ENV = [
        'QUEUE_CONNECTION' => 'foo',
    ];

    public function testHandlerRegistryInterfaceBinding(): void
    {
        $this->assertContainerBoundAsSingleton(HandlerRegistryInterface::class, QueueRegistry::class);
    }

    public function testFailedJobHandlerInterfaceBinding(): void
    {
        $this->assertContainerBoundAsSingleton(FailedJobHandlerInterface::class, LogFailedJobHandler::class);
    }

    public function testQueueConnectionProviderInterfaceBinding(): void
    {
        $this->assertContainerBoundAsSingleton(QueueConnectionProviderInterface::class, QueueManager::class);
    }

    public function testQueueManagerBinding(): void
    {
        $this->assertContainerBoundAsSingleton(QueueManager::class, QueueManager::class);
    }

    public function testQueueRegistryBinding(): void
    {
        $this->assertContainerBoundAsSingleton(QueueRegistry::class, QueueRegistry::class);
    }

    public function testHandlerBinding(): void
    {
        $this->assertContainerBoundAsSingleton(Handler::class, Handler::class);
    }

    public function testConfig(): void
    {
        $this->assertConfigMatches(QueueConfig::CONFIG, [
            'default' => 'foo',
            'connections' => [
                'sync' => ['driver' => 'sync'],
            ],

            'registry' => [
                'handlers' => [],
            ],
            'driverAliases' => [
                'sync' => \Spiral\Queue\Driver\SyncDriver::class,
                'null' => \Spiral\Queue\Driver\NullDriver::class,
            ],
            'interceptors' => [
                \Spiral\Queue\Interceptor\ErrorHandlerInterceptor::class,
            ],
        ]);
    }

    public function testAddInterceptor(): void
    {
        $configs = new ConfigManager($this->createMock(LoaderInterface::class));
        $configs->setDefaults(QueueConfig::CONFIG, ['interceptors' => []]);

        $bootloader = new QueueBootloader($configs);
        $bootloader->addInterceptor('foo');
        $bootloader->addInterceptor('bar');

        $this->assertSame([
            'foo', 'bar'
        ], $configs->getConfig(QueueConfig::CONFIG)['interceptors']);
    }

    public function testRegisterDriverAlias(): void
    {
        $configs = new ConfigManager($this->createMock(LoaderInterface::class));
        $configs->setDefaults(QueueConfig::CONFIG, ['driverAliases' => []]);

        $bootloader = new QueueBootloader($configs);
        $bootloader->registerDriverAlias('foo', 'bar');

        $this->assertSame([
            'bar' => 'foo'
        ], $configs->getConfig(QueueConfig::CONFIG)['driverAliases']);
    }
}
