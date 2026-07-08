<?php
declare(strict_types=1);

final class AdminController extends BaseController
{
	public function dashboard(): void
	{
		if (!is_admin_authenticated()) {
			$this->redirect('/login');
		}

		$pdo = pdo();
		$opportunityModel = new Opportunity();
		$cvModel = new Cv();
		$matchModel = new MatchResult();
		$syncInfo = $opportunityModel->getSyncInfo();
		$stats = [
			'totalUsers' => $this->countTable($pdo, 'users'),
			'memberUsers' => $this->countUsersByRole($pdo, 'member'),
			'adminUsers' => $this->countUsersByRole($pdo, 'admin'),
			'totalCvs' => $this->countTable($pdo, 'cvs'),
			'totalOpportunities' => $this->countTable($pdo, 'opportunities'),
			'totalMatches' => $this->countTable($pdo, 'matches'),
			'favoriteMatches' => $this->countFavorites($pdo),
		];

		$this->view('admin/dashboard', [
			'config' => config(),
			'isAdmin' => is_admin_authenticated(),
			'error' => $_SESSION['admin_flash_error'] ?? null,
			'flashSuccess' => $_SESSION['admin_flash_success'] ?? null,
			'syncInfo' => $syncInfo,
			'adminUser' => admin_user(),
			'stats' => $stats,
			'recentUsers' => $this->fetchRecentRows($pdo, 'users', 5),
			'recentCvs' => $cvModel->latest(5),
			'recentMatches' => $matchModel->latest(5),
			'recentOpportunities' => $opportunityModel->latest(5),
		]);

		unset($_SESSION['admin_flash_success']);
		unset($_SESSION['admin_flash_error']);
	}

	public function signup(): void
	{
		$this->redirect('/signup?role=admin');
	}

	public function signin(): void
	{
		$this->redirect('/login');
	}

	public function syncOpportunities(): void
	{
		require_admin();

		$opportunityModel = new Opportunity();
		try {
			$count = $opportunityModel->syncAllFromApi();
			if ($count > 0) {
				$_SESSION['admin_flash_success'] = sprintf('Successfully synchronized %d opportunities from AIESEC API.', $count);
			} else {
				$_SESSION['admin_flash_error'] = 'API returned no opportunities or synchronization failed. Please check your AIESEC_ACCESS_TOKEN.';
			}
		} catch (Throwable $e) {
			$_SESSION['admin_flash_error'] = 'API connection error: ' . $e->getMessage();
		}

		$this->redirect('/admin');
	}

	public function logout(): void
	{
		logout_admin();
		$_SESSION['admin_flash_success'] = 'Admin session ended.';
		$this->redirect('/admin');
	}

	private function countTable(PDO $pdo, string $table): int
	{
		$stmt = $pdo->query('SELECT COUNT(*) FROM ' . $table);
		return (int) $stmt->fetchColumn();
	}

	private function countUsersByRole(PDO $pdo, string $role): int
	{
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
		$stmt->execute(['role' => $role]);
		return (int) $stmt->fetchColumn();
	}

	private function countFavorites(PDO $pdo): int
	{
		$stmt = $pdo->query('SELECT COUNT(*) FROM matches WHERE is_favorite = 1');
		return (int) $stmt->fetchColumn();
	}

	private function fetchRecentRows(PDO $pdo, string $table, int $limit = 5): array
	{
		$stmt = $pdo->prepare('SELECT * FROM ' . $table . ' ORDER BY id DESC LIMIT :limit');
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}
}
