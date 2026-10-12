<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Project
{
    public const CATEGORIES = [
        'motion-graphics' => 'Motion Graphics',
        '3d-cgi'          => '3D & CGI',
        'video-editing'   => 'Video Editing',
        'graphic-design'  => 'Graphic Design',
        'unreal-engine'   => 'Unreal Engine',
    ];

    public static function categoryLabel(?string $slug): string
    {
        return self::CATEGORIES[$slug] ?? 'Uncategorised';
    }

    /** Published projects, optionally filtered by category slug. */
    public static function published(?string $category = null, ?int $limit = null): array
    {
        $sql    = 'SELECT * FROM projects WHERE published = 1';
        $params = [];

        if ($category !== null && isset(self::CATEGORIES[$category])) {
            $sql .= ' AND category = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY sort_order ASC, year DESC, id DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        return Database::select($sql, $params);
    }

    public static function featured(int $limit = 3): array
    {
        return Database::select(
            'SELECT * FROM projects WHERE published = 1 AND featured = 1
             ORDER BY sort_order ASC, id DESC LIMIT ' . max(1, $limit)
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::selectOne(
            'SELECT * FROM projects WHERE slug = :slug AND published = 1 LIMIT 1',
            ['slug' => $slug]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM projects WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public static function images(int $projectId): array
    {
        return Database::select(
            'SELECT * FROM project_images WHERE project_id = :id ORDER BY sort_order ASC, id ASC',
            ['id' => $projectId]
        );
    }

    public static function related(array $project, int $limit = 3): array
    {
        return Database::select(
            'SELECT * FROM projects
             WHERE published = 1 AND id <> :id AND category = :category
             ORDER BY sort_order ASC, id DESC LIMIT ' . max(1, $limit),
            ['id' => $project['id'], 'category' => $project['category']]
        );
    }

    /** Category counts for the filter bar. */
    public static function countsByCategory(): array
    {
        $rows = Database::select(
            'SELECT category, COUNT(*) AS c FROM projects WHERE published = 1 GROUP BY category'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['category']] = (int) $row['c'];
        }
        return $out;
    }

    // ---------------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------------

    public static function paginate(int $page, int $perPage = 15, ?string $search = null): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where  = '';
        $params = [];

        if ($search !== null && $search !== '') {
            $where = ' WHERE title LIKE :s OR slug LIKE :s OR description LIKE :s';
            $params['s'] = '%' . $search . '%';
        }

        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM projects$where", $params)['c'] ?? 0);

        $items = Database::select(
            "SELECT * FROM projects$where ORDER BY sort_order ASC, id DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function allForAdmin(): array
    {
        return Database::select('SELECT * FROM projects ORDER BY sort_order ASC, id DESC');
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO projects
                (title, slug, category, year, description, case_study, software, thumbnail, video_url, featured, published, sort_order, created_at, updated_at)
             VALUES
                (:title, :slug, :category, :year, :description, :case_study, :software, :thumbnail, :video_url, :featured, :published, :sort_order, NOW(), NOW())',
            self::bindings($data)
        );
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE projects SET
                title = :title, slug = :slug, category = :category, year = :year,
                description = :description, case_study = :case_study, software = :software,
                thumbnail = :thumbnail, video_url = :video_url, featured = :featured,
                published = :published, sort_order = :sort_order, updated_at = NOW()
             WHERE id = :id',
            array_merge(self::bindings($data), ['id' => $id])
        );
    }

    public static function delete(int $id): void
    {
        $images = self::images($id);
        Database::execute('DELETE FROM projects WHERE id = :id', ['id' => $id]);
        // project_images cascade via FK; return paths for file cleanup by caller.
        unset($images);
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT id FROM projects WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        return Database::selectOne($sql . ' LIMIT 1', $params) !== null;
    }

    /** Produce a unique, URL-safe slug. */
    public static function uniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = self::slugify($title);
        $slug = $base;
        $i    = 2;
        while (self::slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        return $text !== '' ? $text : 'project';
    }

    // --- gallery ---------------------------------------------------------

    public static function addImage(int $projectId, string $path, ?string $caption = null): int
    {
        $max = Database::selectOne(
            'SELECT COALESCE(MAX(sort_order), 0) AS m FROM project_images WHERE project_id = :id',
            ['id' => $projectId]
        );
        return Database::insert(
            'INSERT INTO project_images (project_id, path, caption, sort_order, created_at)
             VALUES (:pid, :path, :caption, :sort, NOW())',
            [
                'pid'     => $projectId,
                'path'    => $path,
                'caption' => $caption,
                'sort'    => ((int) ($max['m'] ?? 0)) + 1,
            ]
        );
    }

    public static function deleteImage(int $imageId): ?string
    {
        $img = Database::selectOne('SELECT * FROM project_images WHERE id = :id', ['id' => $imageId]);
        if ($img === null) {
            return null;
        }
        Database::execute('DELETE FROM project_images WHERE id = :id', ['id' => $imageId]);
        return $img['path'];
    }

    public static function countAll(): int
    {
        return (int) (Database::selectOne('SELECT COUNT(*) AS c FROM projects')['c'] ?? 0);
    }

    public static function countPublished(): int
    {
        return (int) (Database::selectOne('SELECT COUNT(*) AS c FROM projects WHERE published = 1')['c'] ?? 0);
    }

    /** @param array<string,mixed> $data */
    private static function bindings(array $data): array
    {
        return [
            'title'       => $data['title'],
            'slug'        => $data['slug'],
            'category'    => $data['category'],
            'year'        => (int) $data['year'],
            'description' => $data['description'] ?? '',
            'case_study'  => $data['case_study'] ?? '',
            'software'    => $data['software'] ?? '',
            'thumbnail'   => $data['thumbnail'] ?? null,
            'video_url'   => $data['video_url'] ?? null,
            'featured'    => !empty($data['featured']) ? 1 : 0,
            'published'   => !empty($data['published']) ? 1 : 0,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
