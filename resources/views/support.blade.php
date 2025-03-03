@extends('layouts.guest')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <h1 class="text-3xl font-bold text-center gradient-text-default mb-10 p-8">Support</h1>

    <div class="bg-slate-950/60 rounded-lg p-8 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
        <div class="m-10">
            <h2 class="text-2xl font-bold gradient-text-subtle mb-8">Business Support</h2>

            <p class="text-gray-300 mb-6">
                We are currently working on providing business support options tailored to your needs. Stay tuned for more information about our professional support services or drop us a line at <a href="mailto:hello@moox.org?subject=Moox%20Support" class="text-pink-400">hello@moox.org</a>.
            </p>

            <h2 class="text-2xl font-bold gradient-text-subtle mb-8 mt-12">Open Source Support</h2>

            <p class="text-gray-300 mb-6">
                For our open source packages, please use the official issue tracker in our monorepo:
            </p>

            <div class="mt-8">
                <a href="https://github.com/mooxphp/moox/issues"
                   class="relative px-4 py-2 rounded flex items-center gap-2 group w-fit">
                    <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-sm relative z-10 text-gray-200">bug_report</span>
                    <span class="relative z-10 text-gray-200">mooxphp/moox Issues</span>
                </a>
            </div>

            <p class="text-gray-300 mt-8">
                Before creating a new issue, please search the existing issues to avoid duplicates. When creating an issue, provide as much relevant information as possible, including:
            </p>

            <ul class="list-disc text-gray-300 mt-4 ml-6 space-y-2">
                <li>Package version</li>
                <li>PHP version</li>
                <li>Laravel version</li>
                <li>Clear description of the problem</li>
                <li>Steps to reproduce</li>
                <li>Expected vs actual behavior</li>
            </ul>
        </div>
    </div>
</div>
@endsection
