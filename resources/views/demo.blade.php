@extends('layouts.guest')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <h1 class="text-3xl font-bold text-center gradient-text-default mb-10 p-8">Demo</h1>

    <div class="bg-slate-950/60 rounded-lg p-8 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
        <div class="m-10">
            <h2 class="text-2xl font-bold gradient-text-subtle mb-8">Online Demo Coming Soon</h2>

            <p class="text-gray-300 mb-10">
                We're working on setting up a comprehensive online demo that will showcase all the features of Moox. Stay tuned!
            </p>

            <h2 class="text-2xl font-bold gradient-text-subtle mb-8">Try Locally Now</h2>

            <p class="text-gray-300 mb-6">
                You don't have to wait to try Moox! You can run a full demo locally in just a few minutes using our monorepo's Laravel application.
            </p>

            <div class="flex gap-6 mt-8">
                <a href="https://github.com/mooxphp/moox"
                   class="relative px-4 py-2 rounded flex items-center gap-2 group">
                    <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-sm relative z-10 text-gray-200">code</span>
                    <span class="relative z-10 text-gray-200">View Monorepo</span>
                </a>

                <a href="https://github.com/mooxphp/moox#quick-start"
                   class="relative px-4 py-2 rounded flex items-center gap-2 group">
                    <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-sm relative z-10 text-gray-200">rocket_launch</span>
                    <span class="relative z-10 text-gray-200">Quick Start Guide</span>
                </a>
            </div>

            <div class="mt-12 p-6 bg-slate-950/40 rounded-lg border border-pink-500/10">
                <h3 class="text-xl font-bold text-gray-200 mb-4">What's included in the local demo:</h3>
                <ul class="list-disc text-gray-300 ml-6 space-y-2">
                    <li>Complete Laravel application setup</li>
                    <li>All Moox packages pre-configured</li>
                    <li>Sample data and examples</li>
                    <li>Development environment ready to explore</li>
                    <li>Latest features and updates</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
