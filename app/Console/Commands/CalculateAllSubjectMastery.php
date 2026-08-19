<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SubjectMasteryService;
use Illuminate\Console\Command;

class CalculateAllSubjectMastery extends Command
{
    protected $signature = 'spk:calculate-mastery {--user_id= : Calculate for specific user only}';

    protected $description = 'Calculate subject mastery for all students';

    public function handle(SubjectMasteryService $service): int
    {
        if ($this->option('user_id')) {
            $user = User::find($this->option('user_id'));
            if (!$user) {
                $this->error('User not found');
                return 1;
            }
            $service->calculateAllMastery($user);
            $this->info("Subject mastery calculated for user {$user->name}");
            return 0;
        }

        $students = User::where('role', 'siswa')->get();
        $bar = $this->output->createProgressBar(count($students));
        $bar->start();

        foreach ($students as $student) {
            $service->calculateAllMastery($student);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Subject mastery calculated for {$students->count()} students");
        return 0;
    }
}
