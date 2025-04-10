<?php

namespace IBroStudio\PipedTasks\Concerns;

trait HasEvents
{
    protected bool $useEvents = false;

    public function withEvents(): static
    {
        $this->useEvents = true;

        return $this;
    }

    protected function fireEvent(string $event, ...$params): void
    {
        if (! $this->useEvents) {
            return;
        }

        event(new $event(...$params));
    }
}
