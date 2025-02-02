<?php

namespace App\Contact\ManageGoals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Goal;
use App\Services\BaseService;
use Carbon\Carbon;

class CreateGoal extends BaseService implements ServiceInterface
{
    private Goal $goal;

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
     * Create a goal.
     *
     * @param  array  $data
     * @return Goal
     */
    public function execute(array $data): Goal
    {
        $this->data = $data;
        $this->validate();

        $this->create();
        $this->createFeedItem();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        return $this->goal;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);
    }

    private function create(): void
    {
        $this->goal = Goal::create([
            'contact_id' => $this->contact->id,
            'author_id' => $this->author->id,
            'name' => $this->data['name'],
            'active' => true,
        ]);
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_GOAL_CREATED,
            'description' => $this->data['name'],
        ]);
        $this->goal->feedItem()->save($feedItem);
    }
}
