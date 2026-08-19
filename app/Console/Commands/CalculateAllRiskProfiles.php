<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StudentRiskProfileService;
use Illuminate\Console\Command;

class CalculateAllRiskProfiles extends Command
{
    protected $signature = 'spk:calculate-risks {--user_id= : Calculate for specific user only}';

    protected $description = 'Calculate risk profiles for all students';

    public function handle(StudentRiskProfileService $service): int
    {
        if ($this->option('user_id')) {
            $user = User::find($this->option('user_id'));
            if (!$user) {
                $this->error('User not found');
                return 1;
            }
            $service->calculateRiskProfile($user);
            $this->info("Risk profile calculated for user {$user->name}");
            return 0;
        }

        $profiles = $service->calculateAllRiskProfiles();
        $this->info('Risk profiles calculated for ' . count($profiles) . ' students using WASPAS');
        return 0;
    }
}
