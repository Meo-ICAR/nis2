<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class GrafanaMetricsMiddleware
{
    /**
     * Gestisce la richiesta in ingresso, calcola le metriche e le invia a Grafana Live.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Registra il tempo di inizio millisecondo prima dell'esecuzione dell'API
        $startTime = microtime(true);

        // 2. Esegue l'API e ottiene la risposta dal controller
        $response = $next($request);

        // 3. Calcola la durata effettiva della richiesta in millisecondi
        $durationMs = round((microtime(true) - $startTime) * 1000);

        // 4. Estrae e normalizza i metadati dell'API per evitare caratteri speciali
        $endpoint = $request->route() ? $request->route()->uri() : $request->getPathInfo();
        $endpointClean = str_replace([' ', '{', '}', '/'], ['_', '', '', '_'], trim($endpoint, '/'));
        if (empty($endpointClean)) {
            $endpointClean = 'root';
        }

        $method = $request->method();
        $statusCode = $response->getStatusCode();

        // 5. Costruisce il payload in formato Influx Line Protocol con il timestamp finale
        // Nota: I nanosecondi si ottengono moltiplicando il time() per 1.000.000.000
        $timestampNs = time() * 1000000000;

        $payload = sprintf(
            'laravel_api,endpoint=%s,method=%s,status=%s duration_ms=%d %d',
            $endpointClean,
            $method,
            $statusCode,
            $durationMs,
            $timestampNs  // <--- Questo dice a Grafana ESATTAMENTE dove posizionare il punto sul grafico!
        );

        // 6. Invia i dati in tempo reale a Grafana tramite HTTP POST
        // Invia i dati a Grafana saltando il controllo SSL locale
        try {
            $grafanaResponse = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GRAFANA_TOKEN'),
                    'Content-Type' => 'text/plain',
                ])
                ->withBody($payload, 'text/plain')
                // Cambiamo l'endpoint in quello universale dei plugin
                ->post(env('GRAFANA_URL') . '/api/live/push/laravel_app');

            Log::info('GRAFANA PUSH - Status: ' . $grafanaResponse->status());

            if ($grafanaResponse->failed()) {
                Log::error('GRAFANA PUSH FAILED - Body: ' . $grafanaResponse->body());
            }
        } catch (\Exception $e) {
            Log::error('GRAFANA PUSH EXCEPTION: ' . $e->getMessage());
        }
        return $response;
    }
}
