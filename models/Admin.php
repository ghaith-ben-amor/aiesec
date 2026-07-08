<?php
declare(strict_types=1);

final class Admin extends BaseModel
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        return is_array($admin) ? $admin : null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :password_hash)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}