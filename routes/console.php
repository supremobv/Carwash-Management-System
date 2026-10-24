<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('carwash:admin {email} {--name=Administrator}', function () {
    $email=$this->argument('email');
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->error('Enter a valid email.'); return 1; }
    if (\App\Models\User::where('email',$email)->exists()) { $this->error('This account already exists.'); return 1; }
    $password=$this->secret('Choose a password (at least 12 characters)');
    if (strlen($password??'')<12) { $this->error('Password is too short.'); return 1; }
    \App\Models\User::create(['name'=>$this->option('name'),'email'=>$email,'password'=>$password,'role'=>'admin','active'=>true]);
    $this->info('Administrator created. Sign in with your chosen credentials.');
})->purpose('Create a secure administrator account');

Artisan::command('carwash:demo', function () {
    if (!app()->environment('local')) { $this->error('Demo setup is available only in APP_ENV=local.'); return 1; }
    $this->call('db:seed',['--class'=>'DatabaseSeeder']);
    if (!\App\Models\User::where('role','admin')->exists()) {
        $password=\Illuminate\Support\Str::random(24);
        \App\Models\User::create(['name'=>'Demo Administrator','email'=>'admin@aquaflow.test','password'=>$password,'role'=>'admin','active'=>true]);
        \Illuminate\Support\Facades\Storage::disk('local')->put('local-access.txt',"LOCAL DEMO ONLY\nURL: http://127.0.0.1:8000\nEmail: admin@aquaflow.test\nPassword: ".$password."\n\nChange the password from My account after signing in. Delete this file afterward.\n");
        $this->info('Random login credentials saved to storage/app/private/local-access.txt');
    }
    $this->call('db:seed',['--class'=>'DemoSeeder']);
})->purpose('Add fictional local demo data and a randomly secured administrator');
