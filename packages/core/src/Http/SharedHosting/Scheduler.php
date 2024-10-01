<?php

namespace Moox\Core\Http\SharedHosting;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class Scheduler extends Controller
{
    public function __invoke()
    {
        $sharedHostingToken = config('core.shared_hosting.token');

        if (request('_token') !== $sharedHostingToken) {
            activity()->log('Unauthorized scheduler request');
            abort(403, 'Unauthorized');
        }

        // TODO: log, activity log is optional
        activity()->log('Scheduler invoked by route');

        $output = Artisan::call('schedule:run');

        if ($output == 0) {
            activity()->log('Scheduler ran successfully');

            return 'Scheduler run was successful';
        } else {
            activity()->log('Ran Scheduler with output: '.$output);

            return 'Scheduler ran with output: '.$output;
        }
    }
}
