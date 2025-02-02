<?php

namespace App\Settings\ManageNotificationChannels\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleAllContactRemindersForNotificationChannel extends BaseService implements ServiceInterface
{
    private array $data;

    private UserNotificationChannel $userNotificationChannel;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'user_notification_channel_id' => 'required|integer|exists:user_notification_channels,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
        ];
    }

    /**
     * Schedule all the contact reminders of the given user for a given user
     * notification channel.
     * This is useful when we create a new user notification channel, or when
     * we activate a formerly inactive user notification channel.
     *
     * @param  array  $data
     * @return UserNotificationChannel
     */
    public function execute(array $data): UserNotificationChannel
    {
        $this->data = $data;
        $this->validate();
        $this->schedule();

        return $this->userNotificationChannel;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->userNotificationChannel = UserNotificationChannel::where('user_id', $this->data['author_id'])
            ->findOrFail($this->data['user_notification_channel_id']);
    }

    private function schedule(): void
    {
        $vaults = Vault::where('account_id', $this->data['account_id'])
            ->pluck('id')->toArray();

        $contactReminders = DB::table('contact_reminders')
            ->join('contacts', 'contacts.id', '=', 'contact_reminders.contact_id')
            ->join('vaults', 'vaults.id', '=', 'contacts.vault_id')
            ->whereIn('vaults.id', $vaults)
            ->select('contact_reminders.id as id', 'contact_reminders.day as day', 'contact_reminders.year as year', 'contact_reminders.month as month')
            ->get();

        foreach ($contactReminders as $contactReminder) {
            if (! $contactReminder->year) {
                $upcomingDate = Carbon::parse('1900-'.$contactReminder->month.'-'.$contactReminder->day);
            } else {
                $upcomingDate = Carbon::parse($contactReminder->year.'-'.$contactReminder->month.'-'.$contactReminder->day);
            }

            if ($upcomingDate->isPast()) {
                $upcomingDate->year = Carbon::now()->year;

                if ($upcomingDate->isPast()) {
                    $upcomingDate->year = Carbon::now()->addYear()->year;
                }
            }

            $upcomingDate->shiftTimezone($this->userNotificationChannel->user->timezone);
            $upcomingDate->hour = $this->userNotificationChannel->preferred_time->hour;
            $upcomingDate->minute = $this->userNotificationChannel->preferred_time->minute;

            DB::table('contact_reminder_scheduled')->insert([
                'user_notification_channel_id' => $this->userNotificationChannel->id,
                'contact_reminder_id' => $contactReminder->id,
                'scheduled_at' => $upcomingDate->tz('UTC'),
            ]);
        }
    }
}
