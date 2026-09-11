<?php

namespace App\Http\Controllers;

use App\Services\Health\HealthCheck;
use App\Services\Health\SystemHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Whether the server is well.
 *
 * `/health` is for a monitoring tool: 200 when every check passes, 503 when one
 * fails, and — because it is unauthenticated — nothing else unless the caller is
 * signed in with the maintenance permission, in which case it says what failed.
 * The status page is the same checks for a person.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, SystemHealth $health): JsonResponse
    {
        $checks = $health->checks();
        $healthy = ! collect($checks)->contains(fn (HealthCheck $check): bool => $check->isFailing());

        $body = ['status' => $healthy ? 'ok' : 'failing'];

        if ($request->user()?->can('maintenance')) {
            $body['checks'] = collect($checks)->map(fn (HealthCheck $check): array => [
                'key' => $check->key,
                'status' => $check->status,
                'detail' => $check->detail,
            ])->values()->all();
        }

        return response()->json($body, $healthy ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }

    public function status(SystemHealth $health): View
    {
        return view('app.maintenance.status', [
            'checks' => $health->checks(),
            'healthy' => $health->isHealthy(),
        ]);
    }
}
