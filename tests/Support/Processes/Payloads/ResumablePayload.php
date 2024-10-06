<?php

namespace IBroStudio\PipedTasks\Tests\Support\Processes\Payloads;

use IBroStudio\PipedTasks\Contracts\Payload;
use IBroStudio\PipedTasks\Contracts\UseProcessModel;
use IBroStudio\PipedTasks\Models\Task;

class ResumablePayload implements Payload, UseProcessModel
{
    use \IBroStudio\PipedTasks\Concerns\ResumablePayload;

    public function __construct(
        protected ?string $property1 = null,
        protected ?Task $property2 = null
    ) {}

    public function setProperty1(string $value): void
    {
        $this->property1 = $value;
    }

    public function getProperty1(): ?string
    {
        return $this->property1;
    }

    public function setProperty2(Task $value): void
    {
        $this->property2 = $value;
    }

    public function getProperty2(): ?Task
    {
        return $this->property2;
    }
}
