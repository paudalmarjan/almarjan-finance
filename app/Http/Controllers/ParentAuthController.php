<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;

class ParentAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('parent_student_id')) {
            return redirect()->route('wali.dashboard');
        }
        return view('wali.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nis' => 'required|string',
            'pin' => 'required|string',
        ]);

        $student = Student::where('nis', $request->nis)->first();

        if ($student && Hash::check($request->pin, $student->pin)) {
            session(['parent_student_id' => $student->id]);
            return redirect()->route('wali.dashboard')->with('success', 'Selamat datang di Portal Wali Murid!');
        }

        return back()->with('error', 'NIS atau PIN yang Anda masukkan salah. PIN standar adalah 123456.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('parent_student_id');
        return redirect()->route('wali.login')->with('success', 'Anda telah berhasil keluar.');
    }
}
