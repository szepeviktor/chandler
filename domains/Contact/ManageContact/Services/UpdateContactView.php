<?php

namespace App\Contact\ManageContact\Services;

use App\Interfaces\ServiceInterface;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateContactView extends BaseService implements ServiceInterface
{
    private array $data;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'vault_id' => 'required|integer|exists:vaults,id',
            'author_id' => 'required|integer|exists:users,id',
            'contact_id' => 'required|integer|exists:contacts,id',
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
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Increment the number of views on the contact by the given user in the
     * given vault.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data): void
    {
        $this->data = $data;

        $this->validateRules($data);
        $this->updateView();
    }

    private function updateView(): void
    {
        $contact = [
            'contact_id' => $this->data['contact_id'],
            'vault_id' => $this->data['vault_id'],
            'user_id' => $this->data['author_id'],
        ];

        $exists = DB::table('contact_vault_user')
            ->where($contact)
            ->exists();

        if ($exists) {
            DB::table('contact_vault_user')
                ->where($contact)
                ->increment('number_of_views');
        } else {
            DB::table('contact_vault_user')->insert($contact + [
                'number_of_views' => 1,
            ]);
        }
    }
}
