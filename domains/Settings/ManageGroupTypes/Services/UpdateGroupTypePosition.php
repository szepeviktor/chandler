<?php

namespace App\Settings\ManageGroupTypes\Services;

use App\Interfaces\ServiceInterface;
use App\Models\GroupType;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class UpdateGroupTypePosition extends BaseService implements ServiceInterface
{
    private GroupType $groupType;

    private int $pastPosition;

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
            'author_id' => 'required|integer|exists:users,id',
            'group_type_id' => 'required|integer|exists:group_types,id',
            'new_position' => 'required|integer',
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
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update the group type's position.
     *
     * @param  array  $data
     * @return GroupType
     */
    public function execute(array $data): GroupType
    {
        $this->data = $data;
        $this->validate();
        $this->updatePosition();

        return $this->groupType;
    }

    private function validate(): void
    {
        $this->validateRules($this->data);

        $this->groupType = GroupType::where('account_id', $this->data['account_id'])
            ->findOrFail($this->data['group_type_id']);

        $this->pastPosition = DB::table('group_types')
            ->where('id', $this->groupType->id)
            ->select('position')
            ->first()->position;
    }

    private function updatePosition(): void
    {
        if ($this->data['new_position'] > $this->pastPosition) {
            $this->updateAscendingPosition();
        } else {
            $this->updateDescendingPosition();
        }

        DB::table('group_types')
            ->where('id', $this->groupType->id)
            ->update([
                'position' => $this->data['new_position'],
            ]);
    }

    private function updateAscendingPosition(): void
    {
        DB::table('group_types')
            ->where('position', '>', $this->pastPosition)
            ->where('position', '<=', $this->data['new_position'])
            ->decrement('position');
    }

    private function updateDescendingPosition(): void
    {
        DB::table('group_types')
            ->where('position', '>=', $this->data['new_position'])
            ->where('position', '<', $this->pastPosition)
            ->increment('position');
    }
}
