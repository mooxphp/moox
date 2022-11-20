@extends('layouts.guest')

@section('content')

    <div class="flex flex-col justify-center p-5">
        <h1 class="m-10 font-sans text-4xl text-center">
            The <b>UI</b> for Laravel<br>
            and the <b>TALL</b>-Stack
        </h1>

        <p class="m-5 text-lg leading-6 text-center">
            Welcome to the TallUI DevApp.<br>
            Have a nice dev today!
        </p>

        <div class="flex justify-center m-5">

        @if(view()->exists('custom.kim'))

            <a href="custom/kim" class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">Kim</a>

        @endif

        @if(view()->exists('custom.reinhold'))

            <a href="custom/reinhold" class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">Reinhold</a>

        @endif

        @if(view()->exists('custom.alf'))

            <a href="custom/alf" class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">Alf</a>

        @endif

        </div>
    </div>

@stop
