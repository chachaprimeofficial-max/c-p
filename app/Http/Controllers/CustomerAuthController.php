<?php

namespace App\Http\Controllers;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class CustomerAuthController extends Controller
{
    public function showRegister(): View { return view('auth.customer-register'); }
    public function showLogin(): View { return view('auth.customer-login'); }
    public function showVerify(Request $request): View { return view('auth.verify-email', ['email' => (string) $request->query('email', session('pending_email', ''))]); }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => Hash::make($data['password']), 'is_admin' => false]);
        try {
            $this->sendOtp($user->email);
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('account.verify', ['email' => $user->email])->with('status', 'Your account was created, but the verification email could not be sent. Please use resend after checking email configuration.');
        }
        return redirect()->route('account.verify', ['email' => $user->email])->with('status', 'We sent a 6-digit verification code to your email address.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $credentials['is_admin'] = false;
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password could not be verified.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $user = $request->user();
        if (!$user->email_verified_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            session(['pending_email' => $user->email]);
            return redirect()->route('account.verify', ['email' => $user->email])->with('status', 'Please verify your email before signing in.');
        }
        return redirect()->intended(route('home'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $otp = EmailOtp::where('email', strtolower($data['email']))->latest()->first();
        if (!$otp || $otp->expires_at->isPast() || $otp->attempts >= 5 || !Hash::check($data['code'], $otp->code_hash)) {
            if ($otp && !$otp->expires_at->isPast()) $otp->increment('attempts');
            return back()->withErrors(['code' => 'The code is invalid or expired. Request a new code and try again.'])->withInput(['email' => $data['email']]);
        }
        $user = User::where('email', strtolower($data['email']))->where('is_admin', false)->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        EmailOtp::where('email', $user->email)->delete();
        session()->forget('pending_email');
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('home')->with('status', 'Email verified. Welcome to ChachaPrime.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($data['email']);
        $key = 'email-otp:'.$request->ip().':'.$email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Too many requests. Please wait before requesting another code.'])->withInput();
        }
        RateLimiter::hit($key, 60);
        if (!User::where('email', $email)->where('is_admin', false)->exists()) {
            return back()->withErrors(['email' => 'If the account exists, a verification code can be sent.'])->withInput();
        }
        try {
            $this->sendOtp($email);
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['email' => 'Email delivery failed. Check the mail settings and try again.'])->withInput();
        }
        return redirect()->route('account.verify', ['email' => $email])->with('status', 'A new verification code has been sent.');
    }

    public function logout(Request $request): RedirectResponse
    {
        abort_if($request->user()?->is_admin, 403);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    private function sendOtp(string $email): void
    {
        $email = strtolower($email);
        $last = EmailOtp::where('email', $email)->latest()->first();
        if ($last && $last->last_sent_at && $last->last_sent_at->diffInSeconds(now()) < 45) {
            throw new \RuntimeException('Please wait before requesting another code.');
        }
        $code = (string) random_int(100000, 999999);
        EmailOtp::where('email', $email)->delete();
        EmailOtp::create(['email' => $email, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10), 'attempts' => 0, 'last_sent_at' => now()]);
        Mail::raw("Your ChachaPrime verification code is: {$code}\n\nThis code expires in 10 minutes. If you did not request it, you can ignore this email.", function ($message) use ($email) {
            $message->to($email)->subject('Your ChachaPrime verification code');
        });
    }
}
