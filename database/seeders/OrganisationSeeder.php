<?php

namespace Database\Seeders;

use App\Models\Organisation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganisationSeeder extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::query()->where('code', 'free')->first();

        $organisation = Organisation::query()->firstOrCreate(
            ['name' => 'Demo Organisation'],
            [
                'email' => 'demo@finvixy.com',
                'currency' => 'ZAR',
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'demo@finvixy.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'organisation_id' => $organisation->id,
                'plan_id' => $freePlan?->id,
                'email_verified_at' => now(),
                'first_time_login' => false,
            ]
        );
    }
}
