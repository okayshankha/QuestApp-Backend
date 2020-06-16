<?php

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'user_id' => sha1('User' . str_random(8)),
            'name' => 'Super Admin',
            'email' => config('QuestApp.System.super_admin_email'),
            'password' => bcrypt('12345'),
            'active' => true,
            'role' => config('QuestApp.UserLevels.sa')
        ]);
    }
}
