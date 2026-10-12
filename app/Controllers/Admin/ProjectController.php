<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Project;
use App\Services\Uploader;

final class ProjectController extends AdminController
{
    public function index(): void
    {
        $page   = max(1, Request::int('page', 1));
        $search = Request::string('q');
        $result = Project::paginate($page, 15, $search !== '' ? $search : null);

        $this->view('admin/projects/index', [
            'title'   => 'Projects',
            'active'  => 'projects',
            'result'  => $result,
            'search'  => $search,
        ]);
    }

    public function create(): void
    {
        $this->view('admin/projects/form', [
            'title'      => 'New project',
            'active'     => 'projects',
            'project'    => null,
            'images'     => [],
            'categories' => Project::CATEGORIES,
            'errors'     => \App\Core\Session::pull('_errors', []),
            'old'        => \App\Core\Session::pull('_old', []),
        ]);
    }

    public function store(): void
    {
        $this->verifyPost();

        $data   = $this->collectInput();
        $errors = $this->validate($data, null);

        $thumbResult = $this->handleThumbnail();
        if ($thumbResult['error'] !== null) {
            $errors['thumbnail'] = $thumbResult['error'];
        }
        $data['thumbnail'] = $thumbResult['path'];

        if ($errors !== []) {
            \App\Core\Session::put('_errors', $errors);
            \App\Core\Session::put('_old', $data);
            $this->back('/admin/projects/create', 'error', 'Please correct the highlighted fields.');
        }

        $data['slug'] = $data['slug'] !== '' ? $data['slug'] : Project::slugify($data['title']);
        $data['slug'] = Project::uniqueSlug($data['slug']);

        $id = Project::create($data);

        // Gallery uploads.
        $this->storeGalleryImages($id);

        $this->back('/admin/projects/' . $id . '/edit', 'success', 'Project created.');
    }

    public function edit(array $params): void
    {
        $project = Project::find((int) ($params['id'] ?? 0));
        if ($project === null) {
            Response::abort(404, 'That project does not exist.');
        }

        $this->view('admin/projects/form', [
            'title'      => 'Edit project',
            'active'     => 'projects',
            'project'    => $project,
            'images'     => Project::images((int) $project['id']),
            'categories' => Project::CATEGORIES,
            'errors'     => \App\Core\Session::pull('_errors', []),
            'old'        => \App\Core\Session::pull('_old', []),
        ]);
    }

    public function update(array $params): void
    {
        $this->verifyPost();

        $id      = (int) ($params['id'] ?? 0);
        $project = Project::find($id);
        if ($project === null) {
            Response::abort(404, 'That project does not exist.');
        }

        $data   = $this->collectInput();
        $errors = $this->validate($data, $id);

        $thumbResult = $this->handleThumbnail($project['thumbnail'] ?? null);
        if ($thumbResult['error'] !== null) {
            $errors['thumbnail'] = $thumbResult['error'];
        }
        $data['thumbnail'] = $thumbResult['path'];

        if ($errors !== []) {
            \App\Core\Session::put('_errors', $errors);
            \App\Core\Session::put('_old', $data);
            $this->back('/admin/projects/' . $id . '/edit', 'error', 'Please correct the highlighted fields.');
        }

        $data['slug'] = $data['slug'] !== '' ? $data['slug'] : Project::slugify($data['title']);
        $data['slug'] = Project::uniqueSlug($data['slug'], $id);

        Project::update($id, $data);
        $this->storeGalleryImages($id);

        $this->back('/admin/projects/' . $id . '/edit', 'success', 'Project updated.');
    }

    /** Delete with explicit confirmation token from the modal. */
    public function destroy(array $params): void
    {
        $this->verifyPost();

        $id      = (int) ($params['id'] ?? 0);
        $project = Project::find($id);
        if ($project === null) {
            Response::abort(404, 'That project does not exist.');
        }

        // Clean up files before removing the row.
        Uploader::delete($project['thumbnail'] ?? null);
        foreach (Project::images($id) as $img) {
            Uploader::delete($img['path']);
        }

        Project::delete($id);
        $this->back('/admin/projects', 'success', 'Project deleted.');
    }

    public function deleteImage(array $params): void
    {
        $this->verifyPost();

        $imageId = (int) ($params['imageId'] ?? 0);
        $projectId = (int) ($params['id'] ?? 0);

        $path = Project::deleteImage($imageId);
        if ($path !== null) {
            Uploader::delete($path);
        }

        $this->back('/admin/projects/' . $projectId . '/edit', 'success', 'Image removed.');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function collectInput(): array
    {
        return [
            'title'       => Request::string('title'),
            'slug'        => Request::string('slug'),
            'category'    => Request::string('category'),
            'year'        => Request::string('year', (string) date('Y')),
            'description' => Request::string('description'),
            'case_study'  => (string) Request::input('case_study', ''),
            'software'    => Request::string('software'),
            'video_url'   => Request::string('video_url'),
            'featured'    => Request::bool('featured') ? 1 : 0,
            'published'   => Request::bool('published') ? 1 : 0,
            'sort_order'  => Request::int('sort_order', 0),
        ];
    }

    private function validate(array $data, ?int $exceptId): array
    {
        $validator = new Validator($data, [
            'title'       => 'required|min:2|max:190',
            'slug'        => 'max:190',
            'category'    => 'required|in:' . implode(';', array_keys(Project::CATEGORIES)),
            'year'        => 'required|year',
            'description' => 'required|min:10|max:500',
            'case_study'  => 'max:20000',
            'software'    => 'max:255',
            'video_url'   => 'max:500',
            'sort_order'  => 'int',
        ]);

        $errors = $validator->validate() ? [] : $validator->errors();

        if (!empty($data['slug']) && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors['slug'] = 'Slug may only contain lowercase letters, numbers and hyphens.';
        }
        if (!empty($data['slug']) && Project::slugExists($data['slug'], $exceptId)) {
            $errors['slug'] = 'That slug is already used by another project.';
        }
        if (!empty($data['video_url']) && !filter_var($data['video_url'], FILTER_VALIDATE_URL)) {
            $errors['video_url'] = 'Enter a full video URL (https://…).';
        }

        return $errors;
    }

    /**
     * @return array{path:?string,error:?string}
     */
    private function handleThumbnail(?string $existing = null): array
    {
        $file = Request::file('thumbnail');

        // No new file uploaded — keep the existing thumbnail.
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => $existing, 'error' => null];
        }

        $result = Uploader::store($file, 'thumbnails');
        if (!$result['ok']) {
            return ['path' => $existing, 'error' => $result['error']];
        }

        // Replace: remove the previous file.
        if ($existing !== null && $existing !== $result['path']) {
            Uploader::delete($existing);
        }

        return ['path' => $result['path'], 'error' => null];
    }

    private function storeGalleryImages(int $projectId): void
    {
        $files = $_FILES['gallery'] ?? null;
        if ($files === null || !isset($files['tmp_name'])) {
            return;
        }

        $names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];

        foreach (array_keys($names) as $i) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name'     => $files['name'][$i] ?? '',
                'type'     => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $files['size'][$i] ?? 0,
            ];
            $result = Uploader::store($single, 'thumbnails');
            if ($result['ok']) {
                Project::addImage($projectId, $result['path'], null);
            } else {
                \App\Core\Logger::warning('Gallery upload rejected', ['error' => $result['error']]);
            }
        }
    }
}
