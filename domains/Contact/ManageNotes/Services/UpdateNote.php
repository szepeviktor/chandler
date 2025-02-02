<?php

namespace App\Contact\ManageNotes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactFeedItem;
use App\Models\Emotion;
use App\Models\Note;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UpdateNote extends BaseService implements ServiceInterface
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
            'emotion_id' => 'nullable|integer|exists:emotions,id',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:65535',
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
     * Update a note.
     *
     * @param  array  $data
     * @return Note
     */
    public function execute(array $data): Note
    {
        $this->validateRules($data);

        $this->note = Note::where('contact_id', $data['contact_id'])
            ->findOrFail($data['note_id']);

        if ($this->valueOrNull($data, 'emotion_id')) {
            Emotion::where('account_id', $data['account_id'])
            ->where('id', $data['emotion_id'])
            ->firstOrFail();
        }

        $this->note->body = $data['body'];
        $this->note->title = $this->valueOrNull($data, 'title');
        $this->note->emotion_id = $this->valueOrNull($data, 'emotion_id');
        $this->note->save();

        $this->contact->last_updated_at = Carbon::now();
        $this->contact->save();

        $this->createFeedItem();

        return $this->note;
    }

    private function createFeedItem(): void
    {
        $feedItem = ContactFeedItem::create([
            'author_id' => $this->author->id,
            'contact_id' => $this->contact->id,
            'action' => ContactFeedItem::ACTION_NOTE_UPDATED,
            'description' => Str::words($this->note->body, 10, '…'),
        ]);
        $this->note->feedItem()->save($feedItem);
    }
}
