<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\MataPelajaran;
use App\Models\User;
use App\Models\UserLesson;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LessonService extends BaseService
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}

    public function getPaginatedMataPelajarans(?User $user = null, int $pageSize = 15): LengthAwarePaginator
    {
        $paginator = MataPelajaran::query()
            ->with(['lessons' => function ($query) use ($user) {
                $this->kelasAccessService->scopeContentQuery($query, $user)->orderBy('id');
            }])
            ->orderBy('name', 'asc')
            ->paginate($pageSize);

        foreach ($paginator->getCollection() as $mataPelajaran) {
            $this->attachCompletionStatus($mataPelajaran->lessons, $user);
        }

        return $paginator;
    }

    public function getMataPelajaransWithLessons(?User $user = null): Collection
    {
        $mataPelajarans = MataPelajaran::query()
            ->with(['lessons' => function ($query) use ($user) {
                $this->kelasAccessService->scopeContentQuery($query, $user)->orderBy('id');
            }])
            ->orderBy('name', 'asc')
            ->get();

        foreach ($mataPelajarans as $mataPelajaran) {
            $this->attachCompletionStatus($mataPelajaran->lessons, $user);
        }

        return $mataPelajarans;
    }

    public function getMataPelajaranById(int $id, ?User $user = null): MataPelajaran
    {
        $mataPelajaran = MataPelajaran::query()
            ->with(['lessons' => function ($query) use ($user) {
                $this->kelasAccessService->scopeContentQuery($query, $user)->orderBy('id');
            }])
            ->findOrFail($id);

        $this->attachCompletionStatus($mataPelajaran->lessons, $user);

        return $mataPelajaran;
    }

    public function createMataPelajaran(array $data): MataPelajaran
    {
        return MataPelajaran::query()->create($data);
    }

    public function updateMataPelajaranById(int $id, array $data): MataPelajaran
    {
        $mataPelajaran = MataPelajaran::query()->findOrFail($id);
        $mataPelajaran->update($data);

        return $mataPelajaran->fresh(['lessons']);
    }

    public function deleteMataPelajaranById(int $id): void
    {
        $mataPelajaran = MataPelajaran::query()->findOrFail($id);
        $mataPelajaran->delete();
    }

    public function getLessonBySlug(string $slug, ?User $user = null): Lesson
    {
        $lesson = $this->kelasAccessService
            ->scopeContentQuery(
                Lesson::query()->with('mataPelajaran')->where('slug', $slug),
                $user
            )
            ->firstOrFail();

        $this->attachCompletionStatus(new Collection([$lesson]), $user);

        return $lesson;
    }

    public function getLessonsByMataPelajaranId(int $id, ?User $user = null): Collection
    {
        $lessons = $this->kelasAccessService
            ->scopeContentQuery(
                Lesson::query()->where('mata_pelajaran_id', $id),
                $user
            )
            ->orderBy('id', 'asc')
            ->get();

        $this->attachCompletionStatus($lessons, $user);

        return $lessons;
    }

    public function getLessonsByKelasId(int $kelasId, ?User $user = null): Collection
    {
        $lessons = $this->kelasAccessService
            ->scopeContentQuery(
                Lesson::query()->where('kelas_id', $kelasId)->with('mataPelajaran'),
                $user
            )
            ->orderBy('id', 'asc')
            ->get();

        $this->attachCompletionStatus($lessons, $user);

        return $lessons;
    }

    public function getAllLessons(?User $user = null): Collection
    {
        $lessons = $this->kelasAccessService
            ->scopeContentQuery(
                Lesson::query()->with('mataPelajaran'),
                $user
            )
            ->orderBy('id', 'asc')
            ->get();

        $this->attachCompletionStatus($lessons, $user);

        return $lessons;
    }

    public function getCompletedLessons(?User $user = null): Collection
    {
        if (!$user) {
            return new Collection();
        }

        $completedLessonIds = UserLesson::query()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->all();

        $lessons = $this->kelasAccessService
            ->scopeContentQuery(
                Lesson::query()
                    ->with('mataPelajaran')
                    ->whereIn('id', $completedLessonIds),
                $user
            )
            ->orderBy('id', 'asc')
            ->get();

        $this->attachCompletionStatus($lessons, $user);

        return $lessons;
    }

    public function getPaginatedLessons(?User $user = null, int $pageSize = 15): LengthAwarePaginator
    {
        $paginator = $this->kelasAccessService
            ->scopeContentQuery(
                Lesson::query()->with('mataPelajaran'),
                $user
            )
            ->orderBy('id', 'asc')
            ->paginate($pageSize);

        $this->attachCompletionStatus($paginator->getCollection(), $user);

        return $paginator;
    }

    public function createLesson(array $data): Lesson
    {
        unset($data['slug']);

        $lesson = Lesson::query()->create($data);
        $lesson->setAttribute('completed', false);

        return $lesson;
    }

    public function updateLessonBySlug(string $slug, array $data, ?User $user = null): Lesson
    {
        $lesson = Lesson::query()->where('slug', $slug)->firstOrFail();

        unset($data['slug']);

        $lesson->update($data);

        $updatedLesson = $lesson->fresh(['mataPelajaran']);
        $this->attachCompletionStatus(new Collection([$updatedLesson]), $user);

        return $updatedLesson;
    }

    public function deleteLessonBySlug(string $slug): void
    {
        $lesson = Lesson::query()->where('slug', $slug)->firstOrFail();
        $lesson->delete();
    }

    private function attachCompletionStatus(Collection $lessons, ?User $user = null): void
    {
        if ($lessons->isEmpty()) {
            return;
        }

        $completedLessonIds = [];

        if ($user) {
            $completedLessonIds = UserLesson::query()
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $lessons->pluck('id')->all())
                ->where('completed', true)
                ->pluck('lesson_id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        }

        foreach ($lessons as $lesson) {
            $lessonId = (int)$lesson->id;
            $lesson->setAttribute('completed', in_array($lessonId, $completedLessonIds, true));
        }
    }
}
