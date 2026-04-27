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

    /**
     * Update the given user's profile fields.
     *
     * Only the keys present in $data are written; omitted fields are left unchanged.
     *
     * @param  User                  $user
     * @param  array<string, mixed>  $data  Subset of: name, mobile, gender, dob.
     * @return User                         The same model instance after the update.
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }
}
