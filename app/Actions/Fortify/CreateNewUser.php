<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Organisation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'organisation_name' => ['required', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $freePlan = Plan::query()->where('code', 'free')->first();

            $organisation = Organisation::create([
                'name' => $input['organisation_name'],
                'email' => $input['email'],
                'currency' => 'ZAR',
            ]);

            return User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'organisation_id' => $organisation->id,
                'plan_id' => $freePlan?->id,
            ]);
        });
    }
}
