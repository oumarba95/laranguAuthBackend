@component('mail::message')
# Password reset

Please click on button to reset your password .

@component('mail::button', ['url' => 'http://localhost:4200/response-password-reset?token='.$token])
reset password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
