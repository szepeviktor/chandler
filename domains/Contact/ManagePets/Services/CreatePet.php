<?php

namespace App\Contact\ManagePets\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Pet;
use App\Models\PetCategory;
use App\Services\BaseService;
use Carbon\Carbon;

class CreatePet extends BaseService implements ServiceInterface
{
    private Pet $pet;

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
            'pet_category_id' => 'required|integer|exists:pet_categories,id',
            'name' => 'nullable|string|max:255',
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
            'author_must_be_vault_editor',
            'contact_must_belong_to_vault',
        ];
    }

    /**
     * Create a pet.
     *
     * @param  array  $data
     * @return Pet
     */
    public function execute(array $data): Pet
    {
        $this->validateRules($data);

        PetCategory::where('account_id', $data['account_id'])
            ->findOrFail($data['pet_category_id']);

        $this->pet = Pet::create([
            'contact_id' => $this->contact->id,
            'pet_category_id' => $data['pet_category_id'],
            'name' => $this->valueOrNull($data, 'name'),
        ]);

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();

        return $this->pet;
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_PET_CREATED,
            'description' => $this->pet->petCategory->name,
        ]);

        $this->pet->feedItem()->save($feedItem);
    }
}
