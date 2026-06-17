<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * Disabled: Registration is managed by admin via Settings page.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('login')->with('error', 'Pendaftaran akun baru tidak tersedia. Hubungi admin untuk mendapatkan akses.');
    }

    /**
     * Handle an incoming registration request.
     * Disabled: Registration is managed by admin via Settings page.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        abort(403, 'Pendaftaran akun baru tidak diizinkan. Akun hanya dapat dibuat oleh admin sistem.');
    }
}
