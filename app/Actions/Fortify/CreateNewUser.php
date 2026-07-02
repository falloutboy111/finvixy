<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Jobs\PushSignupEvent;
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
            'whatsapp_number' => [
                'required',
                'string',
                'regex:/^\+?[0-9\s\-]{7,20}$/',
                function ($attribute, $value, $fail) {
                    $cleaned = preg_replace('/[^0-9+]/', '', $value);
                    if (User::where('whatsapp_number', $cleaned)->exists()) {
                        $fail('This WhatsApp number is already linked to another account.');
                    }
                },
            ],
        ], [
            'whatsapp_number.required' => 'A WhatsApp number is required so you can scan receipts.',
            'whatsapp_number.regex' => 'Please enter a valid phone number (e.g. +27 012 345 6789).',
        ])->validate();

        $user = DB::transaction(function () use ($input) {
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
                'whatsapp_number' => preg_replace('/[^0-9+]/', '', $input['whatsapp_number']),
                'whatsapp_enabled' => true,
            ]);
        });

        // PII-free signup notification to the Enclivix stats channel. Queued so a
        // CRM outage never blocks signup; failures are swallowed by the job.
        PushSignupEvent::dispatch();

        return $user;
    }
}
