@extends('layouts.guest')

@section('content')

    <div class="flex flex-col justify-center p-5">
        <h1 class="m-10 font-sans text-4xl text-center">
            <b>Custom</b> views<br>
        </h1>

        <br>

        <div class="flex justify-center m-5">

            @foreach (config('tallui.custom_views') as $key => $value)
                <a
                    href="custom/{{ $value }}"
                    class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded"
                >{{ $value }}</a>
            @endforeach

        </div>

        <br>

        <p class="m-5 text-lg leading-6 text-center">
            Need custom views?<br>
            See the <a href="custom/example/">example</a>
        </p>
    </div>

@stop
