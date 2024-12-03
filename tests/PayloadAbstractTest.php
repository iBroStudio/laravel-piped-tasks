<?php

use IBroStudio\TestSupport\Data\FakeData;
use IBroStudio\TestSupport\Processes\FakeProcess;
use IBroStudio\TestSupport\Processes\Payloads\FakeDtoPayload;
use IBroStudio\TestSupport\Processes\Payloads\FakePayload;
use Illuminate\Support\Collection;

it('can instantiate payload', function () {
    $payload = new FakePayload;

    expect($payload)->toBeInstanceOf(FakePayload::class);
});

it('can set process', function () {
    $payload = new FakePayload;
    $payload->process = new FakeProcess;

    expect($payload->process)->toBeInstanceOf(FakeProcess::class);
});

it('can convert payload to collection', function () {
    $payload = new FakePayload;
    $payload->process = new FakeProcess;

    expect($payload->toCollection())->toBeInstanceOf(Collection::class)
        ->and($payload->toCollection()->has('process'))->toBeFalse();
});

it('can convert payload to array', function () {
    $payload = new FakePayload;

    expect($payload->toArray())->toBeArray();
});

it('can transform array to dto', function () {
    $payload = new FakeDtoPayload([
        'name' => fake()->name,
        'description' => fake()->text,
    ]);

    expect($payload->dto)->toBeInstanceOf(FakeData::class);
});
