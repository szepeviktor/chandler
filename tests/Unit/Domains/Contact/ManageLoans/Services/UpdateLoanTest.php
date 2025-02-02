<?php

namespace Tests\Unit\Domains\Contact\ManageLoans\Services;

use App\Contact\ManageLoans\Services\UpdateLoan;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Loan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateLoanTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_loan(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loaner = Contact::factory()->create(['vault_id' => $vault->id]);
        $loanee = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan, $loaner, $loanee);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLoan())->execute($request);
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
        $loaner = Contact::factory()->create(['vault_id' => $vault->id]);
        $loanee = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $account, $vault, $contact, $loan, $loaner, $loanee);
    }

    /** @test */
    public function it_fails_if_contact_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create();
        $loaner = Contact::factory()->create(['vault_id' => $vault->id]);
        $loanee = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan, $loaner, $loanee);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loaner = Contact::factory()->create(['vault_id' => $vault->id]);
        $loanee = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan, $loaner, $loanee);
    }

    /** @test */
    public function it_fails_if_loan_is_not_in_the_contact(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loaner = Contact::factory()->create(['vault_id' => $vault->id]);
        $loanee = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan, $loaner, $loanee);
    }

    /** @test */
    public function it_fails_if_loaner_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loaner = Contact::factory()->create();
        $loanee = Contact::factory()->create(['vault_id' => $vault->id]);
        $loan = Loan::factory()->create([]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan, $loaner, $loanee);
    }

    /** @test */
    public function it_fails_if_loanee_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $loaner = Contact::factory()->create(['vault_id' => $vault->id]);
        $loanee = Contact::factory()->create();
        $loan = Loan::factory()->create([]);

        $this->executeService($regis, $regis->account, $vault, $contact, $loan, $loaner, $loanee);
    }

    private function executeService(User $author, Account $account, Vault $vault, Contact $contact, Loan $loan, Contact $loaner, Contact $loanee): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'contact_id' => $contact->id,
            'loan_id' => $loan->id,
            'type' => Loan::TYPE_DEBT,
            'name' => 'resto',
            'description' => 'This is incredible',
            'loaner_ids' => [$loaner->id],
            'loanee_ids' => [$loanee->id],
            'amount_lent' => 123,
            'loaned_at' => '2020-01-01',
        ];

        $loan = (new UpdateLoan())->execute($request);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'vault_id' => $vault->id,
            'type' => Loan::TYPE_DEBT,
            'name' => 'resto',
            'description' => 'This is incredible',
            'amount_lent' => 123,
            'loaned_at' => '2020-01-01 00:00:00',
        ]);

        $this->assertDatabaseHas('contact_loan', [
            'loan_id' => $loan->id,
            'loaner_id' => $loaner->id,
            'loanee_id' => $loanee->id,
        ]);
    }
}
