<?php

namespace App\Services;

use App\Models\ManagementScope;
use Illuminate\Support\Facades\DB;

class ManagementScopeService
{
    public function createScopes(array $data): void
    {
        $managerIds = (array) ($data['manager_ids'] ?? []);
        $scopeType = $data['scope_type'];
        $name = $data['name'] ?? null;
        $template = $data['template'] ?? 'general';
        $subIds = $data['subordinate_employee_ids'] ?? [];
        $settings = $data['settings'] ?? null;
        $context = $data['context'] ?? 'general';

        if (empty($managerIds)) {
            return;
        }

        DB::transaction(function () use ($data, $managerIds, $scopeType, $name, $template, $subIds, $settings, $context) {
            $baseAttributes = [
                'name' => $name,
                'template' => $template,
                'context' => $context,
                'settings' => $settings,
            ];

            $scope = null;

            switch ($scopeType) {
                case ManagementScope::TYPE_GLOBAL:
                    $scope = $this->createScope($managerIds, $scopeType, $baseAttributes);
                    break;

                case ManagementScope::TYPE_LOCATION:
                    $scope = $this->createScope($managerIds, $scopeType, array_merge($baseAttributes, [
                        'location_id' => $data['location_id'] ?? null,
                    ]));
                    break;

                case ManagementScope::TYPE_DEPARTMENT:
                    $scope = $this->createScope($managerIds, $scopeType, array_merge($baseAttributes, [
                        'location_id' => $data['location_id'] ?? null,
                        'department_id' => $data['department_id'] ?? null,
                    ]));
                    break;

                case ManagementScope::TYPE_CENTER:
                    $scope = $this->createScope($managerIds, $scopeType, array_merge($baseAttributes, [
                        'center_id' => $data['center_id'] ?? null,
                    ]));
                    break;

                case ManagementScope::TYPE_EMPLOYEE:
                    if (!empty($subIds)) {
                        // Filter out any IDs that might be in the manager list to prevent self-management
                        $validSubIds = array_diff($subIds, $managerIds);

                        if (!empty($validSubIds)) {
                            $newSettings = (array) $settings;
                            $newSettings['target_employee_ids'] = array_values($validSubIds);

                            $scope = $this->createScope($managerIds, $scopeType, array_merge($baseAttributes, [
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
        $managerIds = (array) ($data['manager_ids'] ?? []);

        // Explicitly extract fields for update
        $updateData = [
            'manager_id' => !empty($managerIds) ? $managerIds[0] : $scope->manager_id,
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
            $validSubIds = array_diff($subIds, $managerIds);
            $settings = (array) $settings;
            $settings['target_employee_ids'] = array_values($validSubIds);
            $updateData['subordinate_employee_id'] = null;
        } else {
            // Keep existing subordinate if not grouping
            $updateData['subordinate_employee_id'] = $scope->subordinate_employee_id;
        }

        $updateData['settings'] = $settings;

        DB::transaction(function () use ($scope, $updateData, $managerIds) {
            $scope->update($updateData);

            if (!empty($managerIds)) {
                $scope->managers()->sync($managerIds);
            }
        });
    }

    protected function createScope(array $managerIds, $scopeType, array $attributes = []): ManagementScope
    {
        return DB::transaction(function () use ($managerIds, $scopeType, $attributes) {
            $scope = ManagementScope::create(array_merge([
                'manager_id' => $managerIds[0] ?? null,
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

            $scope->managers()->attach($managerIds);

            return $scope;
        });
    }
}
