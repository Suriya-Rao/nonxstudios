<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Project;
use App\Models\Setting;

final class WorkController extends BaseController
{
    /** /work — all published projects with a category filter. */
    public function index(): void
    {
        $category = Request::string('category');
        if ($category !== '' && !isset(Project::CATEGORIES[$category])) {
            $category = '';
        }

        $projects = Project::published($category !== '' ? $category : null);

        $this->view('pages/work', array_merge(
            $this->meta(
                'Selected Work — ' . Setting::value('company_name'),
                'A collection of visuals, experiments, and worlds we\'ve brought to life across motion, 3D, video and design.'
            ),
            [
                'bodyClass' => 'page-work',
                'projects'  => $projects,
                'counts'    => Project::countsByCategory(),
                'active'    => $category,
            ]
        ));
    }

    /** /project/{slug} — full case study. */
    public function show(array $params): void
    {
        $slug = (string) ($params['slug'] ?? '');
        $project = Project::findBySlug($slug);

        if ($project === null) {
            Response::abort(404, 'That project could not be found.');
        }

        $images = Project::images((int) $project['id']);
        $related = Project::related($project, 3);
        $cover = !empty($project['thumbnail'])
            ? url('/media/' . ltrim((string) $project['thumbnail'], '/'))
            : null;

        $this->view('pages/project', array_merge(
            $this->meta(
                $project['title'] . ' — ' . Setting::value('company_name'),
                mb_substr((string) $project['description'], 0, 155),
                url('/project/' . $project['slug']),
                $cover
            ),
            [
                'bodyClass' => 'page-project',
                'project'   => $project,
                'images'    => $images,
                'related'   => $related,
                'videoEmbed' => $this->videoEmbed((string) ($project['video_url'] ?? '')),
            ]
        ));
    }

    /** Convert a YouTube/Vimeo URL into a privacy-friendly embed URL. */
    private function videoEmbed(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})#', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }

        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }
}
