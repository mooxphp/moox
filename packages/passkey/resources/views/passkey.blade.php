@extends('passkey::layout')

@section('content')

<div x-data="authForm" x-cloak>
    <div x-show="!browserSupported">
        <div>
            <div>

                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M8.485 3.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 3.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <h3>
                    Your browser isn't supported!
                </h3>
                <div>
                    <p>
                        That's sort of a bummer, sorry. Maybe you have access to a browser that does though,
                        <a target="_blank" href="https://caniuse.com/?search=webauthn">
                            check and see
                        </a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div x-show="browserSupported">
        <form @submit.prevent="submit">
            <h2>
                Sign In or Register
            </h2>
            <div x-show="mode === 'login'">
                <label for="email">Username</label>
                <div>
                    <input x-model="username" type="text" id="username" v-model="username" autocomplete="username"
                        required autocapitalize="off" />

                    <div x-show="error">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                </div>
                <p x-show="error" x-text="error"></p>
            </div>

            <div x-show="mode === 'confirmRegistration'">
                <p>No account exists for "<span x-text="username"></span>".
                <p>Do you want to create a new account?</p>
                <p>
                    <a href="#" @click.prevent="mode = 'login'">Cancel</a>
                </p>
            </div>

            <div>
                <button type="submit" x-text="mode === 'confirmRegistration' ? 'Register' : 'Continue'">
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
