<?php
declare(strict_types=1);

function db_config(): array
{
    return [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'aiesec_matcher',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'sqlite_path' => getenv('DB_SQLITE_PATH') ?: (defined('UPLOAD_PATH') ? UPLOAD_PATH . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'aiesec.sqlite' : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aiesec.sqlite'),
    ];
}

function config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function pdo(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = db_config();
    try {
        $pdo = create_mysql_pdo($db);
    } catch (PDOException $mysqlException) {
        $pdo = create_sqlite_pdo($db);
    }

    ensure_schema_initialized($pdo, $db);

    return $pdo;
}

function ensure_database_exists(array $db): void
{
    if (($db['driver'] ?? 'mysql') !== 'mysql') {
        return;
    }

    $serverDsn = sprintf('mysql:host=%s;port=%s;charset=%s', $db['host'], $db['port'], $db['charset']);
    $server = new PDO($serverDsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $quotedDatabase = str_replace('`', '``', $db['database']);
    $server->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $quotedDatabase));
}

function create_mysql_pdo(array $db): PDO
{
    ensure_database_exists($db);

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['port'], $db['database'], $db['charset']);
    return new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function create_sqlite_pdo(array $db): PDO
{
    $sqlitePath = $db['sqlite_path'];
    $sqliteDir = dirname($sqlitePath);

    if (!is_dir($sqliteDir) && !mkdir($sqliteDir, 0775, true) && !is_dir($sqliteDir)) {
        throw new RuntimeException('Unable to create SQLite directory.');
    }

    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');

    return $pdo;
}

function ensure_schema_initialized(PDO $pdo, array $db): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ?: ($db['driver'] ?? 'mysql');
    ensure_users_password_column($pdo, $driver);
    ensure_users_role_column($pdo, $driver);
    ensure_admins_table($pdo, $driver);
    ensure_ep_tables($pdo, $driver);
    ensure_opportunities_columns($pdo, $driver);

    if ($driver === 'mysql') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'");
        $tableExists = (int) $stmt->fetchColumn() > 0;
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'users'");
        $tableExists = (int) $stmt->fetchColumn() > 0;
    }
    if ($tableExists || !is_file(DB_SCHEMA_PATH)) {
        return;
    }

    if ($driver === 'sqlite') {
        initialize_sqlite_schema($pdo);
        return;
    }

    $schema = file_get_contents(DB_SCHEMA_PATH);
    if ($schema === false) {
        return;
    }

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }

        if (preg_match('/^(CREATE\s+DATABASE|USE)\b/i', $statement)) {
            continue;
        }

        $pdo->exec($statement);
    }
}

function ensure_opportunities_columns(PDO $pdo, string $driver): void
{
    $columns = [
        'duration' => $driver === 'mysql' ? 'VARCHAR(100) NULL' : 'TEXT NULL',
        'company' => $driver === 'mysql' ? 'VARCHAR(255) NULL' : 'TEXT NULL',
        'category' => $driver === 'mysql' ? 'VARCHAR(100) NULL' : 'TEXT NULL',
        'external_id' => $driver === 'mysql' ? 'VARCHAR(100) NULL' : 'TEXT NULL',
        'source_type' => $driver === 'mysql' ? "VARCHAR(50) DEFAULT 'api'" : "TEXT DEFAULT 'api'",
    ];

    foreach ($columns as $name => $definition) {
        try {
            if ($driver === 'mysql') {
                $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'opportunities' AND column_name = '$name'");
                $columnExists = (int) $stmt->fetchColumn() > 0;
                if (!$columnExists) {
                    $pdo->exec("ALTER TABLE opportunities ADD COLUMN $name $definition");
                }
            } else {
                $stmt = $pdo->query("PRAGMA table_info(opportunities)");
                $info = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                $columnExists = false;
                foreach ($info as $col) {
                    if (($col['name'] ?? '') === $name) {
                        $columnExists = true;
                        break;
                    }
                }
                if (!$columnExists) {
                    $pdo->exec("ALTER TABLE opportunities ADD COLUMN $name $definition");
                }
            }
        } catch (Throwable $e) {
            // Ignore if column already exists or table is not ready
        }
    }
}

function ensure_users_password_column(PDO $pdo, string $driver): void
{
    try {
        if ($driver === 'mysql') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'password_hash'");
            $columnExists = (int) $stmt->fetchColumn() > 0;
            if ($columnExists) {
                return;
            }

            $tableStmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'");
            $tableExists = (int) $tableStmt->fetchColumn() > 0;
            if ($tableExists) {
                $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
            }

            return;
        }

        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'password_hash') {
                return;
            }
        }

        $tableStmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'users'");
        $tableExists = (int) $tableStmt->fetchColumn() > 0;
        if ($tableExists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN password_hash TEXT NOT NULL DEFAULT ''");
        }
    } catch (Throwable) {
        return;
    }
}

