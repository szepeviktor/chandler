<?php

namespace Tests\Unit\Domains\Settings\ManagePronouns\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Pronoun;
use App\Models\User;
use App\Settings\ManagePronouns\Services\DestroyPronoun;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyPronounTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_pronoun(): void
    {
        $ross = $this->createAdministrator();
        $pronoun = Pronoun::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $pronoun);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyPronoun())->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $pronoun = Pronoun::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $pronoun);
    }

    /** @test */
    public function it_fails_if_pronoun_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $pronoun = Pronoun::factory()->create();
        $this->executeService($ross, $ross->account, $pronoun);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $pronoun = Pronoun::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $pronoun);
    }

    private function executeService(User $author, Account $account, Pronoun $pronoun): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'pronoun_id' => $pronoun->id,
        ];

        (new DestroyPronoun())->execute($request);

        $this->assertDatabaseMissing('pronouns', [
            'id' => $pronoun->id,
        ]);
    }
}
