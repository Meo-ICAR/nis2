<?php

namespace App\Observers;

use App\Mail\ExternalTechnicalContactInvitation;
use App\Models\Application;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Mail;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void
    {
        $this->logAction($application, 'create');

        if ($application->external_technical_email) {
            //   $this->sendInvitation($application);
        }
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        $this->logAction($application, 'update');

        if ($application->wasChanged('external_technical_email') && $application->external_technical_email) {
            //  $this->sendInvitation($application);
        }
    }

    /**
     * Handle the Application "deleted" event.
     */
    public function deleted(Application $application): void
    {
        $this->logAction($application, 'delete');
    }

    /**
     * Send the invitation email to the external technical contact.
     */
    protected function sendInvitation(Application $application): void
    {
        Mail::to($application->external_technical_email)->send(new ExternalTechnicalContactInvitation($application));
    }

    /**
     * Helper to log actions to AuditLog.
     */
    protected function logAction(Application $application, string $action): void
    {
        AuditLog::create([
            'event_type' => $action,
            'user_id' => auth()->id(),
            'subject_type' => 'Application',
            'subject_id' => $application->id,
            'payload' => [
                'before' => $action === 'update' ? $application->getOriginal() : [],
                'after' => $action !== 'delete' ? $application->toArray() : [],
            ],
            'ip_address' => request()->ip(),
        ]);
    }
}
