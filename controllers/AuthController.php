<?php
declare(strict_types=1);

final class AuthController extends BaseController
{
	public function landing(): void
	{
		if (is_authenticated()) {
			$this->redirect((current_user()['role'] ?? '') === 'admin' ? '/admin' : '/upload');
		}

		$this->redirect('/login');
	}

	public function loginForm(): void
	{
		if (is_authenticated()) {
			$this->redirect((current_user()['role'] ?? '') === 'admin' ? '/admin' : '/upload');
		}

		$this->view('auth/login', [
			'config' => config(),
			'error' => null,
			'success' => $_SESSION['flash_success'] ?? null,
			'email' => '',
		]);

		unset($_SESSION['flash_success']);
	}

	public function login(): void
	{
		if (is_authenticated()) {
			$this->redirect((current_user()['role'] ?? '') === 'admin' ? '/admin' : '/upload');
		}

		$email = trim((string) $this->request('email', ''));
		$password = (string) $this->request('password', '');

		if ($email === '' || $password === '') {
			$this->view('auth/login', [
				'config' => config(),
				'error' => 'Please enter your email and password.',
				'success' => null,
				'email' => $email,
			]);
			return;
		}

		$userModel = new User();
		$user = $userModel->findByEmail($email);
		$adminModel = new Admin();
		$admin = null;

		if ($user && !empty($user['password_hash']) && password_verify($password, (string) $user['password_hash'])) {
			$role = $this->normalizeRole((string) ($user['role'] ?? 'member'));
			login_user($user);
			$_SESSION['flash_success'] = 'Welcome back, ' . ($user['name'] ?? 'user') . '.';
			$this->redirect($role === 'admin' ? '/admin' : '/upload');
			return;
		}

		$admin = $adminModel->findByEmail($email);
		if ($admin && !empty($admin['password_hash']) && password_verify($password, (string) $admin['password_hash'])) {
			login_admin($admin);
			$_SESSION['flash_success'] = 'Welcome back, ' . ($admin['name'] ?? 'admin') . '.';
			$this->redirect('/admin');
			return;
		}

		if ($user || $admin) {
			$this->view('auth/login', [
				'config' => config(),
				'error' => 'Invalid email or password.',
				'success' => null,
				'email' => $email,
			]);
			return;
		}

		$this->view('auth/login', [
			'config' => config(),
			'error' => 'No account found for this email address.',
			'success' => null,
			'email' => $email,
		]);
	}

	public function signupForm(): void
	{
		if (is_authenticated()) {
			$this->redirect((current_user()['role'] ?? '') === 'admin' ? '/admin' : '/upload');
		}

		$this->view('auth/signup', [
			'config' => config(),
			'error' => null,
			'name' => '',
			'email' => '',
			'role' => $this->normalizeRole((string) $this->request('role', 'member')),
		]);
	}

	public function signup(): void
	{
		if (is_authenticated()) {
			$this->redirect((current_user()['role'] ?? '') === 'admin' ? '/admin' : '/upload');
		}

		$name = trim((string) $this->request('name', ''));
		$email = trim((string) $this->request('email', ''));
		$password = (string) $this->request('password', '');
		$passwordConfirmation = (string) $this->request('password_confirmation', '');
		$role = $this->normalizeRole((string) $this->request('role', 'member'));
		$adminCode = trim((string) $this->request('admin_code', ''));

		if ($name === '' || $email === '' || $password === '') {
			$this->view('auth/signup', [
				'config' => config(),
				'error' => 'All fields are required.',
				'name' => $name,
				'email' => $email,
				'role' => $role,
			]);
			return;
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->view('auth/signup', [
				'config' => config(),
				'error' => 'Please enter a valid email address.',
				'name' => $name,
				'email' => $email,
				'role' => $role,
			]);
			return;
		}

		if (strlen($password) < 8) {
			$this->view('auth/signup', [
				'config' => config(),
				'error' => 'Password must contain at least 8 characters.',
				'name' => $name,
				'email' => $email,
				'role' => $role,
			]);
			return;
		}

		if ($role === 'admin' && !hash_equals(admin_code(), $adminCode)) {
			$this->view('auth/signup', [
				'config' => config(),
				'error' => 'Invalid admin code.',
				'name' => $name,
				'email' => $email,
				'role' => $role,
			]);
			return;
		}

		if ($password !== $passwordConfirmation) {
			$this->view('auth/signup', [
				'config' => config(),
				'error' => 'Passwords do not match.',
				'name' => $name,
				'email' => $email,
				'role' => $role,
			]);
			return;
		}

		$userModel = new User();
		if ($userModel->findByEmail($email)) {
			$this->view('auth/signup', [
				'config' => config(),
				'error' => 'This email is already registered.',
				'name' => $name,
				'email' => $email,
				'role' => $role,
			]);
			return;
		}

		$userModel->create([
			'name' => $name,
			'email' => $email,
			'password_hash' => password_hash($password, PASSWORD_DEFAULT),
			'role' => $role,
		]);

		$_SESSION['flash_success'] = $role === 'admin' ? 'Admin account created successfully.' : 'Account created successfully. Please log in.';
		$this->redirect($role === 'admin' ? '/admin' : '/login');
	}

	public function logout(): void
	{
		logout_user();
		$_SESSION['flash_success'] = 'You have been signed out.';
		$this->redirect('/login');
	}

	private function normalizeRole(string $role): string
	{
		$role = strtolower(trim($role));
		return in_array($role, ['admin', 'member'], true) ? $role : 'member';
	}
}
