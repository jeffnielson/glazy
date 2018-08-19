@component('mail::message')
# New Password

Your new Glazy account's password has been reset.

Please login to Glazy using this password:

**{!! $password !!}**

Once you have logged in, please change your password.

@component('mail::button', ['url' => 'https://glazy.org/login'])
    Login to Glazy
@endcomponent

@component('mail::button', ['url' => 'https://glazy.org/settings'])
    Change Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
