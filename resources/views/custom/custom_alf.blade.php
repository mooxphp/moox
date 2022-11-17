@extends('layouts.pages')

@section('content')
        <head>
            <nav class="flex justify-between m-8">
                <img  src="/build/assets/images/logo.png">
                <div class="flex items-center text-3xl">
                    <a class="px-4 font-normal" href="/">Home</a>
                    <a class="px-4 font-normal" href="/packages">Packages</a>
                    <a class="px-4 font-normal" href="">Workspace</a>
                    <a href="https://github.com/usetall/tallui">
                        <img class="px-4 " src="{{ asset('build/assets/images/icons/git.svg') }} " alt="git">

                    </a>
                </div>
            </nav>
        </head>
        <div class="flex flex-col justify-center">
            <h1 class="m-12 font-sans text-4xl text-center">
                The <b>UI</b> for Laravel<br>
                and the <b>TALL</b>-Stack
            </h1>

            <p class="m-12 leading-6 text-center">
                TallUI is currently under active development.<br>
                Our first components will be available shortly.
            </p>
        </div>
@stop
