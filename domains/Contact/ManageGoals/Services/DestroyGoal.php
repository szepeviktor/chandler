<?php

namespace App\Contact\ManageGoals\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Goal;
use App\Services\BaseService;
use Carbon\Carbon;

class DestroyGoal extends BaseService implements ServiceInterface
{
    private Goal $goal;

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
            'goal_id' => 'required|integer|exists:goals,id',
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
            'contact_must_belong_to_vault',
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Destroy a goal.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->goal = Goal::where('contact_id', $data['contact_id'])
            ->findOrFail($data['goal_id']);

        $this->goal->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_GOAL_DESTROYED,
            'description' => $this->goal->name,
        ]);
    }
}
