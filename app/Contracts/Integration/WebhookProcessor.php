<?php

namespace App\Contracts\Integration;

use App\Models\Application;

interface WebhookProcessor
{
    /**
     * Process an incoming webhook payload.
     */
    public function process(Application $app, array $payload): void;
}
