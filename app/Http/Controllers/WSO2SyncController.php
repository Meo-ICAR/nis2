<?php

namespace App\Http\Controllers;

use App\Jobs\SyncWSO2AdminUsersJob;
use App\Jobs\SyncWSO2ApplicationsJob;
use App\Services\WSO2ApplicationService;
use App\Services\WSO2UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WSO2SyncController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get sync statistics
     */
    public function stats(WSO2UserService $wso2Service): JsonResponse
    {
        Gate::authorize('viewAdminStats');

        $stats = $wso2Service->getSyncStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Trigger immediate sync
     */
    public function sync(Request $request, WSO2UserService $wso2Service): JsonResponse
    {
        Gate::authorize('syncWSO2Users');

        $async = $request->boolean('async', true);

        try {
            if ($async) {
                // Dispatch job for async execution
                SyncWSO2AdminUsersJob::dispatch();

                return response()->json([
                    'success' => true,
                    'message' => 'Sincronizzazione avviata in background',
                    'async' => true
                ]);
            } else {
                // Execute sync immediately
                $result = $wso2Service->syncAdminUsers();

                return response()->json([
                    'success' => true,
                    'message' => 'Sincronizzazione completata',
                    'data' => $result,
                    'async' => false
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get applications sync statistics
     */
    public function applicationsStats(WSO2ApplicationService $wso2AppService): JsonResponse
    {
        Gate::authorize('viewAdminStats');

        $stats = $wso2AppService->getSyncStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Trigger applications immediate sync
     */
    public function syncApplications(Request $request, WSO2ApplicationService $wso2AppService): JsonResponse
    {
        Gate::authorize('syncWSO2Users');

        $async = $request->boolean('async', true);
        $appId = $request->input('id');

        try {
            if ($async) {
                // Dispatch job for async execution
                SyncWSO2ApplicationsJob::dispatch();

                return response()->json([
                    'success' => true,
                    'message' => 'Sincronizzazione applicazioni avviata in background',
                    'async' => true
                ]);
            } else {
                // Execute sync immediately
                if ($appId) {
                    $application = $wso2AppService->syncApplicationById($appId);
                    $result = ['synced' => [$application], 'total' => 1];
                } else {
                    $result = $wso2AppService->syncApplications();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Sincronizzazione applicazioni completata',
                    'data' => $result,
                    'async' => false
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione applicazioni: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get last sync status (placeholder - could be enhanced with database tracking)
     */
    public function status(): JsonResponse
    {
        Gate::authorize('viewAdminStats');

        // This could be enhanced to track sync status in database
        return response()->json([
            'success' => true,
            'data' => [
                'last_sync' => null,  // Could be stored in cache or database
                'next_sync' => null,  // Based on schedule
                'status' => 'idle'
            ]
        ]);
    }
}
