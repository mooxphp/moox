<?php

namespace Moox\Prompts\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\Prompts\Support\PromptFlowStateStore;

class CancelFlowController extends Controller
{
    /**
     * Mark a flow as cancelled when the user leaves the page (e.g. tab close, navigate away).
     * Called via sendBeacon from the Run Command view on pagehide.
     */
    public function __invoke(Request $request, PromptFlowStateStore $stateStore)
    {
        $request->validate([
            'flow_id' => ['required', 'string', 'max:255'],
        ]);

        $flowId = $request->input('flow_id');
        $stateStore->reset($flowId);

        return response()->json(['ok' => true]);
    }
}
