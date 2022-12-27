<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Enums\Gender;
use App\Http\Resources\HeroCardResource;
use App\Jobs\SendEmail;
use App\Mail\EmailChanged;
use App\Mail\ResetPassword;
use App\Mail\SendOneTimePassword;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password as PasswordValidator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Password;
use App\Mail\Welcome;
use Illuminate\Support\Carbon;
use stdClass;

class AuthController extends Controller
{

    private $throttle = 60; // Đếm 60s giữa mỗi lần gửi email
    private $expires = 10 * 60; // OTP hết hạn sau 10 phút

    /**
     * Return authenticated user information.
     *
     * @return \Illuminate\Http\Response
     */
    public function show() {
        return response()->json([
            'user' => new UserResource(auth()->user())
        ]);
    }

    /**
     * Get user's owned cards.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCards() {
        $id_array = DB::table('order_details')->join('orders', 'orders.id', '=', 'order_details.order_id')
                        ->where('orders.user_id', auth()->user()->id)
                        ->select('product_id', DB::raw('SUM(quantity) AS quantity, MAX(name) AS name'))
                        ->groupBy('product_id')->get();

        if(count($id_array) > 0) {
            foreach ($id_array as $key => $value) {
                $products[$key] = Product::find($value->product_id);
                $products[$key]->unit = $value->quantity;
            }

            return HeroCardResource::collection(collect($products)->sortBy('id')->values());
        } else {
            return response()->json([
                'data' => []
            ]);
        }
    }

    /**
     * Sign up a user with username, email and password.
     *
     * @param  \App\Http\Requests\AuthRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function signup(AuthRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        $user->cart()->create(['user_id' => $user->id]);

        SendEmail::dispatch(new Welcome($user), $user);

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Sign in a user with an email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function signin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6|max:100'
        ]);

        if(!Auth::attempt($validated))
            return response()->json([
                'errors' => ['unauthorized' => 'Invalid login credentials.']
            ], 401);

        return response()->json([
            'access_token' => auth()->user()->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource(auth()->user())
        ]);
    }

    /**
     * Update a user information.
     *
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $validated = request()->validate([
            'username' => 'required|alpha_dash|max:40',
            'fullname' =>  'nullable|string|max:100',
            'address' =>  'nullable|string|max:255',
            'phone' =>  'nullable|digits:10',
            'birthday' =>  'nullable|date|date_format:Y-m-d',
            'gender' =>  ['nullable', new Enum(Gender::class)]
        ]);

        try {
            auth()->user()->update($validated);

            return response()->json([
                'message' => 'Your profile has been updated successfully.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ops, something went wrong. Please try again later.'
            ], 409);
        }
    }

    /**
     * Change User's Password.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword()
    {
        $validated = request()->validate([
            'old_password' => ['bail', 'current_password:sanctum', 'required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100'],
            'new_password' => ['bail', 'confirmed', 'different:old_password', 'required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100'],
            'new_password_confirmation' => ['bail', 'required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100']
        ]);

        try {
            auth()->user()->fill([
                'password' => bcrypt($validated['new_password'])
            ])->save();

            return response()->json([
                'message' => 'Your password was successfully changed.'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Ops, something went wrong. Please try again later.'
            ], 409);
        }
    }

    /**
     * Handle User's Request Forgot Password.
     *
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword()
    {
        request()->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            request()->only('email'),
            function($user, $token) {
                SendEmail::dispatch(new ResetPassword($user, $token), $user);
            }
        );

        $statusCode = $status === Password::INVALID_USER ? 404 :
                     ($status === Password::RESET_THROTTLED ? 409 : 200);

        return response()->json([
            'message' => __($status)
        ], $statusCode);
    }

    /**
     * Handle User's Request To Reset Password.
     * vendor\laravel\framework\src\Illuminate\Auth\Passwords\PasswordBroker.php
     *
     * @return \Illuminate\Http\Response
     */
    public function resetPassword()
    {
        request()->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password'=> ['required', 'string', PasswordValidator::min(6)->letters()->numbers(), 'max:100', 'confirmed'],
        ]);

        $status = Password::reset(
            request()->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();
            }
        );

        $statusCode = $status === Password::PASSWORD_RESET ? 200 : 404;

        return response()->json([
            'message' => __($status)
        ], $statusCode);
    }

    /**
     * Sign out a user from a current device.
     *
     * @return \Illuminate\Http\Response
     */
    public function signout()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token Revoked'
        ]);
    }

    /* Send OTP to current Email */
    public function sendOtpToCurrentEmail()
    {
        $record = DB::table('email_changes')->where('email', auth()->user()->email)->first();

        $otp = rand(100000, 999999);

        $email = [
            'subject' => 'Verifying your email address',
            'to' => auth()->user()->email,
            'body' => [
                'user' => auth()->user(),
                'title' => 'Change Your Email',
                'pre_action' => 'To complete your request to',
                'action' => 'Change Jenova Store Email',
            ],
            'otp' => $otp
        ];

        /* Nếu chưa có record => tạo mới */
        if(collect($record)->isEmpty()){
            DB::table('email_changes')->insert([
                'email' => auth()->user()->email,
                'one_time_password' => $otp,
                'created_at' => now(),
                'otp_created_at' => now()
            ]);

            return $this->sendEmail(new SendOneTimePassword($email['subject'], $email['body'], $email['otp']), $email['to']);
        /* Nếu đã có record */
        } else {
            $data = [
                'one_time_password' => $otp,
                'created_at' => now(),
                'otp_created_at' => now()
            ];

            return $this->sendOtp($data, $record->otp_created_at, $email);
        }
    }

    /* Verify current email with otp */
    public function verifyCurrentEmailCode()
    {
        request()->validate([
            'otp' => 'required|digits:6'
        ]);

        $record = DB::table('email_changes')->where('email', auth()->user()->email)->first();

        $error = $this->handleRecord($record, request('otp'));

        if($error)
            return $error;

        /* Nếu đúng => xóa mã rồi trả về thông báo */
        DB::table('email_changes')->where('email', auth()->user()->email)
                                  ->update([
                                      'one_time_password' => null,
                                      'verified_owner' => true,
                                      'otp_created_at' => null
                                  ]);

        return response()->json([
            'message' => 'Verification successful!'
        ]);
    }

    /* Gửi OTP tới email mới sau khi đã xác nhận email cũ */
    public function sendOtpToNewEmail()
    {
        request()->validate([
            'new_email' => 'required|email|unique:users,email'
        ]);

        $record = DB::table('email_changes')->where('email', auth()->user()->email)->first();

        $error = $this->handleRecord($record, null, true);

        if($error)
            return $error;

        $otp = rand(100000, 999999);

        $email = [
            'subject' => 'Verifying your email address',
            'to' => request('new_email'),
            'body' => [
                'user' => auth()->user(),
                'title' => 'Change Your Email',
                'pre_action' => 'In order to change your login email from',
                'action' => auth()->user()->email,
            ],
            'otp' => $otp
        ];

        $data = [
            'new_email' => request('new_email'),
            'one_time_password' => $otp,
            'created_at' => now(),
            'otp_created_at' => now()
        ];

        return $this->sendOtp($data, $record->otp_created_at, $email);
    }

    /* Verify new email with otp and change email */
    public function updateNewEmail()
    {
        request()->validate([
            'otp' => 'required|digits:6',
            'new_email' => 'required|email|unique:users,email'
        ]);

        $record = DB::table('email_changes')->where('email', auth()->user()->email)->first();

        $error = $this->handleRecord($record, request('otp'), true, request('new_email'));

        if($error)
            return $error;

        DB::table('email_changes')->where('email', auth()->user()->email)->delete();

        $old_user = new stdClass();
        $old_user->email = auth()->user()->email;

        DB::table('users')->where('email', auth()->user()->email)
                                ->update([
                                    'email' => request('new_email')
                                ]);

        SendEmail::dispatch(new EmailChanged('E-mail address is successfully changed', auth()->user()), $old_user);

        return response()->json([
            'message' => 'Your email has been changed successfully.'
        ]);
    }

    /**
     * Determine if the otp was recently created.
     *
     * @param  string  $createdAt
     * @param  integer  $throttle
     * @return bool
     */
    protected function otpRecentlyCreated($created_at, $throttle)
    {
        return $created_at == null ? false : Carbon::parse($created_at)->addSeconds($throttle)->isFuture();
    }

    /**
     * Determine if the otp has expired.
     *
     * @param  string  $createdAt
     * @param  integer  $expires
     * @return bool
     */
    protected function otpExpired($created_at, $expires)
    {
        return Carbon::parse($created_at)->addSeconds($expires)->isPast();
    }

    /**
     * Check throttle and send otp through the email.
     *
     * @param  array  $data
     * @param  string  $otp_created_at
     * @param array $email
     * @return \Illuminate\Http\Response
     */
    protected function sendOtp($data, $otp_created_at, $email)
    {
        /* Nếu yêu cầu gửi OTP tiếp theo trước 60s, báo chờ */
        if($this->otpRecentlyCreated($otp_created_at, $this->throttle))
            return response()->json([
                'message' => 'Please wait ' . $this->throttle . 's before retrying.'
            ], 425);
        else
            DB::table('email_changes')->where('email', auth()->user()->email)->update($data);

        return $this->sendEmail(new SendOneTimePassword($email['subject'], $email['body'], $email['otp']), $email['to']);
    }

    /* Send Email */
    protected function sendEmail($mail, $to)
    {
        $user = new stdClass();
        $user->email = $to;

        SendEmail::dispatch($mail, $user);

        return response()->json([
            'message' => 'Email sent successfully.'
        ]);
    }

    /**
     * Xử lý các trường hợp của record trong table email_changes
     *
     * @param  object  $record
     * @return \Illuminate\Http\Response
     */
    protected function handleRecord($record, $otp = null, $need_verify_owner = null, $new_email = null)
    {
        /* Nếu không có record hoặc otp hết hạn quá 10p */
        if(collect($record)->isEmpty() || $this->otpExpired($record->created_at, $this->expires)) {
            /* Xóa record hết hạn */
            DB::table('email_changes')->where('email', auth()->user()->email)->delete();

            return response()->json([
                "message" => "Your one time password (OTP) has expired. Please return and try again.",
            ], 410);
        }

        /* Nếu chưa verify */
        if(!$record->verified_owner && $need_verify_owner != null)
            return response()->json([
                "message" => "You have not verified ownership of your current email. Please go back and try again.",
            ], 410);

        /* Nếu Email không đúng */
        if($new_email != null && !is_null($record->new_email) && $record->new_email != $new_email)
            return response()->json([
                "message" => "Email does not match that which you registered before.",
                "errors" => [
                    "otp" => [
                        "Email does not match that which you registered before."
                    ]
                ]
            ], 422);

        /* Nếu OTP không đúng */
        if($otp != null && $record->one_time_password != $otp)
            return response()->json([
                "message" => "Invalid verification code.",
                "errors" => [
                    "otp" => [
                        "Invalid verification code."
                    ]
                ]
            ], 422);

        return false;
    }
}
