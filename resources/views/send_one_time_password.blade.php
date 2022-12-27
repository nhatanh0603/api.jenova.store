<table style="max-width: 600px; padding: 20px; margin: auto; border: 1px solid #e5e5e5; border-radius: 10px; color: rgb(14, 13, 13); font-size: 16px; font-family: 'Nunito', sans-serif;">
    <tr>
        <th style="font-size: 25px; text-transform: uppercase; font-weight: 800; color: #596dff; padding-bottom: 10px; border-bottom: 1px solid #e5e5e5;">
            {{ $body['title'] }}
        </th>
    </tr>

    <tr>
        <td style="padding-top: 20px;">Hi <span style="color: #596dff; font-weight: 700;">
            {{ $body['user']->username }}</span>,
        </td>
    </tr>

    <tr>
        <td>{{ $body['pre_action']}} <span style="font-weight: 600;">{{ $body['action'] }}</span>, please enter the 6-digit code on the <span style="font-style: italic;">One Time Password Field</span>:</td>
    </tr>

    <tr>
        <td style="font-size: 24px; font-weight: 700; color: #00a700; text-align: center; padding: 15px 0;">
            {{ $otp }}
        </td>
    </tr>

    <tr>
        <td style="color: red; font-weight: 700;">Do not share this code with anyone under any circumstances.</td>
    </tr>

    <tr>
        <td style="padding-top: 20px;">Best regards,</td>
    </tr>

    <tr>
        <td>Jenova Store</td>
    </tr>
</table>

@include('email_footer')
