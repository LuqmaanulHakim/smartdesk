<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'agent@smartdesk.com'],
            [
                'name' => 'SmartDesk Agent',
                'password' => Hash::make('password123'),
            ]
        );
    }
}