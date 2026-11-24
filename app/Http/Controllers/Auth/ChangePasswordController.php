<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.change-password');
    }

    public function store(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your password has been changed.');
    }
}
