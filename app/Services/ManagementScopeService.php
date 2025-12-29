<?php

namespace App\Services;

use App\Models\ManagementScope;
use Illuminate\Support\Facades\DB;

class ManagementScopeService
{
    public function createScopes(array $data): void
    {
        $managerId = $data['manager_id'];
        $scopeType = $data['scope_type'];
        $subIds = $data['subordinate_employee_ids'] ?? [];
        $settings = $data['settings'] ?? null;
        $context = $data['context'] ?? 'general';

        DB::transaction(function () use ($data, $managerId, $scopeType, $subIds, $settings, $context) {
            switch ($scopeType) {
                case ManagementScope::TYPE_GLOBAL:
                    $this->createScope($managerId, $scopeType, [
                        'settings' => $settings,
                        'context' => $context
                    ]);
                    break;

                case ManagementScope::TYPE_LOCATION:
                    $this->createScope($managerId, $scopeType, [
                        'location_id' => $data['location_id'],
                        'settings' => $settings,
                        'context' => $context
                    ]);
                    break;

                case ManagementScope::TYPE_DEPARTMENT:
                    $this->createScope($managerId, $scopeType, [
                        'location_id' => $data['location_id'],
                        'department_id' => $data['department_id'],
                        'settings' => $settings,
                        'context' => $context
                    ]);
                    break;

                case ManagementScope::TYPE_CENTER:
                    $this->createScope($managerId, $scopeType, [
                        'center_id' => $data['center_id'],
                        'settings' => $settings,
                        'context' => $context
                    ]);
                    break;

                case ManagementScope::TYPE_EMPLOYEE:
                    // 1. Handle single subordinate (legacy/simple)
                    foreach ($subIds as $subId) {
                        if ($managerId === $subId)
                            continue;
                        // We can still create individual rows if desired, OR group them.
                        // For backward compatibility or specific use cases, let's keep individual rows 
                        // IF the user didn't request grouping. 
                        // BUT the requirement is to allow grouping.
                        // Let's assume if we have multiple subIds, we group them into one scope.
                    }

                    // NEW LOGIC: Grouping
                    // If we have subIds, create ONE scope with all IDs in settings
                    if (!empty($subIds)) {
                        // Filter out self
                        $validSubIds = array_filter($subIds, fn($id) => $id !== $managerId);

                        if (!empty($validSubIds)) {
                            // Merge with existing settings
                            $newSettings = $settings ?? [];
                            $newSettings['target_employee_ids'] = array_values($validSubIds);

                            $this->createScope($managerId, $scopeType, [
                                'subordinate_employee_id' => null, // Grouped scope has no single subordinate
                                'settings' => $newSettings,
                                'context' => $context
                            ]);
                        }
                    }
                    break;
            }
        });
    }

    public function updateScope(ManagementScope $scope, array $data): void
    {
        // For update, we usually handle single scope update. 
        // The original controller logic for update was reusing 'validateScope' which returned normalized data.
        // We will stick to simple update here.

        $scope->update($data);
    }

    protected function createScope($managerId, $scopeType, array $attributes = [])
    {
        ManagementScope::create(array_merge([
            'manager_id' => $managerId,
            'scope_type' => $scopeType,
            'location_id' => null,
            'department_id' => null,
            'center_id' => null,
            'subordinate_employee_id' => null,
            'settings' => null,
            'context' => 'general',
        ], $attributes));
    }
}
