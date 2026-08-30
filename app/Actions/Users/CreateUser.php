<?php

namespace App\Actions\Users;

use App\Models\User;

class CreateUser
{
    public function handle(array $data): User
    {
        $data['email_verified_at'] = now();

        return User::create($data);
    }
}
