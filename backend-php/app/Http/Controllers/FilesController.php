<?php

namespace App\Http\Controllers;

use App\Exceptions\HttpException;
use App\Models\Files;
use App\Models\User;
use App\Support\Auth as AuthSupport;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FilesController extends Controller
{
    public function getSubmissions(Request $request): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);

        $query = Files::with(['submitter', 'committee']);
        $status = $request->query->get('status');
        $committeeId = $request->query->get('committeeId');
        $search = trim((string) $request->query->get('search', ''));

        if ($status) {
            // allow comma-separated status values (e.g. status=submitted,draft)
            if (strpos($status, ',') !== false) {
                $values = array_filter(array_map('trim', explode(',', $status)));
                if (!empty($values)) {
                    $query->whereIn('status', $values);
                }
            } else {
                $query->where('status', $status);
            }
        }
        if ($committeeId) {
            $query->where('committee_id', $committeeId);
        }
        if ($search !== '') {
            $needle = '%' . strtolower($search) . '%';
            $query->where(static function (Builder $builder) use ($needle): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$needle]);
            });
        }

        $files = $query->orderByDesc('submitted_at')->get();
        return $this->json([
            'items' => $files->map(fn (Files $file) => $this->fileToApiResponse($file))->all(),
            'total' => $files->count(),
        ]);
    }

    public function submitFile(Request $request): JsonResponse
    {
        $user = \App\Support\Auth::user($this->app, $request, true);

        $body = $this->body($request);
        // debug - persist received body for investigation
        try {
            $debugPath = $this->app->path('storage') . DIRECTORY_SEPARATOR . 'debug_file_update.log';
            file_put_contents($debugPath, date('c') . ' UPDATE-PATCH BODY: ' . json_encode($body, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore debug logging errors
        }
        // also log raw content and format to PHP error log for visibility
        try {
            error_log('UPDATE-PATCH RAW_CONTENT: ' . $request->getContent());
            error_log('UPDATE-PATCH FORMAT: ' . (string)$request->getContentTypeFormat());
        } catch (\Throwable $e) {
            // ignore
        }
        $this->validateSubmitData($body);

        $file = Files::create([
            'committee_id' => $this->getUserCommitteeId($user),
            'type' => $body['type'],
            'title' => $body['title'],
            'description' => $body['description'] ?? null,
            'content_path' => $body['content_path'],
            'submitted_by' => $user->id,
            'status' => 'submitted',
            'visibility' => 'committee_only',
        ]);

        return $this->json($this->fileToApiResponse($file), 201);
    }

    private function validateSubmitData(array $data): void
    {
        if (!isset($data['title']) || !is_string($data['title']) || strlen($data['title']) > 255) {
            throw new HttpException('Title is required and must be a string of max 255 characters', 400);
        }
        $validTypes = ['position_paper', 'working_paper', 'draft_resolution', 'press_release', 'other'];
        if (!isset($data['type']) || !in_array($data['type'], $validTypes, true)) {
            throw new HttpException('Type is required and must be one of: ' . implode(', ', $validTypes), 400);
        }
        if (isset($data['description']) && (!is_string($data['description']) || strlen($data['description']) > 1000)) {
            throw new HttpException('Description must be a string of max 1000 characters', 400);
        }
        if (!isset($data['content_path']) || !is_string($data['content_path']) || strlen($data['content_path']) > 500) {
            throw new HttpException('Content path is required and must be a string of max 500 characters', 400);
        }
    }

    public function decideSubmission(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);

        $body = $this->body($request);
        if (!isset($body['decision']) || !in_array($body['decision'], ['approved', 'rejected'], true)) {
            throw new HttpException('Decision must be approved or rejected', 400);
        }
        if (isset($body['dias_fb']) && !is_string($body['dias_fb'])) {
            throw new HttpException('Dias feedback must be a string', 400);
        }

        $file = Files::findOrFail((int) $params['submissionId']);
        $file->update([
            'status' => $body['decision'] === 'approved' ? 'approved' : 'rejected',
            'dias_fb' => $body['dias_fb'] ?? null,
        ]);

        return $this->json($this->fileToApiResponse($file));
    }

    /**
     * Allow a submitter to update their own submission before it has been approved/rejected/published.
     * PATCH /api/files/submissions/{submissionId}
     */
    public function updateSubmission(Request $request, array $params): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);

        $body = $this->body($request);

        $file = Files::findOrFail((int) $params['submissionId']);

        if ($file->submitted_by !== $user->id) {
            throw new HttpException('Only the submitter may modify this submission', 403);
        }

        // only allow edits if not yet approved/published/rejected
        if (in_array($file->status, ['approved', 'published', 'rejected'], true)) {
            throw new HttpException('Cannot modify a submission that has been finalized', 400);
        }

        $updates = [];
        if (isset($body['title'])) {
            if (!is_string($body['title']) || strlen($body['title']) > 255) {
                throw new HttpException('Title must be a string of max 255 characters', 400);
            }
            $updates['title'] = $body['title'];
        }
        if (isset($body['description'])) {
            if (!is_string($body['description']) || strlen($body['description']) > 1000) {
                throw new HttpException('Description must be a string of max 1000 characters', 400);
            }
            $updates['description'] = $body['description'];
        }
        if (isset($body['type'])) {
            $validTypes = ['position_paper', 'working_paper', 'draft_resolution', 'press_release', 'other'];
            if (!in_array($body['type'], $validTypes, true)) {
                throw new HttpException('Type must be one of: ' . implode(', ', $validTypes), 400);
            }
            $updates['type'] = $body['type'];
        }
        if (isset($body['content_path'])) {
            if (!is_string($body['content_path']) || strlen($body['content_path']) > 500) {
                throw new HttpException('Content path must be a string of max 500 characters', 400);
            }
            $updates['content_path'] = $body['content_path'];
        }
        if (isset($body['status'])) {
            // allow submitter to set status to draft or submitted
            $valid = ['draft', 'submitted'];
            if (!in_array($body['status'], $valid, true)) {
                throw new HttpException('Submitter can only set status to: ' . implode(', ', $valid), 400);
            }
            $updates['status'] = $body['status'];
        }

        if (!empty($updates)) {
            $file->update($updates);
            if (method_exists($file, 'refresh')) {
                $file->refresh();
            } else {
                $file = Files::findOrFail($file->id);
            }
        }

        return $this->json($this->fileToApiResponse($file));
    }

    public function deletePublished(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);

        // find by id regardless of status — presidium is required earlier so any file may be updated
        $file = Files::findOrFail((int) $params['fileId']);
        $file->delete();

        return $this->json(['message' => 'File deleted successfully']);
    }

    private function getUserCommitteeId(User $user): ?int
    {
        // Find the user's committee from the Delegates table
        $delegate = \App\Models\Delegate::where('user_id', $user->id)->first();
        return $delegate ? $delegate->committee_id : null;
    }

    public function getPublished(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);

        $query = Files::with(['submitter', 'committee']);
        $status = $request->query->get('status');
        $committeeId = $request->query->get('committeeId');
        $search = trim((string) $request->query->get('search', ''));

        // 如果指定了status参数，则按status过滤，否则显示所有文件
        if ($status) {
            $query->where('status', $status);
        }

        if ($committeeId) {
            $query->where('committee_id', $committeeId);
        }
        if ($search !== '') {
            $needle = '%' . strtolower($search) . '%';
            $query->where(static function (Builder $builder) use ($needle): void {
                $builder
                    ->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$needle]);
            });
        }

        // 可见性控制：管理员可以看到所有文件，其他用户根据visibility过滤
        if ($user->role !== 'admin') {
            // 获取用户所属的委员会ID列表
            $userCommitteeIds = $user->delegates()->pluck('committee_id')->toArray();

            $query->where(function (Builder $q) use ($user, $userCommitteeIds) {
                // 公开文件：所有登录用户可见
                $q->where('visibility', 'public')
                // 委员会所有成员文件：用户必须属于该委员会
                  ->orWhere(function (Builder $subQ) use ($userCommitteeIds) {
                      $subQ->where('visibility', 'all_committees')
                           ->whereIn('committee_id', $userCommitteeIds);
                  })
                // 仅主席团文件：用户必须是主席团且属于该委员会
                  ->orWhere(function (Builder $subQ) use ($user, $userCommitteeIds) {
                      $subQ->where('visibility', 'committee_only')
                           ->whereIn('committee_id', $userCommitteeIds)
                           ->where(function (Builder $roleQ) use ($user) {
                               return $user->role === 'dais' ? $roleQ->whereRaw('1 = 1') : $roleQ->whereRaw('1 = 0');
                           });
                  });
            });
        }

        $files = $query->orderByDesc('updated_at')->get();
        return $this->json([
            'items' => $files->map(fn (Files $file) => $this->fileToApiResponse($file))->all(),
            'total' => $files->count(),
        ]);
    }

    public function publishFile(Request $request): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);

        $body = $this->body($request);
        $this->validatePublishData($body);

        $file = Files::create([
            'committee_id' => $body['committee_id'] ?? null,
            'type' => $body['type'],
            'title' => $body['title'],
            'description' => $body['description'] ?? null,
            'content_path' => $body['content_path'],
            'submitted_by' => AuthSupport::user($this->app, $request, true)->id,
            'status' => 'published',
            'visibility' => $body['visibility'],
        ]);

        return $this->json($this->fileToApiResponse($file), 201);
    }

    public function updatePublished(Request $request, array $params): JsonResponse
    {
        AuthSupport::requirePresidium($this->app, $request);

        $body = $this->body($request);
        $updates = [];
        if (isset($body['title'])) {
            if (!is_string($body['title']) || strlen($body['title']) > 255) {
                throw new HttpException('Title must be a string of max 255 characters', 400);
            }
            $updates['title'] = $body['title'];
        }
        if (isset($body['description'])) {
            if (!is_string($body['description']) || strlen($body['description']) > 1000) {
                throw new HttpException('Description must be a string of max 1000 characters', 400);
            }
            $updates['description'] = $body['description'];
        }
        if (isset($body['visibility'])) {
            $validVis = ['committee_only', 'all_committees', 'public'];
            if (!in_array($body['visibility'], $validVis, true)) {
                throw new HttpException('Visibility must be one of: ' . implode(', ', $validVis), 400);
            }
            $updates['visibility'] = $body['visibility'];
        }
        if (isset($body['type'])) {
            $validTypes = ['position_paper', 'working_paper', 'draft_resolution', 'press_release', 'other'];
            if (!in_array($body['type'], $validTypes, true)) {
                throw new HttpException('Type must be one of: ' . implode(', ', $validTypes), 400);
            }
            $updates['type'] = $body['type'];
        }
        if (isset($body['status'])) {
            // new allowed statuses: draft, submitted, approved, published, rejected
            $validStatuses = ['draft', 'submitted', 'approved', 'published', 'rejected'];
            if (!in_array($body['status'], $validStatuses, true)) {
                throw new HttpException('Status must be one of: ' . implode(', ', $validStatuses), 400);
            }
            $updates['status'] = $body['status'];
        }
        if (isset($body['committee_id'])) {
            if ($body['committee_id'] !== null && (!is_int($body['committee_id']) && !ctype_digit($body['committee_id']))) {
                throw new HttpException('Committee ID must be an integer or null', 400);
            }
            $updates['committee_id'] = $body['committee_id'];
        }
        if (isset($body['dias_fb'])) {
            if (!is_string($body['dias_fb'])) {
                throw new HttpException('Dias feedback must be a string', 400);
            }
            $updates['dias_fb'] = $body['dias_fb'];
        }

        // Find file by id (do not restrict by status) — presidium permission already enforced
        $file = Files::findOrFail((int) $params['fileId']);
        $file->update($updates);
        // ensure model reflects latest values
        if (method_exists($file, 'refresh')) {
            $file->refresh();
        } else {
            $file = Files::findOrFail($file->id);
        }

        return $this->json($this->fileToApiResponse($file));
    }

    public function getReference(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);

        $query = Files::where('status', 'published')
            ->select('id', 'title', 'type', 'committee_id', 'visibility')
            ->with('committee:id,name,code');

        $visibilityFilter = $request->query->get('visibility');
        $allowedVisibility = $visibilityFilter
            ? array_values(array_filter(array_map('trim', explode(',', (string) $visibilityFilter))))
            : ['all_committees', 'public'];

        if (!empty($allowedVisibility)) {
            $query->whereIn('visibility', $allowedVisibility);
        }

        // 可见性控制：管理员可以看到所有文件，其他用户根据visibility过滤
        if ($user->role !== 'admin') {
            // 获取用户所属的委员会ID列表
            $userCommitteeIds = $user->delegates()->pluck('committee_id')->toArray();

            $query->where(function (Builder $q) use ($user, $userCommitteeIds) {
                // 公开文件：所有登录用户可见
                $q->where('visibility', 'public')
                // 委员会所有成员文件：用户必须属于该委员会
                  ->orWhere(function (Builder $subQ) use ($userCommitteeIds) {
                      $subQ->where('visibility', 'all_committees')
                           ->whereIn('committee_id', $userCommitteeIds);
                  })
                // 仅主席团文件：用户必须是主席团且属于该委员会
                  ->orWhere(function (Builder $subQ) use ($user, $userCommitteeIds) {
                      $subQ->where('visibility', 'committee_only')
                           ->whereIn('committee_id', $userCommitteeIds)
                           ->where(function (Builder $roleQ) use ($user) {
                               return $user->role === 'dais' ? $roleQ->whereRaw('1 = 1') : $roleQ->whereRaw('1 = 0');
                           });
                  });
            });
        }

        $files = $query->orderBy('title')->get();

        return $this->json([
            'items' => $files->map(fn (Files $file) => [
                'id' => $file->id,
                'title' => $file->title,
                'type' => $file->type,
                'committee' => $file->committee ? [
                    'id' => $file->committee->id,
                    'name' => $file->committee->name,
                    'code' => $file->committee->code,
                ] : null,
                'visibility' => $file->visibility,
            ])->all(),
        ]);
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $user = AuthSupport::user($this->app, $request, true);

        if (!$request->files->has('file')) {
            throw new HttpException('No file uploaded', 400);
        }

        $uploadedFile = $request->files->get('file');
        $extension = $uploadedFile->getClientOriginalExtension();

        // create per-user directory inside attachments
        $userDir = (string) $user->id;
        $attachmentsDir = $this->app->path('attachments');
        $targetDir = $attachmentsDir . DIRECTORY_SEPARATOR . $userDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // format filename as yyyy-mm-dd-HH-ii-ss.ext for readability
        $ts = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H-i-s');
        $safeExt = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $extension);
        $filename = $ts . ($safeExt !== '' ? '.' . $safeExt : '');
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        // move uploaded file into user directory
        $uploadedFile->move($targetDir, $filename);

        return $this->json([
            'fileUrl' => '/attachments/' . $userDir . '/' . $filename,
            'filename' => $filename,
        ]);
    }

    private function validatePublishData(array $data): void
    {
        if (isset($data['committee_id']) && (!is_int($data['committee_id']) && !ctype_digit($data['committee_id']))) {
            throw new HttpException('Committee ID must be an integer', 400);
        }
        $validTypes = ['position_paper', 'working_paper', 'draft_resolution', 'press_release', 'other'];
        if (!isset($data['type']) || !in_array($data['type'], $validTypes, true)) {
            throw new HttpException('Type is required and must be one of: ' . implode(', ', $validTypes), 400);
        }
        if (!isset($data['title']) || !is_string($data['title']) || strlen($data['title']) > 255) {
            throw new HttpException('Title is required and must be a string of max 255 characters', 400);
        }
        if (isset($data['description']) && (!is_string($data['description']) || strlen($data['description']) > 1000)) {
            throw new HttpException('Description must be a string of max 1000 characters', 400);
        }
        if (!isset($data['content_path']) || !is_string($data['content_path']) || strlen($data['content_path']) > 500) {
            throw new HttpException('Content path is required and must be a string of max 500 characters', 400);
        }
        $validVis = ['committee_only', 'all_committees', 'public'];
        if (!isset($data['visibility']) || !in_array($data['visibility'], $validVis, true)) {
            throw new HttpException('Visibility is required and must be one of: ' . implode(', ', $validVis), 400);
        }
    }

    private function fileToApiResponse(Files $file): array
    {
        return [
            'id' => $file->id,
            'committee' => $file->committee ? [
                'id' => $file->committee->id,
                'name' => $file->committee->name,
                'code' => $file->committee->code,
            ] : null,
            'type' => $file->type,
            'title' => $file->title,
            'description' => $file->description,
            'content_path' => $file->content_path,
            'submitted_by' => $file->submitter ? [
                'id' => $file->submitter->id,
                'name' => $file->submitter->name,
                'organization' => $file->submitter->organization,
            ] : null,
            'submitted_at' => $file->submitted_at?->toISOString(),
            'status' => $file->status,
            'visibility' => $file->visibility,
            'dias_fb' => $file->dias_fb,
            'created_at' => $file->created_at?->toISOString(),
            'updated_at' => $file->updated_at?->toISOString(),
        ];
    }
}