function ensure_users_role_column(PDO $pdo, string $driver): void
{
    try {
        if ($driver === 'mysql') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'role'");
            $columnExists = (int) $stmt->fetchColumn() > 0;
            if ($columnExists) {
                return;
            }

            $tableStmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'");
            $tableExists = (int) $tableStmt->fetchColumn() > 0;
            if ($tableExists) {
                $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'member' AFTER password_hash");
            }

            return;
        }

        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'role') {
                return;
            }
        }

        $tableStmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'users'");
        $tableExists = (int) $tableStmt->fetchColumn() > 0;
        if ($tableExists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'member'");
        }
    } catch (Throwable) {
        return;
    }
}

function initialize_sqlite_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL DEFAULT "",
        role TEXT NOT NULL DEFAULT "member",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS cvs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        file_path TEXT NOT NULL,
        parsed_data TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS opportunities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        skills TEXT NOT NULL,
        location TEXT NOT NULL,
        source_url TEXT NOT NULL,
        duration TEXT NULL,
        company TEXT NULL,
        category TEXT NULL,
        external_id TEXT NULL,
        source_type TEXT DEFAULT "api",
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS matches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cv_id INTEGER NOT NULL,
        opportunity_id INTEGER NULL,
        score REAL NOT NULL DEFAULT 0,
        is_favorite INTEGER NOT NULL DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE,
        FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL
    )');
}

function ensure_admins_table(PDO $pdo, string $driver): void
{
    try {
        if ($driver === 'mysql') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'admins'");
            $tableExists = (int) $stmt->fetchColumn() > 0;
            if (!$tableExists) {
                $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    email VARCHAR(191) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )');
            }
            return;
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'admins'");
        $tableExists = (int) $stmt->fetchColumn() > 0;
        if (!$tableExists) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )');
        }
    } catch (Throwable) {
        return;
    }
}

function ensure_ep_tables(PDO $pdo, string $driver): void
{
    try {
        if ($driver === 'mysql') {
            $pdo->exec('CREATE TABLE IF NOT EXISTS ep_applications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(120) NOT NULL,
                last_name VARCHAR(120) NOT NULL,
                email VARCHAR(191) NOT NULL UNIQUE,
                phone VARCHAR(40) NOT NULL,
                nationality VARCHAR(120) NOT NULL,
                university VARCHAR(191) NOT NULL,
                field_of_study VARCHAR(191) NOT NULL,
                opportunity_title VARCHAR(255) NOT NULL,
                country VARCHAR(120) NOT NULL,
                organization VARCHAR(191) NOT NULL,
                application_date DATE NOT NULL,
                opportunity_link VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "applied",
                stage_index TINYINT UNSIGNED NOT NULL DEFAULT 0,
                folder_name VARCHAR(191) NOT NULL,
                status_updated_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )');

            $pdo->exec('CREATE TABLE IF NOT EXISTS ep_documents (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ep_id INT UNSIGNED NOT NULL,
                document_type VARCHAR(120) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(120) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ep_documents_ep FOREIGN KEY (ep_id) REFERENCES ep_applications(id) ON DELETE CASCADE
            )');

            $pdo->exec('CREATE TABLE IF NOT EXISTS ep_status_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ep_id INT UNSIGNED NOT NULL,
                status VARCHAR(50) NOT NULL,
                stage_index TINYINT UNSIGNED NOT NULL DEFAULT 0,
                changed_by_label VARCHAR(191) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ep_status_history_ep FOREIGN KEY (ep_id) REFERENCES ep_applications(id) ON DELETE CASCADE
            )');

            $pdo->exec('CREATE TABLE IF NOT EXISTS ep_notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ep_id INT UNSIGNED NOT NULL,
                notification_type VARCHAR(80) NOT NULL,
                message VARCHAR(255) NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_ep_notifications_ep FOREIGN KEY (ep_id) REFERENCES ep_applications(id) ON DELETE CASCADE
            )');
            return;
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS ep_applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT NOT NULL,
            nationality TEXT NOT NULL,
            university TEXT NOT NULL,
            field_of_study TEXT NOT NULL,
            opportunity_title TEXT NOT NULL,
            country TEXT NOT NULL,
            organization TEXT NOT NULL,
            application_date TEXT NOT NULL,
            opportunity_link TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "applied",
            stage_index INTEGER NOT NULL DEFAULT 0,
            folder_name TEXT NOT NULL,
            status_updated_at TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ep_documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ep_id INTEGER NOT NULL,
            document_type TEXT NOT NULL,
            original_name TEXT NOT NULL,
            file_name TEXT NOT NULL,
            file_path TEXT NOT NULL,
            mime_type TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ep_id) REFERENCES ep_applications(id) ON DELETE CASCADE
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ep_status_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ep_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            stage_index INTEGER NOT NULL DEFAULT 0,
            changed_by_label TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ep_id) REFERENCES ep_applications(id) ON DELETE CASCADE
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS ep_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ep_id INTEGER NOT NULL,
            notification_type TEXT NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ep_id) REFERENCES ep_applications(id) ON DELETE CASCADE
        )');
    } catch (Throwable $e) {
        return;
    }
}
