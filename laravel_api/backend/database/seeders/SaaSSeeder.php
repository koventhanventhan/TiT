<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaaSSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = \App\Models\SubscriptionPlan::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth Plan',
                'monthly_price' => 2500,
                'yearly_price' => 25000,
                'max_students' => 500,
                'max_teachers' => 25,
                'features' => ['Zoom Integration', 'Grade Management', 'WhatsApp Alerts'],
            ]
        );

        $institute = \App\Models\Institute::updateOrCreate(
            ['slug' => 'main'],
            [
                'name' => 'TiT education main academy',
                'status' => 'active',
                'subscription_plan_id' => $plan->id,
            ]
        );

        // Updated for correct scope usage
        \App\Models\User::withoutGlobalScope(\App\Scopes\InstituteScope::class)
            ->whereNull('institute_id')
            ->update(['institute_id' => $institute->id]);

        // Create Super Admin if not exists
        \App\Models\User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'superadmin@lenova.lk'],
            [
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => \Illuminate\Support\Facades\Hash::make('superadmin123'),
                'institute_id' => null, // Super admins are global
            ]
        );
    }
}
