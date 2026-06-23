<?php

namespace App\Services;

use App\Models\ExpoEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ExpoService — ສ້າງ/ຈັດການ ໃບງານ Expo (Phase 6.9). CRUD-centric (ບໍ່ມີ state machine ໜັກ).
 */
class ExpoService
{
    public function nextNumber(int $year): string
    {
        $prefix = "EXP{$year}-";
        $max = DB::table('expo_events')->where('expo_number', 'like', $prefix.'%')
            ->lockForUpdate()->max('expo_number');
        $seq = $max ? ((int) substr($max, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array  $data  title, topic, background, venue, city, country, address,
     *                       start_date, end_date, total_companies_at_expo,
     *                       attendee_ids: int[]
     */
    public function create(array $data, $actor): ExpoEvent
    {
        return DB::transaction(function () use ($data, $actor) {
            $start = Carbon::parse($data['start_date'] ?? Carbon::today());

            $event = ExpoEvent::create([
                'expo_number' => $this->nextNumber((int) $start->year),
                'title' => $data['title'],
                'topic' => $data['topic'] ?? null,
                'background' => $data['background'] ?? null,
                'venue' => $data['venue'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'address' => $data['address'] ?? null,
                'start_date' => $start->toDateString(),
                'end_date' => ! empty($data['end_date']) ? Carbon::parse($data['end_date'])->toDateString() : null,
                'total_companies_at_expo' => $data['total_companies_at_expo'] ?? null,
                'status' => 'draft',
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncAttendees($event, $data['attendee_ids'] ?? []);

            return $event->refresh();
        });
    }

    /** ຕັ້ງ attendees ໃໝ່ (snapshot ຊື່), ຮັກສາ opinion ເກົ່າ ຖ້າ user ຍັງຢູ່. */
    public function syncAttendees(ExpoEvent $event, array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $existing = $event->attendees()->pluck('user_id')->all();

        $event->attendees()->whereNotIn('user_id', $userIds ?: [0])->delete();

        foreach ($userIds as $uid) {
            if (! in_array($uid, $existing, true)) {
                $u = User::find($uid);
                $event->attendees()->create(['user_id' => $uid, 'user_name' => $u?->display_name ?? $u?->email ?? 'user '.$uid]);
            }
        }
    }
}
