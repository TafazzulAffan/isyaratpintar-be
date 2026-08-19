<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Assessment;
use App\Models\Kelas;
use App\Models\Lesson;
use App\Models\PblCase;
use App\Policies\AssessmentPolicy;
use App\Policies\KelasPolicy;
use App\Policies\LessonPolicy;
use App\Policies\PblCasePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Kelas::class => KelasPolicy::class,
        Lesson::class => LessonPolicy::class,
        PblCase::class => PblCasePolicy::class,
        Assessment::class => AssessmentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
