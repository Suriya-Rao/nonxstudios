<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::selectOne(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => strtolower(trim($email))]
        );
    }

    public static function create(string $name, string $email, string $password, string $role = 'admin'): int
    {
        return Database::insert(
            'INSERT INTO users (name, email, password_hash, role, created_at, updated_at)
             VALUES (:name, :email, :hash, :role, NOW(), NOW())',
            [
                'name'  => $name,
                'email' => strtolower(trim($email)),
                'hash'  => password_hash($password, PASSWORD_DEFAULT),
                'role'  => $role,
            ]
        );
    }

    public static function updatePasswordHash(int $id, string $hash): void
    {
        Database::execute(
            'UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id',
            ['hash' => $hash, 'id' => $id]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        self::updatePasswordHash($id, password_hash($password, PASSWORD_DEFAULT));
    }

    public static function count(): int
    {
        $row = Database::selectOne('SELECT COUNT(*) AS c FROM users');
        return (int) ($row['c'] ?? 0);
    }
}
