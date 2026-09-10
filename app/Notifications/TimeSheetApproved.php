<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Someone's time sheet reached the next stage of approval.
 *
 * Written to the database so it survives a closed browser, and broadcast so an open
 * one hears about it now. Both channels carry the same payload, built once in
 * {@see payload()}, because a notification that reads differently depending on how
 * it arrived is a bug waiting to be reported as a mystery.
 */
class TimeSheetApproved extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        private readonly Employee $employee,
        private readonly string $stage,
        private readonly int $month,
        private readonly int $year,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'type' => 'timesheet.approved',
            'icon' => 'ti-checkbox',
            'title' => __('crud.time_sheets.name'),
            'message' => __('notifications.timesheet_approved', [
                'employee' => $this->employee->name ?? $this->employee->english_name,
                'stage' => $this->stage,
                'month' => $this->month,
                'year' => $this->year,
            ]),
            'url' => route('time-sheets.approve', ['selected_month' => $this->month, 'selected_year' => $this->year]),
            'employee_id' => $this->employee->id,
        ];
    }
}
