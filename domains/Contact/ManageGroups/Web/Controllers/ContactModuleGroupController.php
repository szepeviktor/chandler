<?php

namespace App\Contact\ManageGroups\Web\Controllers;

use App\Contact\ManageGroups\Services\AddContactToGroup;
use App\Contact\ManageGroups\Services\CreateGroup;
use App\Contact\ManageGroups\Services\RemoveContactFromGroup;
use App\Contact\ManageGroups\Web\ViewHelpers\ModuleGroupsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleGroupController extends Controller
{
    /**
     * Store the group.
     * There are two scenarios:
     * 1) the group needs to be created, and the contact needs to be associated
     * with it,
     * 2) the group already exists, and the contact needs to be associated
     * with it.
     *
     * @param  Request  $request
     * @param  int  $vaultId
     * @param  int  $contactId
     * @return JsonResponse
     */
    public function store(Request $request, int $vaultId, int $contactId): JsonResponse
    {
        // case if we need to create the group
        // the current way to know if we need to create the group is if the
        // name is empty. I know, it's not ideal.
        $groupId = 0;
        if ($request->input('name') != '') {
            $data = [
                'account_id' => Auth::user()->account_id,
                'author_id' => Auth::user()->id,
                'vault_id' => $vaultId,
                'group_type_id' => $request->input('group_type_id'),
                'name' => $request->input('name'),
            ];

            $group = (new CreateGroup())->execute($data);
            $groupId = $group->id;
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'group_id' => $request->input('group_id') != 0 ? $request->input('group_id') : $groupId,
            'group_type_role_id' => $request->input('group_type_role_id') ?? null,
        ];

        $group = (new AddContactToGroup())->execute($data);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGroupsViewHelper::dto($contact, $group, true),
        ], 201);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $groupId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'group_id' => $groupId,
        ];

        $group = (new RemoveContactFromGroup())->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleGroupsViewHelper::dto($contact, $group, false),
        ], 200);
    }
}
