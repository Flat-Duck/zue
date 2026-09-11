<?php

namespace App\Services;

use App\Contracts\AuditLoggerContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserAccountService
{
    public function __construct(
        private readonly AuditLoggerContract $auditLogger,
        private readonly SignatureService $signatureService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @param  Collection<int, Role>  $roles
     */
    public function create(array $validated, Collection $roles, ?UploadedFile $signature = null): User
    {
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if ($signature !== null) {
            $this->signatureService->saveSignature($user, $signature);
        }

        $user->syncRoles($roles);

        $this->auditLogger->record('user.created', [
            'user_id' => $user->id,
            'roles' => $user->getRoleNames()->all(),
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  Collection<int, Role>  $roles
     */
    public function update(User $user, array $validated, Collection $roles, ?UploadedFile $signature = null): void
    {
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if ($signature !== null) {
            $this->signatureService->saveSignature($user, $signature);
        }

        $rolesBefore = $user->getRoleNames()->all();

        $user->syncRoles($roles);

        $rolesAfter = $user->fresh()->getRoleNames()->all();

        if ($rolesBefore !== $rolesAfter) {
            $this->auditLogger->record('user.roles_changed', [
                'user_id' => $user->id,
                'from' => $rolesBefore,
                'to' => $rolesAfter,
            ]);
        }

    }
}
