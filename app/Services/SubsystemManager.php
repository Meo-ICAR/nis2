<?php
namespace App\Services;
class SubsystemManager {
    public function connect(Application $app): SubsystemConnector {
        $driver = $app->connector_type; // es. 'hpc', 'oidc', 'custom_rest'

        return match($driver) {
            'hpc' => new HpcConnector($app),
            'wso2' => new Wso2Connector($app),
            default => new DefaultRestConnector($app),
        };
    }
}
