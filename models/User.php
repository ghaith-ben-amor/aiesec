<?php
declare(strict_types=1);

final class User extends BaseModel
{
	public function findByEmail(string $email): ?array
	{
		$stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
		$stmt->execute(['email' => $email]);
		$user = $stmt->fetch();

		return is_array($user) ? $user : null;
	}

	public function findById(int $id): ?array
	{
		$stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
		$stmt->execute(['id' => $id]);
		$user = $stmt->fetch();

		return is_array($user) ? $user : null;
	}

	public function create(array $data): int
	{
		$stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)');
		$stmt->execute([
			'name' => $data['name'],
			'email' => $data['email'],
			'password_hash' => $data['password_hash'],
			'role' => $data['role'] ?? 'member',
		]);

		return (int) $this->pdo->lastInsertId();
	}
}