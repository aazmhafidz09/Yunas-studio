<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Replace the default password (password = username / phone number) with a new one.
     * No current password is asked, because it is only allowed while the default is still in use.
     */
    public function updateDefaultPassword(Request $request)
    {
        $user = $request->user();

        if (!Hash::check((string) $user->username, $user->password)) {
            $request->session()->forget('suggest_password_change');

            return back()->with('error', 'Password Anda sudah pernah diganti. Gunakan menu Change Password untuk menggantinya lagi.');
        }

        $request->validateWithBag('defaultPassword', [
            'new_password' => ['required', 'string', 'min:8', 'confirmed', Rule::notIn([(string) $user->username])],
        ], [
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak sama.',
            'new_password.not_in' => 'Password baru tidak boleh sama dengan nomor HP / username.',
        ]);

        $user->password = Hash::make($request->new_password);
        $user->save();

        $request->session()->forget('suggest_password_change');

        return back()->with('success', 'Password berhasil diganti. Gunakan password baru saat login berikutnya.');
    }
}
