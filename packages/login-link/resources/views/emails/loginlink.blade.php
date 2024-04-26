@component('mail::message')
# Login Link

Click the button below to log in to your account:

@component('mail::button', ['url' => $url])
Login
@endcomponent

This link will expire in {{ config('loginlink.expiration_time') }} hours.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
