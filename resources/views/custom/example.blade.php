@extends('layouts.guest')

@section('content')

    <div class="flex flex-col justify-center p-5">
        <h1 class="m-10 font-sans text-4xl text-center">
            <b>Need custom views?</b>
        </h1>

        <p class="m-5 text-lg leading-6 text-center">
            To develop your own package(s) in <b>/_custom</b><br>
            copy resources/views/custom/example.blade.php<br>
            and replace the entry for <b>CUSTOM_VIEWS</b> in<br>
            the .env-file.<br><br>
            Now you can load your own views, without<br>
            breaking the dev app for other developers.<br><br>
            See <b>/_custom/README.md</b> for details.<br>
        </p>
    </div>

@stop
