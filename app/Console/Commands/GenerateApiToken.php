<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate-token {user_id} {name=ExternalApp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a personal access token for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $name = $this->argument('name');

        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $token = $user->createToken($name)->plainTextToken;

        $this->info("Token generated successfully for {$user->name} ({$user->email}):");
        $this->line($token);

        return 0;
    }
}
