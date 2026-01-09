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
        $name = $data['name'] ?? null;
        $template = $data['template'] ?? 'general';
        $subIds = $data['subordinate_employee_ids'] ?? [];
        $settings = $data['settings'] ?? null;
        $context = $data['context'] ?? 'general';

        DB::transaction(function () use ($data, $managerId, $scopeType, $name, $template, $subIds, $settings, $context) {
            $baseAttributes = [
                'name' => $name,
                'template' => $template,
                'context' => $context,
                'settings' => $settings,
            ];

            switch ($scopeType) {
                case ManagementScope::TYPE_GLOBAL:
                    $this->createScope($managerId, $scopeType, $baseAttributes);
                    break;

                case ManagementScope::TYPE_LOCATION:
                    $this->createScope($managerId, $scopeType, array_merge($baseAttributes, [
                        'location_id' => $data['location_id'] ?? null,
                    ]));
                    break;

                case ManagementScope::TYPE_DEPARTMENT:
                    $this->createScope($managerId, $scopeType, array_merge($baseAttributes, [
                        'location_id' => $data['location_id'] ?? null,
                        'department_id' => $data['department_id'] ?? null,
                    ]));
                    break;

                case ManagementScope::TYPE_CENTER:
                    $this->createScope($managerId, $scopeType, array_merge($baseAttributes, [
                        'center_id' => $data['center_id'] ?? null,
                    ]));
                    break;

                case ManagementScope::TYPE_EMPLOYEE:
                    if (!empty($subIds)) {
                        $validSubIds = array_filter($subIds, fn($id) => $id !== $managerId);

                        if (!empty($validSubIds)) {
                            $newSettings = (array) $settings;
                            $newSettings['target_employee_ids'] = array_values($validSubIds);

                            $this->createScope($managerId, $scopeType, array_merge($baseAttributes, [
                                'subordinate_employee_id' => null,
                                'settings' => $newSettings,
                            ]));
                        }
                    }
                    break;
            }
        });
    }

    public function updateScope(ManagementScope $scope, array $data): void
    {
        // Explicitly extract fields for update
        $updateData = [
            'manager_id' => $data['manager_id'] ?? $scope->manager_id,
            'name' => array_key_exists('name', $data) ? $data['name'] : $scope->name,
            'template' => $data['template'] ?? $scope->template,
            'context' => $data['context'] ?? $scope->context,
            'scope_type' => $data['scope_type'] ?? $scope->scope_type,
            'location_id' => array_key_exists('location_id', $data) ? $data['location_id'] : $scope->location_id,
            'department_id' => array_key_exists('department_id', $data) ? $data['department_id'] : $scope->department_id,
            'center_id' => array_key_exists('center_id', $data) ? $data['center_id'] : $scope->center_id,
        ];

        $subIds = $data['subordinate_employee_ids'] ?? [];
        $settings = $data['settings'] ?? $scope->settings;

        if ($updateData['scope_type'] === ManagementScope::TYPE_EMPLOYEE && !empty($subIds)) {
            $validSubIds = array_filter($subIds, fn($id) => $id !== $updateData['manager_id']);
            $settings = (array) $settings;
            $settings['target_employee_ids'] = array_values($validSubIds);
            $updateData['subordinate_employee_id'] = null;
        } else {
            // Keep existing subordinate if not grouping
            $updateData['subordinate_employee_id'] = $scope->subordinate_employee_id;
        }

        $updateData['settings'] = $settings;

        $scope->update($updateData);
    }

    protected function createScope($managerId, $scopeType, array $attributes = [])
    {
        ManagementScope::create(array_merge([
            'manager_id' => $managerId,
            'scope_type' => $scopeType,
            'name' => null,
            'template' => 'general',
            'context' => 'general',
            'location_id' => null,
            'department_id' => null,
            'center_id' => null,
            'subordinate_employee_id' => null,
            'settings' => null,
        ], $attributes));
    }
}
