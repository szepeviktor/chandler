<?php

namespace App\Vault\ManageVaultImportantDateTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDateType;
use App\Services\BaseService;

class CreateContactImportantDateType extends BaseService implements ServiceInterface
{
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
            'vault_id' => 'required|integer|exists:vaults,id',
            'label' => 'required|string|max:255',
            'internal_type' => 'nullable|string|max:255',
            'can_be_deleted' => 'nullable|boolean',
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
            'author_must_be_vault_editor',
            'vault_must_belong_to_account',
        ];
    }

    /**
     * Create a contact important date type.
     *
     * @param  array  $data
     * @return ContactImportantDateType
     */
    public function execute(array $data): ContactImportantDateType
    {
        $this->validateRules($data);

        $type = ContactImportantDateType::create([
            'vault_id' => $data['vault_id'],
            'label' => $data['label'],
            'internal_type' => $this->valueOrNull($data, 'internal_type'),
            'can_be_deleted' => $this->valueOrTrue($data, 'can_be_deleted'),
        ]);

        return $type;
    }
}
