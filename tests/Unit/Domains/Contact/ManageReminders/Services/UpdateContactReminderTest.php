<?php

namespace Tests\Unit\Domains\Contact\ManageReminders\Services;

use App\Contact\ManageReminders\Services\UpdateContactReminder;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateContactReminderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_reminder(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $reminder);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateContactReminder())->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $reminder);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $reminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $reminder);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $reminder);
    }

    /** @test */
    public function it_fails_if_reminder_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = ContactReminder::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $contact, $reminder);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, ContactReminder $reminder): void
    {
        UserNotificationChannel::factory()->create([
            'user_id' => $author->id,
            'preferred_time' => '18:00',
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'contact_reminder_id' => $reminder->id,
            'label' => 'birthdate',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'frequency_number' => null,
        ];

        $reminder = (new UpdateContactReminder())->execute($request);

        $this->assertDatabaseHas('contact_reminders', [
            'id' => $reminder->id,
            'contact_id' => $contact->id,
            'label' => 'birthdate',
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'frequency_number' => null,
        ]);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'contact_reminder_id' => $reminder->id,
        ]);
    }
}
