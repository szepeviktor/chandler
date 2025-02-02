<?php

namespace App\Settings\ManagePostTemplates\Web\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ManagePostTemplates\Services\CreatePostTemplate;
use App\Settings\ManagePostTemplates\Services\DestroyPostTemplate;
use App\Settings\ManagePostTemplates\Services\UpdatePostTemplate;
use App\Settings\ManagePostTemplates\Web\ViewHelpers\PersonalizePostTemplateViewHelper;
use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PersonalizePostTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Personalize/PostTemplates/Index', [
            'layoutData' => VaultIndexViewHelper::layoutData(),
            'data' => PersonalizePostTemplateViewHelper::data(Auth::user()->account),
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'label' => $request->input('label'),
            'can_be_deleted' => true,
        ];

        $postTemplate = (new CreatePostTemplate())->execute($data);

        return response()->json([
            'data' => PersonalizePostTemplateViewHelper::dto($postTemplate),
        ], 201);
    }

    public function update(Request $request, int $postTemplateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'post_template_id' => $postTemplateId,
            'label' => $request->input('label'),
        ];

        $postTemplate = (new UpdatePostTemplate())->execute($data);

        return response()->json([
            'data' => PersonalizePostTemplateViewHelper::dto($postTemplate),
        ], 200);
    }

    public function destroy(Request $request, int $postTemplateId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'post_template_id' => $postTemplateId,
        ];

        (new DestroyPostTemplate())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
