<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateUser
{
    public function handle(User $user, array $data): User
    {
        if (($data['password'] ?? '') === '') {
            unset($data['password']);
        }

        $user->update($data);

        return $user->refresh();
    }
}
