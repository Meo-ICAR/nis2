<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Integration\IntegrationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected IntegrationManager $manager
    ) {}

    /**
     * Handle incoming webhooks.
     */
    public function handle(Request $request, string $token): JsonResponse
    {
        $app = Application::where('webhook_token', $token)->first();

        if (!$app) {
            Log::warning("Invalid webhook token received: {$token}");
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        Log::info("Webhook received for application: {$app->name}", ['payload' => $payload]);

        $processor = $this->manager->getProcessor($app);

        if ($processor) {
            $processor->process($app, $payload);
        } else {
            Log::info("No specific processor found for application: {$app->name}. Payload logged only.");
        }

        return response()->json(['status' => 'success', 'received' => true]);
    }
}
