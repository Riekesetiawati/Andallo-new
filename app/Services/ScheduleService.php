<?php

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\ProviderSlotReservation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    public function slotsFor(Service $service, Carbon $date): array
    {
        $providerId = $service->provider_id;
        $blocked = AvailabilitySlot::query()
            ->where('provider_id', $providerId)
            ->where('is_blocked', true)
            ->whereDate('specific_date', $date->toDateString())
            ->exists();

        if ($blocked) {
            return [];
        }

        $specific = AvailabilitySlot::query()
            ->where('provider_id', $providerId)
            ->where('is_blocked', false)
            ->whereDate('specific_date', $date->toDateString())
            ->get();

        $windows = $specific->isNotEmpty()
            ? $specific
            : AvailabilitySlot::query()
                ->where('provider_id', $providerId)
                ->where('is_blocked', false)
                ->whereNull('specific_date')
                ->where('weekday', $date->dayOfWeek)
                ->get();

        if ($windows->isEmpty()) {
            return [];
        }

        $duration = max(30, (int) $service->duration_minutes);
        $slots = [];

        foreach ($windows as $window) {
            $cursor = Carbon::parse($date->toDateString().' '.substr((string) $window->start_time, 0, 5));
            $end = Carbon::parse($date->toDateString().' '.substr((string) $window->end_time, 0, 5));

            while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($slotEnd->greaterThan(now())) {
                    $slots[$cursor->format('H:i')] = [
                        'start' => $cursor->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                    ];
                }
                $cursor->addMinutes($duration);
            }
        }

        $reservations = ProviderSlotReservation::query()
            ->where('provider_id', $providerId)
            ->where('starts_at', '<', $date->copy()->endOfDay())
            ->where('ends_at', '>', $date->copy()->startOfDay())
            ->get();

        return array_values(array_filter($slots, function (array $slot) use ($date, $reservations) {
            $start = Carbon::parse($date->toDateString().' '.$slot['start']);
            $end = Carbon::parse($date->toDateString().' '.$slot['end']);

            return ! $this->overlaps($reservations, $start, $end);
        }));
    }

    public function overlaps(Collection $reservations, Carbon $start, Carbon $end, ?int $ignoreBookingId = null): bool
    {
        foreach ($reservations as $reservation) {
            if ($ignoreBookingId && $reservation->booking_id === $ignoreBookingId) {
                continue;
            }
            if ($reservation->starts_at < $end && $reservation->ends_at > $start) {
                return true;
            }
        }

        return false;
    }

    public function isBookable(Service $service, Carbon $start, Carbon $end): bool
    {
        $slots = $this->slotsFor($service, $start->copy()->startOfDay());

        foreach ($slots as $slot) {
            if ($slot['start'] === $start->format('H:i') && $slot['end'] === $end->format('H:i')) {
                return true;
            }
        }

        return false;
    }
}
