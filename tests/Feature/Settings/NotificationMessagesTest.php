<?php

use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use App\Services\NotificationService;

test('the set-password email renders Lao defaults with a valid reset link', function () {
    $user = User::factory()->create(['email' => 'staff@namtheun2.com', 'display_name' => 'Staff One']);

    $mail = (new SetPasswordNotification('tok-123'))->toMail($user);

    expect($mail->subject)->toBe('ຕັ້ງ / ຣີເຊັດ ລະຫັດຜ່ານ');
    expect($mail->actionText)->toBe('ຕັ້ງ ລະຫັດຜ່ານ');
    expect($mail->actionUrl)->toContain('/reset-password/tok-123');
    expect($mail->actionUrl)->toContain('email=staff%40namtheun2.com');
    expect($mail->salutation)->toBe('ດ້ວຍ ຄວາມ ນັບຖື,');
    expect($mail->salutation)->not->toContain('Laravel');   // no framework leak
    expect(implode(' ', $mail->outroLines))->toContain('60'); // {minutes} replaced
});

test('switching the send language to English renders the English email', function () {
    Setting::put('notifications', ['enabled' => true, 'lang' => 'en']);
    $user = User::factory()->create();

    $mail = (new SetPasswordNotification('t'))->toMail($user);

    expect($mail->subject)->toBe('Set / Reset your password');
    expect($mail->actionText)->toBe('Set Password');
});

test('an admin-edited email template overrides the default (other fields fall back)', function () {
    Setting::put('notification_templates', [
        'email.set_password' => ['lo' => ['subject' => 'ຕັ້ງ ລະຫັດ ໃໝ່ ຂອງ NT2', 'button' => 'ກົດ ບ່ອນ ນີ້']],
    ]);
    $user = User::factory()->create();

    $mail = (new SetPasswordNotification('t'))->toMail($user);

    expect($mail->subject)->toBe('ຕັ້ງ ລະຫັດ ໃໝ່ ຂອງ NT2');
    expect($mail->actionText)->toBe('ກົດ ບ່ອນ ນີ້');
    expect($mail->greeting)->toBe('ສະບາຍ ດີ!');   // untouched → default
});

test('disabling an in-app template makes notify a no-op', function () {
    $user = User::factory()->create();
    Setting::put('notifications', ['enabled' => true]);
    Setting::put('notification_templates', ['request.submit' => ['enabled' => false]]);

    (new NotificationService)->notifyTemplate($user->id, 'info', 'request.submit', ['number' => 'R1', 'requester' => 'A']);

    expect(Notification::where('user_id', $user->id)->count())->toBe(0);
});

test('legacy flat template data still resolves (backward compatible)', function () {
    Setting::put('notification_templates', [
        'request.submit' => ['title' => 'OLD {number}', 'message' => 'from {requester}'],
    ]);

    $t = NotificationService::template('request.submit', ['number' => 'R9', 'requester' => 'Bob']);

    expect($t['title'])->toBe('OLD R9');
    expect($t['message'])->toBe('from Bob');
});
