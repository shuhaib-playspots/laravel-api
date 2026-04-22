<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(string $name, string $email, string $password, string $mobile, string $gender, string $dob): User
    {
        return User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'mobile'   => $mobile,
            'gender'   => $gender,
            'dob'      => $dob,
        ]);
    }
}
