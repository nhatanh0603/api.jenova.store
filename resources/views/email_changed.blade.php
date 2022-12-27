<table style="max-width: 600px; padding: 20px; margin: auto; border: 1px solid #e5e5e5; border-radius: 10px; color: rgb(14, 13, 13); font-size: 16px; font-family: 'Nunito', sans-serif;">
    <tr>
        <th style="text-transform: uppercase; font-weight: 800; color: #3e67ff; padding-bottom: 15px; border-bottom: 1px solid #e5e5e5;">E-mail address is successfully changed</th>
    </tr>

    <tr>
        <td style="padding-top: 15px;">
            Hi <span style="color: #3e67ff; font-weight: 700;">{{ $user->username }}</span>,
        </td>
    </tr>

    <tr>
        <td style="padding-top: 10px;">Your E-mail address is successfully changed to <span>{{ $user->email }}</span>. Make sure to enter a new address when you next log in <span style="font-weight: 600;">Jenova Store</span>.</td>
    </tr>

    <tr>
        <td style="padding-top: 10px;">If you did not ask for change of address or if you have reason to believe that your account is in danger, please contact support <span style="font-weight: 600;">Jenova Store</span>, to get help immediately.</td>
    </tr>

    <tr>
        <td style="padding-top: 20px;">With thanks to the team</td>
    </tr>

    <tr>
        <td>Jenova Store</td>
    </tr>
</table>

@include('email_footer')
