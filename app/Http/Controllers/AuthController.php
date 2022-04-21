<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Library\HttpStatusCode;
use App\Library\Messages;
use App\Library\TypeTokens;
use App\Library\Utils;
use App\Mail\UserForgotPassword;
use App\Mail\VerifyUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest
    $request)
    {
        $request->validated();
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
        ]);

        return response()->json($user);
    }

    public function login(LoginRequest $request)
    {
        $request->validated();
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return Utils::generateResponse(false, Messages::MSG_AUTH["credentials_incorrect"], HttpStatusCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        return Utils::generateResponse(true, [
            "accessToken" => $user->createToken(TypeTokens::ACCESS_TOKEN)->plainTextToken,
        ], HttpStatusCode::HTTP_OK);
    }

    public function verify($token)
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return Utils::generateResponse(false, Messages::MSG_AUTH["token_not_found"], HttpStatusCode::HTTP_NOT_FOUND);
        }

        $user = User::find($accessToken->tokenable_id);

        if (!$user) return Utils::generateResponse(false, Messages::MSG_AUTH["account_not_found"], HttpStatusCode::HTTP_NOT_FOUND);

        if (isset($user->email_verified_at))
            return Utils::generateResponse(false, Messages::MSG_AUTH["email_verified"], HttpStatusCode::HTTP_NOT_IMPLEMENTED);

        $expiration = config("sanctum.expiration-verify-email");
        $isVerifyEmail = $accessToken->name === TypeTokens::VERIFY_EMAIL_TOKEN;

        $isValid =
            (!$expiration || $accessToken->created_at->gt(now()->subMinutes($expiration))) && $isVerifyEmail;

        if (!$isValid) return Utils::generateResponse(false, HttpStatusCode::$statusTexts[HttpStatusCode::HTTP_BAD_REQUEST], HttpStatusCode::HTTP_BAD_REQUEST);
        $user->email_verified_at = now();
        $user->save();
        $user->tokens()->where('id', $accessToken->id)->delete();

        return Utils::generateResponse(true, Messages::MSG_AUTH["verify_email_success"], HttpStatusCode::HTTP_OK);
    }

    public function sendVerify(Request $request)
    {
        Mail::to($request->user())->send(new VerifyUser($request->user()->id, $request->user()->createToken(TypeTokens::VERIFY_EMAIL_TOKEN)->plainTextToken));
        return Utils::generateResponse(true, Messages::MSG_AUTH["sent_email"], HttpStatusCode::HTTP_OK);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $request->validated();
        $validated = $request->safe()->only(['email']);

        $user = User::where("email", $validated["email"])->first();

        Mail::to($user)->send(new UserForgotPassword($user->id, $user->createToken(TypeTokens::RESET_PASSWORD_TOKEN)->plainTextToken));
        return Utils::generateResponse(true, Messages::MSG_AUTH["send_email_forgot_pass_success"], HttpStatusCode::HTTP_OK);
    }

    public function resetPassword(ResetPasswordRequest $request, $token)
    {
        $request->validated();

        $validated = $request->safe()->only(['password']);

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return Utils::generateResponse(false, Messages::MSG_AUTH["token_not_found"], HttpStatusCode::HTTP_NOT_FOUND);
        }

        $user = User::find($accessToken->tokenable_id);

        if (!$user) return Utils::generateResponse(false, Messages::MSG_AUTH["account_not_found"], HttpStatusCode::HTTP_NOT_FOUND);

        $expiration = config("sanctum.expiration-reset-pass");
        $isForgotPassword = $accessToken->name === TypeTokens::RESET_PASSWORD_TOKEN;

        $isValid =
            (!$expiration || $accessToken->created_at->gt(now()->subMinutes($expiration))) && $isForgotPassword;

        if (!$isValid) return Utils::generateResponse(false, HttpStatusCode::$statusTexts[HttpStatusCode::HTTP_BAD_REQUEST], HttpStatusCode::HTTP_BAD_REQUEST);

        $user->password = Hash::make($validated["password"]);
        $user->save();
        $user->tokens()->where('id', $accessToken->id)->delete();

        return Utils::generateResponse(true, Messages::MSG_AUTH["reset_pass_success"], HttpStatusCode::HTTP_OK);
    }
}
