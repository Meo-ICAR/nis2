<?php

namespace App\Services\Integration;

use App\Contracts\Integration\SubsystemConnector;
use App\Contracts\Integration\WebhookProcessor;
use App\Models\Application;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class IntegrationManager
{
    /**
     * Get the connector for the given application.
     */
    public function getConnector(Application $app): ?SubsystemConnector
    {
        $driver = $app->connector_type;

        if (!$driver) {
            return null;
        }

        return match ($driver) {
            'wso2' => new \App\Integrations\Wso2\Wso2Connector($app),
            default => null,
        };
    }

    /**
     * Get the webhook processor for the given application.
     */
    public function getProcessor(Application $app): ?WebhookProcessor
    {
        $driver = $app->connector_type;

        if (!$driver) {
            return null;
        }

        // Example: if driver is 'generic_alert', return a generic processor
        return null;
    }
}
