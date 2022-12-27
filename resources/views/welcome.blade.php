<table style="max-width: 600px;
              margin: auto;
              background-color: #002332;
              color: white;
              padding: 20px 30px;
              font-size: 16px;
              font-family: 'Nunito', sans-serif;
              border-radius: 15px;"
>
    <tr>
        <th style="font-size: 28px; padding: 10px 0 25px; color: #90ff90;">
            Your registration was successful
        </th>
    </tr>

    <tr>
        <td>
            <p>Dear <span style="color: #ffaf4b; font-weight: 600;">{{ $user->username }}</span>,</p>

            <p>Welcome to Jenova Store!</p>

            <p>Thanks for registering with us. As a member of Jenova.store, you will be the first one to receive exclusive promotions and latest updates.</p>
        </td>
    </tr>

    <tr>
        <td style="padding: 20px; text-align: center;">
            <a style="color: white;
                      text-decoration: none;
                      border: 1px solid white;
                      padding: 10px 30px;
                      border-radius: 999px;
                      font-size: 17px;
                      font-weight: 700;
                      text-transform: uppercase;
                      background-color: #229322;"
                href="https://jenova.store/heroes"
            >
                Shopping Now
            </a>
        </td>
    </tr>

    <tr>
        <td>
            <img style="max-width: 600px;" src="https://api.jenova.store/images/misc/welcome.png" alt="Welcome">
        </td>
    </tr>
</table>

@include('email_footer')
