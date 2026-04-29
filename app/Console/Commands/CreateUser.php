<?php

namespace App\Console\Commands;

use App\Models\Organisation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'app:create-user
        {--name= : The user\'s full name}
        {--email= : The user\'s email address}
        {--password= : The user\'s password}
        {--org= : The organisation name}
        {--whatsapp= : The WhatsApp number (e.g. +27012345678)}';

    protected $description = 'Manually create a new user and organisation';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Full name');
        $email = $this->option('email') ?? $this->ask('Email address');
        $password = $this->option('password') ?? $this->secret('Password');
        $org = $this->option('org') ?? $this->ask('Organisation name');
        $whatsapp = $this->option('whatsapp') ?? $this->ask('WhatsApp number (e.g. +27012345678)');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'org', 'whatsapp'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'org' => ['required', 'string', 'max:255'],
                'whatsapp' => ['required', 'string', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            ],
            [
                'whatsapp.regex' => 'Please enter a valid phone number (e.g. +27 012 345 6789).',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($name, $email, $password, $org, $whatsapp) {
            $freePlan = Plan::query()->where('code', 'free')->first();

            $organisation = Organisation::query()->create([
                'name' => $org,
                'email' => $email,
                'currency' => 'ZAR',
            ]);

            return User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'organisation_id' => $organisation->id,
                'plan_id' => $freePlan?->id,
                'whatsapp_number' => preg_replace('/[^0-9+]/', '', $whatsapp),
                'whatsapp_enabled' => true,
            ]);
        });

        $this->info('User created successfully.');
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Organisation: {$user->organisation->name}");
        $this->line('Plan: '.($user->plan?->name ?? 'None'));

        return self::SUCCESS;
    }
}
