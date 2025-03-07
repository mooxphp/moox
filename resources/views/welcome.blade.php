@extends('layouts.guest')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;700&display=swap" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tsparticles@2.0.6/tsparticles.bundle.min.js"></script>
<script>
window.onload = async function() {
    await tsParticles.load("tsparticles", {
        fullScreen: false,
        particles: {
            number: { value: 0 },
            shape: {
                type: "image",
                image: { src: "{{ asset('web/polygon.png') }}", width: 100, height: 75 }
            },
            opacity: { value: 1 },
            move: {
                enable: true,
                speed: 1,
                direction: "none",
                random: true,
                straight: false,
                outModes: "bounce"
            }
        },
        emitters: [
            {
                position: { x: 25, y: 55 },
                rate: { quantity: 1, delay: 0.1 },
                life: { duration: 0.1, count: 1 },
                particles: {
                    size: { value: 75 },
                    move: { distance: 100, radius: 100 },
                    number: { value: 1 }
                }
            },
            {
                position: { x: 75, y: 50 },
                rate: { quantity: 1, delay: 0.1 },
                life: { duration: 0.1, count: 1 },
                particles: {
                    size: { value: 40 },
                    move: { distance: 50, radius: 50 },
                    number: { value: 1 }
                }
            },
            {
                position: { x: 85, y: 75 },
                rate: { quantity: 1, delay: 0.1 },
                life: { duration: 0.1, count: 1 },
                particles: {
                    size: { value: 55 },
                    move: { distance: 50, radius: 50 },
                    number: { value: 1 }
                }
            }
        ]
    });
};

function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent
    const button = document.querySelector(`[data-copy-target="${elementId}"]`)
    const icon = button.querySelector('.material-symbols-rounded')

    navigator.clipboard.writeText(text)
        .then(() => {
            icon.textContent = 'check'
            icon.classList.add('text-green-500')

            setTimeout(() => {
                icon.textContent = 'content_copy'
                icon.classList.remove('text-green-500')
            }, 2000)
        })
        .catch(() => {
            icon.textContent = 'error'
            icon.classList.add('text-red-500')

            setTimeout(() => {
                icon.textContent = 'content_copy'
                icon.classList.remove('text-red-500')
            }, 2000)
        })
}
</script>
@endpush

