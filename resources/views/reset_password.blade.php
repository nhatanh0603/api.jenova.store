<table style="max-width: 600px; padding: 20px; margin: auto; border: 1px solid #e5e5e5; border-radius: 10px; color: rgb(14, 13, 13); font-size: 16px; font-family: 'Nunito', sans-serif;">
    <tr>
        <td style="font-size: 25px; font-weight: 900; color: #35527d; text-transform: uppercase; padding-bottom: 20px; text-align: center;">

            <img style="vertical-align: middle; padding-bottom: 1px;" src="https://api.jenova.store/images/logos/jenova-store-logo-symbol.png" alt="Jenova Store Logo Symbol" width="30" height="30">

            <span style="vertical-align: middle;">{{ Lang::get('Reset Your Password') }}</span>
        </td>
    </tr>

    <tr style="text-indent: 15px;">
        <td>
            <div style="margin-bottom: 12px; font-size: 17px;">Hi
                <span style="color: #c41616; font-weight: 700;">
                    {{ $user->username. ',' }}
                </span>
            </div>

            <div>{{ Lang::get('You are receiving this email because we received a password reset request for your account. Click on this link to create a new password:') }}</div>

            <div style="margin: 30px 0; text-align: center;">
                <a style="text-decoration: none; color: white; padding: 10px 20px; background-color: #3c81f3; font-weight: 900; text-transform: uppercase; border-radius: 999px;"
                    href="https://jenova.store/reset-password/{{ $token }}"
                >{{ Lang::get('Set a new password') }}</a>
            </div>

            <div>{{ Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]) }}</div>

            <div>{{ Lang::get('If you did not request a password reset, no further action is required.') }}</div>
        </td>
    </tr>
</table>

<table style="max-width: 600px; margin: auto; margin-top: 50px;">
    <tr>
        <td style="text-align: center;">
            <img style="background-color: #1a1919;
                        display: block;
                        margin-bottom: 20px;
                        padding: 10px 20px;
                        border-radius: 999px;"
                src="https://api.jenova.store/images/logos/jenova-store-logo-full.png"
                alt="Jenova Store Logo Full" height="30"
            >

            <img src="https://api.jenova.store/images/misc/zalo.jpg" alt="Zalo QR" width="200">
        </td>
    </tr>
</table>
