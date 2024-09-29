<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes\Payloads;

use IBroStudio\PipedTasks\Payload;

class FakePayload implements Payload
{
    public function __construct(
        protected ?string $property1 = null,
        protected ?array $property2 = null
    ) {}

    public function getProperty1(): ?string
    {
        return $this->property1;
    }

    public function getProperty2(): ?array
    {
        return $this->property2;
    }
}
