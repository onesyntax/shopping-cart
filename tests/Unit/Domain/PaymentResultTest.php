<?php

declare(strict_types=1);

use App\Domain\Checkout\PaymentResult;

it('is approved only when the charge was approved', function () {
    $result = PaymentResult::approved('TXN-1');

    expect($result->isApproved())->toBeTrue();
    expect($result->isDeclined())->toBeFalse();
    expect($result->isUnreachable())->toBeFalse();
    expect($result->reference)->toBe('TXN-1');
});

it('is declined only when the charge was declined', function () {
    $result = PaymentResult::declined();

    expect($result->isDeclined())->toBeTrue();
    expect($result->isApproved())->toBeFalse();
    expect($result->isUnreachable())->toBeFalse();
});

it('is unreachable only when the gateway could not be reached', function () {
    $result = PaymentResult::unreachable();

    expect($result->isUnreachable())->toBeTrue();
    expect($result->isApproved())->toBeFalse();
    expect($result->isDeclined())->toBeFalse();
});
