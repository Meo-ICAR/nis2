# Integration Gateway Walkthrough

I have implemented the **Integration Gateway**, a core infrastructure layer that allows the NIS2 Portal to act as a bidirectional HUB.

## Key Components

### 1. Unified Webhook Gateway (Inbound)
- **Endpoint**: `POST /api/v1/webhooks/{token}`.
- **Security**: Each application has a unique `webhook_token` (UUID). Only requests with a valid token are accepted.
- **Controller**: `WebhookController` handles incoming requests, logs the payload, and delegates processing to the appropriate integration logic.

### 2. Integration Manager (Outbound & Strategy)
- **Service**: `IntegrationManager` uses the Strategy pattern to resolve how to interact with each subsystem.
- **Contracts**: Defined `SubsystemConnector` (Outbound) and `WebhookProcessor` (Inbound) interfaces to ensure a consistent API across different vendors.

### 3. Database & Model Updates
- Added `connector_type`, `webhook_token`, and `integration_config` (JSON) to the `Application` model.
- Enabled JSON casting for `integration_config` to allow easy management of custom parameters.

### 4. Admin Interface (Filament)
- Added a new **"Integrazione & API"** tab to the Application management form.
- **Token Generation**: Included a "Regenerate" action that creates a new UUID for the webhook token on the fly.
- **Config Management**: Added a JSON editor for custom integration settings.

## How to use it:
1.  Go to an Application in the Admin panel.
2.  Open the **Integrazione & API** tab.
3.  Choose a **Connector Type** and generate a **Webhook Token**.
4.  External systems can now send alerts to `http://your-portal/api/v1/webhooks/{token}`.
5.  All incoming data is logged, and if a specific processor is implemented in the `IntegrationManager`, it will be executed automatically.
