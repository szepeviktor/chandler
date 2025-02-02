<?php

namespace App\Contact\ManageNotes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Note;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DestroyNote extends BaseService implements ServiceInterface
{
    private Note $note;

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
            'note_id' => 'required|integer|exists:notes,id',
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
     * Destroy a note.
     *
     * @param  array  $data
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $this->note = Note::where('contact_id', $data['contact_id'])
            ->findOrFail($data['note_id']);

        $this->createFeedItem();
        $this->note->delete();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();
    }

    private function createFeedItem(): void
    {
        ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_NOTE_DESTROYED,
            'description' => Str::words($this->note->body, 10, '…'),
        ]);
    }
}
