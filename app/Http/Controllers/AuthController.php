<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        if (Auth::attempt(['email' => 'user@test.com', 'password' => Hash::make('test')]))
        {
            return 'Logged in!';
        }
    }
}
