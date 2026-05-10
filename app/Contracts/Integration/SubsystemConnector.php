<?php

namespace App\Contracts\Integration;

use App\Models\Application;

interface SubsystemConnector
{
    /**
     * Synchronize data from the subsystem.
     */
    public function sync(): array;

    /**
     * Get the health status of the subsystem connection.
     */
    public function getStatus(): string;
}