@section('content')
<div class="max-w-6xl mx-auto my-10">
    <div class="flex justify-center gap-10">
        <div class="w-60">
            <img src="{{ asset('web/robot.png') }}" alt="Moox Bot" class="w-70 rotate-12">
        </div>
        <div class="pt-20 pb-20">
            <h1 class="text-3xl font-bold text-center gradient-text-default mb-5">
                Build smarter Laravel apps with Moox
            </h1>
            <h2 class="text-gray-300 text-center text-lg">
                A collection of powerful <span class="font-bold">Laravel</span> and <span class="font-bold">Filament</span> packages,<br>
                designed for the modern <span class="font-bold">Web & App Developer</span>.
            </h2>
            <div class="flex justify-center gap-5 mt-10 mb-10">
                <a href="#get-started" class="relative px-4 py-2 rounded flex items-center gap-2 group" onclick="event.preventDefault(); document.getElementById('get-started').scrollIntoView({behavior: 'smooth'})">
                    <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
                    <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
                    <span class="material-symbols-rounded text-md relative z-10 text-gray-200">rocket_launch</span>
                    <span class="relative z-10 text-gray-200">Get Started</span>
                </a>
            </div>
        </div>
        <div class="w-60"></div>
    </div>

    <div class="relative mx-20">
        <img src="{{ asset('web/mac.png') }}" alt="Macbook with Moox" class="w-full">

        <div class="absolute bottom-30 left-0 right-0 grid grid-cols-4 gap-10 px-5">
            <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-200 mb-3">72</div>
                    <div class="text-gray-400 text-sm">
                        <div class="text-xl mb-1 font-bold gradient-text-default">packages</div>
                        <div>for Laravel</div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-200 mb-3">48</div>
                    <div class="text-gray-400 text-sm">
                        <div class="text-xl mb-1 font-bold gradient-text-default">people</div>
                        <div>all contributors</div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-200 mb-3">102k</div>
                    <div class="text-gray-400 text-sm">
                        <div class="text-xl font-bold gradient-text-default">downloads</div>
                        <div>and counting</div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-200 mb-3">398</div>
                    <div class="text-gray-400 text-sm">
                        <div class="text-xl mb-1 font-bold gradient-text-default">stargazers</div>
                        <div>on GitHub</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto mt-10" id="get-started">
        <h2 class="text-3xl font-bold text-center gradient-text-vibrant mb-10 p-8">Get Started</h2>

        <div class="bg-slate-950/60 rounded-lg p-8 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
            <div class="m-10">
                <div class="grid grid-cols-2 gap-10">
                    <div>
                        <div class="p-6 bg-slate-950/40 rounded-lg border border-pink-500/10">
                            <h3 class="text-xl font-bold text-gray-200 flex items-center gap-3 mr-10">
                                <span class="material-symbols-rounded text-pink-500">tips_and_updates</span>
                                Requirements
                            </h3>
                            <div class="mt-6 ml-4 flex gap-5">
                                <div class="flex items-center gap-3 group">
                                    <span class="material-symbols-rounded text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500 transition-transform duration-200 group-hover:scale-110">check_circle</span>
                                    <div class="text-gray-300 group-hover:text-gray-200 transition-colors duration-200">PHP 8.2+</div>
                                </div>
                                <div class="flex items-center gap-3 group">
                                    <span class="material-symbols-rounded text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500 transition-transform duration-200 group-hover:scale-110">check_circle</span>
                                    <div class="text-gray-300 group-hover:text-gray-200 transition-colors duration-200">Laravel 11+</div>
                                </div>
                                <div class="flex items-center gap-3 group">
                                    <span class="material-symbols-rounded text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-violet-500 transition-transform duration-200 group-hover:scale-110">check_circle</span>
                                    <div class="text-gray-300 group-hover:text-gray-200 transition-colors duration-200">Filament 3.1</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-950/40 rounded-lg p-6 border border-pink-500/10 mt-5">
                            <h3 class="text-xl font-bold text-gray-200 mb-4 flex items-center gap-3">
                                <span class="material-symbols-rounded text-pink-500">terminal</span>
                                1. Install via Composer
                            </h3>
                            <div class="bg-gray-900 p-4 rounded font-mono text-gray-300 relative group">
                                <code id="composer-command">composer require moox/core</code>
                                <button
                                    onclick="copyToClipboard('composer-command')"
                                    data-copy-target="composer-command"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <span class="material-symbols-rounded text-gray-400 hover:text-pink-500">content_copy</span>
                                </button>
                            </div>
                        </div>
                        <div class="bg-slate-950/40 rounded-lg p-6 border border-pink-500/10 mt-5">
                            <h3 class="text-xl font-bold text-gray-200 mb-4 flex items-center gap-3">
                                <span class="material-symbols-rounded text-pink-500">rocket_launch</span>
                                2. Run the Installer
                            </h3>
                            <div class="bg-gray-900 p-4 rounded font-mono text-gray-300 relative group">
                                <code id="artisan-command">php artisan moox:install</code>
                                <button
                                    onclick="copyToClipboard('artisan-command')"
                                    data-copy-target="artisan-command"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <span class="material-symbols-rounded text-gray-400 hover:text-pink-500">content_copy</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="bg-slate-950/40 rounded-lg p-6 border border-pink-500/10">
                            <h3 class="text-xl font-bold text-gray-200 mb-8 flex items-center gap-3">
                                    <span class="material-symbols-rounded text-pink-500">check_circle</span>
                                    3. Choose Packages
                                </h3>
                                <img src="https://raw.githubusercontent.com/mooxphp/moox/refs/heads/main/packages/devlink/screenshots/devlink-status.jpg" alt="Installer" class="w-full rounded">
                            </div>
                        </div>
                    </div>

            </div>
        </div>
    </div>

    <div class="h-20"></div>

    <div class="mt-20" id="packages">

        <h2 class="text-3xl font-bold text-center gradient-text-vibrant mt-20 mb-10">
            Popular Packages
        </h2>

        <div class="flex gap-10">
            <div class="w-2/3">
                <div class="space-y-6 mt-10 mb-20">
                    <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                        <div class="flex gap-6">
                            <div class="w-3/5">
                                <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Core" class="w-full rounded">
                            </div>
                            <div class="w-2/5">
                                <h2 class="text-gray-200 text-2xl font-bold mb-5">
                                    Moox Core
                                    <span class="bg-pink-500/20 text-pink-200 text-sm px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.33</span>
                                </h2>
                                <p class="text-gray-300 mb-5">The foundation of all Moox packages with essential features and utilities.</p>
                                <div class="flex gap-5">
                                    <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                                    <a href="#" class="text-gray-200 h-8"><img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8"></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                        <div class="flex gap-6">
                            <div class="w-3/5">
                                <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Builder" class="w-full rounded">
                            </div>
                            <div class="w-2/5">
                                <h2 class="text-gray-200 text-2xl font-bold mb-5">
                                    Moox Builder
                                    <span class="bg-pink-500/20 text-pink-200 text-sm px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">2.1.0</span>
                                </h2>
                                <p class="text-gray-300 mb-5">Advanced page builder with drag-and-drop interface for Laravel applications.</p>
                                <div class="flex gap-5">
                                    <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                                    <a href="#" class="text-gray-200 h-8"><img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8"></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                        <div class="flex gap-6">
                            <div class="w-3/5">
                                <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Content" class="w-full rounded">
                            </div>
                            <div class="w-2/5">
                                <h2 class="text-gray-200 text-2xl font-bold mb-5">
                                    Moox Content
                                    <span class="bg-pink-500/20 text-pink-200 text-sm px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">3.0.7</span>
                                </h2>
                                <p class="text-gray-300 mb-5">Powerful content management system with advanced publishing workflows.</p>
                                <div class="flex gap-5">
                                    <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                                    <a href="#" class="text-gray-200 h-8"><img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8"></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-1/3">
                <div class="sticky top-10">
                    <img src="{{ asset('web/80-box.png') }}" alt="Delivery Robot" class="w-full px-10">
                </div>
            </div>
        </div>
    </div>


    <div class="flex justify-center gap-5 mt-10 mb-10">
        <a href="packages" class="relative px-4 py-2 rounded flex items-center gap-2 group">
            <div class="absolute inset-0 rounded bg-gradient-to-r from-pink-600 via-purple-600 to-violet-600"></div>
            <div class="absolute inset-[1px] rounded bg-indigo-950/90"></div>
            <span class="material-symbols-rounded text-md relative z-10 text-gray-200">deployed_code</span>
            <span class="relative z-10 text-gray-200">Find your package</span>
        </a>
    </div>

    <div class="h-10"></div>

    <div class="mt-20" id="docs">
        <h2 class="text-3xl font-bold text-center gradient-text-vibrant mb-20">
            Help us grow Moox
        </h2>

        <div class="bg-slate-950/60 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
            <p class="text-gray-300">
                We are working on the contribution guide for Moox.
                Please use the README files on GitHub for now and check back soon.
                <br>
                <br>
                There are many things to do. Coding, documentation, translations,
                and if you want to help, you can do so by sponsoring Moox.
            </p>

        </div>
    </div>

    <div class="h-20"></div>

    <div class="mt-20" id="sponsors">
        <h2 class="text-3xl font-bold text-center gradient-text-vibrant mb-20">
            Say hello to our Sponsors
        </h2>

        <div class="pt-10 pb-10 bg-white/80 rounded-lg p-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">

            <div class="flex justify-center items-center gap-20">

                <a href="https://www.heco.de" target="_blank">
                    <img src="{{ asset('web/heco-logo.png') }}" alt="heco">
                </a>

                <a href="https://www.alf-drollinger.com" target="_blank">
                    <img src="{{ asset('web/alf-drollinger-logo.png') }}" alt="Alf Drollinger" class="w-50">
                </a>

                <a href="mailto:hello@moox.org?subject=Moox%20Sponsor" target="_blank" class="text-violet-600 border-2 px-3 py-2 border-pink-400 rounded-lg">
                    Your Company
                </a>

            </div>

        </div>
    </div>

    <div class="h-10"></div>


    <div id="tsparticles" class="absolute inset-0 -z-10"></div>
</div>
@endsection
