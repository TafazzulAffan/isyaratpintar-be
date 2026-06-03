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
    public function getPaginatedMataPelajarans(?User $user = null, int $pageSize = 15): LengthAwarePaginator
    {
        $paginator = MataPelajaran::query()
            ->with(['lessons' => function ($query) {
                $query->orderBy('id');
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
            ->with(['lessons' => function ($query) {
                $query->orderBy('id');
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
            ->with(['lessons' => function ($query) {
                $query->orderBy('id');
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
        $lesson = Lesson::query()
            ->with('mataPelajaran')
            ->where('slug', $slug)
            ->firstOrFail();

        $this->attachCompletionStatus(new Collection([$lesson]), $user);

        return $lesson;
    }

    public function getLessonsByMataPelajaranId(int $id, ?User $user = null): Collection
    {
        $lessons = Lesson::query()
            ->where('mata_pelajaran_id', $id)
            ->orderBy('id', 'asc')
            ->get();

        $this->attachCompletionStatus($lessons, $user);

        return $lessons;
    }

    public function getAllLessons(?User $user = null): Collection
    {
        $lessons = Lesson::query()
            ->with('mataPelajaran')
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

        $lessons = Lesson::query()
            ->with('mataPelajaran')
            ->whereIn('id', $completedLessonIds)
            ->orderBy('id', 'asc')
            ->get();

        $this->attachCompletionStatus($lessons, $user);

        return $lessons;
    }

    public function getPaginatedLessons(?User $user = null, int $pageSize = 15): LengthAwarePaginator
    {
        $paginator = Lesson::query()
            ->with('mataPelajaran')
            ->orderBy('id', 'asc')
            ->paginate($pageSize);

        $this->attachCompletionStatus($paginator->getCollection(), $user);

        return $paginator;
    }

    public function createLesson(array $data): Lesson
    {
        unset($data['slug']);

        if (isset($data['pdf_file']) && $data['pdf_file'] instanceof UploadedFile) {
            $path = $data['pdf_file']->store('lessons/pdfs', 'public');
            $data['pdf_url'] = $path;
            unset($data['pdf_file']);
        }

        $lesson = Lesson::query()->create($data);
        $lesson->setAttribute('completed', false);

        return $lesson;
    }

    public function updateLessonBySlug(string $slug, array $data, ?User $user = null): Lesson
    {
        $lesson = Lesson::query()->where('slug', $slug)->firstOrFail();

        unset($data['slug']);

        if (isset($data['pdf_file']) && $data['pdf_file'] instanceof UploadedFile) {
            $oldPdfPath = $this->extractPublicStoragePath($lesson->pdf_url);

            $path = $data['pdf_file']->store('lessons/pdfs', 'public');
            $data['pdf_url'] = $path;
            unset($data['pdf_file']);

            if ($oldPdfPath && Storage::disk('public')->exists($oldPdfPath)) {
                Storage::disk('public')->delete($oldPdfPath);
            }
        }

        $lesson->update($data);

        $updatedLesson = $lesson->fresh(['mataPelajaran']);
        $this->attachCompletionStatus(new Collection([$updatedLesson]), $user);

        return $updatedLesson;
    }

    private function extractPublicStoragePath(?string $pdfUrl): ?string
    {
        if (!$pdfUrl) {
            return null;
        }

        $prefix = '/storage/';
        if (str_starts_with($pdfUrl, $prefix)) {
            return ltrim(substr($pdfUrl, strlen($prefix)), '/');
        }

        return ltrim($pdfUrl, '/');
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
