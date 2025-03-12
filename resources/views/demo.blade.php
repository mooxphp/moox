@extends('layouts.guest')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    <h1 class="text-3xl font-bold text-center gradient-text-default mb-10 p-8">Demo</h1>

    <div class="bg-slate-950/60 rounded-lg p-8 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
        <div class="m-10">
            <h2 class="text-2xl font-bold gradient-text-subtle mb-8"> Wanna see Moox in action?
            </h2>

            <p class="text-gray-300 mb-10">
                Check out our online demos for Moox CMS, Moox Press using WordPress and Moox DevOps, our central management platform.
            </p>

            <div class="flex gap-6 mt-8 justify-center">
                <a href="/moox"
                   class="relative px-4 py-2 rounded flex items-center gap-2 group">
                    <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-md relative z-10 text-gray-200">rocket_launch</span>
                    <span class="relative z-10 text-gray-200">Moox CMS</span>
                </a>

                <a href="/press"
                class="relative px-4 py-2 rounded flex items-center gap-2 group">
                 <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-md relative z-10 text-gray-200">rocket_launch</span>
                    <span class="relative z-10 text-gray-200">Moox Press</span>
                </a>

                <a href="/devops"
                   class="relative px-4 py-2 rounded flex items-center gap-2 group">
                    <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-md relative z-10 text-gray-200">rocket_launch</span>
                    <span class="relative z-10 text-gray-200">Moox DevOps</span>
                </a>
            </div>

            <div class="mt-12 p-6 bg-slate-950/40 rounded-lg border border-pink-500/10">
                <h3 class="text-xl font-bold text-gray-200 mb-4">Login:</h3>
                <ul class="list-disc text-gray-300 ml-6 space-y-2">
                    <li>Username: admin@moox.org</li>
                    <li>Password: admin</li>
                </ul>
            </div>

            <div class="mt-6 p-6 bg-slate-950/40 rounded-lg border border-pink-500/10">
                <h3 class="text-xl font-bold text-gray-200 mb-4">Note:</h3>
                <p class="text-gray-300 mb-4">
                    We need to reset the database for the demos regularly. If you have problems to reach the demos, please try again in a minute.
                    <br>
                    <br>
                    Not working for you? Drop us a line at <a href="mailto:hello@moox.org" class="text-pink-500 hover:text-pink-600">hello@moox.org</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
