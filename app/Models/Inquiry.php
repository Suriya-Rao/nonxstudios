<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Inquiry
{
    public const STATUSES = ['new', 'in_progress', 'completed', 'archived'];

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'new'         => 'New',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'archived'    => 'Archived',
            default       => 'Unknown',
        };
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO inquiries
                (name, email, company, service, budget, timeline, description, contact_method, status, ip, user_agent, created_at, updated_at)
             VALUES
                (:name, :email, :company, :service, :budget, :timeline, :description, :contact_method, :status, :ip, :user_agent, NOW(), NOW())',
            [
                'name'           => $data['name'],
                'email'          => strtolower($data['email']),
                'company'        => $data['company'] ?? null,
                'service'        => $data['service'] ?? null,
                'budget'         => $data['budget'] ?? null,
                'timeline'       => $data['timeline'] ?? null,
                'description'    => $data['description'],
                'contact_method' => $data['contact_method'] ?? null,
                'status'         => 'new',
                'ip'             => $data['ip'] ?? null,
                'user_agent'     => $data['user_agent'] ?? null,
            ]
        );
    }

    /** Rate limiting: count submissions from an IP inside the window. */
    public static function recentCountByIp(string $ip): int
    {
        $window = (int) config('app.security.contact_rate_window', 900);
        $row = Database::selectOne(
            'SELECT COUNT(*) AS c FROM inquiries
             WHERE ip = :ip AND created_at > (NOW() - INTERVAL :window SECOND)',
            ['ip' => $ip, 'window' => $window]
        );
        return (int) ($row['c'] ?? 0);
    }

    public static function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM inquiries WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        Database::execute(
            'UPDATE inquiries SET status = :status, updated_at = NOW() WHERE id = :id',
            ['status' => $status, 'id' => $id]
        );
    }

    public static function updateNotes(int $id, string $notes): void
    {
        Database::execute(
            'UPDATE inquiries SET internal_notes = :notes, updated_at = NOW() WHERE id = :id',
            ['notes' => $notes, 'id' => $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM inquiries WHERE id = :id', ['id' => $id]);
    }

    /** @return array{items:array,total:int,page:int,perPage:int,lastPage:int} */
    public static function paginate(int $page, array $filters = [], int $perPage = 15): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where  = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['service'])) {
            $where[] = 'service = :service';
            $params['service'] = $filters['service'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :search OR email LIKE :search OR company LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int) (Database::selectOne("SELECT COUNT(*) AS c FROM inquiries$whereSql", $params)['c'] ?? 0);
        $items = Database::select(
            "SELECT * FROM inquiries$whereSql ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset",
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

    public static function countAll(): int
    {
        return (int) (Database::selectOne('SELECT COUNT(*) AS c FROM inquiries')['c'] ?? 0);
    }

    public static function countByStatus(string $status): int
    {
        return (int) (Database::selectOne(
            'SELECT COUNT(*) AS c FROM inquiries WHERE status = :s',
            ['s' => $status]
        )['c'] ?? 0);
    }

    public static function recent(int $limit = 5): array
    {
        return Database::select(
            'SELECT * FROM inquiries ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit)
        );
    }
}
