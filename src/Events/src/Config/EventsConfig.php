<?php

declare(strict_types=1);

namespace Spiral\Events\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;
use Spiral\Events\Processor\ProcessorInterface;

/**
 * @psalm-type TProcessor = ProcessorInterface|class-string<ProcessorInterface>|Autowire
 * @psalm-type TListener = class-string|EventListener
 * @property array{
 *     processors: TProcessor[],
 *     listeners: array<class-string, TListener[]>
 * } $config
 */
final class EventsConfig extends InjectableConfig
{
    public const CONFIG = 'events';

    protected array $config = [
        'processors' => [],
        'listeners' => [],
    ];

    /**
     * Get registered listeners.
     *
     * @return array<class-string, EventListener[]>
     */
    public function getListeners(): array
    {
        $listeners = [];
        foreach ($this->config['listeners'] as $event => $eventListeners) {
            $listeners[$event] = \array_map(
                static fn (string|EventListener $listener): EventListener =>
                    \is_string($listener) ? new EventListener($listener) : $listener,
                $eventListeners
            );
        }

        return $listeners;
    }

    /**
     * @return TProcessor[]
     */
    public function getProcessors(): array
    {
        return $this->config['processors'];
    }
}
