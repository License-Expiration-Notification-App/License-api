<?php

namespace App\Listeners;

use App\Events\AuditTrailEvent;
use App\Models\AuditTrail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditTrailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Event  $event
     * @return void
     */
    public function handle(AuditTrailEvent $event)
    {
        //
        // $audit_trail_obj = new AuditTrail();
        // $audit_trail_obj->addEvent($event->request);
    }
}
