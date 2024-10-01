<?php

namespace Moox\Jobs\Http\SharedHosting;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class QueueWorker extends Controller
{
    public function __invoke()
    {
        $sharedHostingToken = config('core.shared_hosting.token');

        if (request('_token') !== $sharedHostingToken) {
            activity()->log('Unauthorized queue worker request');

            abort(403, 'Unauthorized');
        }

        // TODO: log, activity log is optional and should be configurable
        activity()->log('Queue worker invoked by route');

        $timeout = 60;

        if (request('timeout')) {
            $timeout = request('timeout');
        }

        $output = Artisan::call('queue:work --once --timeout='.$timeout);

        if ($output == 0) {
            activity()->log('Queue worker ran successfully');

            return 'Queue worker run was successful';
        } else {
            activity()->log('Ran queue worker with output: '.$output);

            return 'Queue worker ran with output: '.$output;
        }
    }
}
