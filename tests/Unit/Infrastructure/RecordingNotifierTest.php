<?php

declare(strict_types=1);

use App\Domain\Notification\Notification;
use App\Domain\Notification\NotificationKind;
use App\Infrastructure\Notification\RecordingNotifier;

function note(string $recipient): Notification
{
    return new Notification($recipient, NotificationKind::OrderPaid);
}

it('records every notification it is given', function () {
    $notifier = new RecordingNotifier;
    $first = note('alice');
    $second = note('bob');

    $notifier->notify($first);
    $notifier->notify($second);

    expect($notifier->all())->toBe([$first, $second]);
});

it('returns the most recent notification for a recipient', function () {
    $notifier = new RecordingNotifier;
    $older = note('alice');
    $newer = note('alice');

    $notifier->notify($older);
    $notifier->notify(note('bob'));
    $notifier->notify($newer);

    expect($notifier->lastFor('alice'))->toBe($newer);
});

it('returns null when a recipient has no notifications', function () {
    expect((new RecordingNotifier)->lastFor('nobody'))->toBeNull();
});
