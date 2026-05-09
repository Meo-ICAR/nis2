<?php

namespace App\Jobs;

use App\Services\WSO2ApplicationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWSO2ApplicationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minuti (più lungo per le applicazioni)

    /**
     * Execute the job.
     */
    public function handle(WSO2ApplicationService $wso2Service): void
    {
        try {
            Log::info('Starting WSO2 applications sync job');

            $result = $wso2Service->syncApplications();

            Log::info('WSO2 applications sync job completed successfully', [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'total' => $result['total']
            ]);

        } catch (\Exception $e) {
            Log::error('WSO2 applications sync job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger job retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WSO2 applications sync job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
