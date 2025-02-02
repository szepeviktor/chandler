<?php

namespace App\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Support\Collection;

class GroupIndexViewHelper
{
    /**
     * Gets the list of groups in the vault.
     *
     * @param  Vault  $vault
     * @return Collection
     */
    public static function data(Vault $vault): Collection
    {
        return $vault->groups()->with('contacts')
            ->orderBy('name')
            ->get()
            ->map(function (Group $group) {
                $contactsCollection = $group->contacts()
                    ->get()
                    ->map(fn (Contact $contact) => [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'age' => $contact->age,
                        'avatar' => $contact->avatar,
                        'url' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ]);

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'url' => [
                        'show' => route('group.show', [
                            'vault' => $group->vault_id,
                            'group' => $group->id,
                        ]),
                    ],
                    'contacts' => $contactsCollection,
                ];
            });
    }
}
