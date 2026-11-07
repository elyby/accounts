<?php
declare(strict_types=1);

namespace common\tests\_support;

use Codeception\Events;
use Codeception\Extension;
use common\config\ConfigLoader;
use PDO;
use PDOException;
use RuntimeException;

/**
 * The tests use a separate database on the application's MariaDB server (see common/config/config-test.php).
 * It's created on demand and migrated once per tests run, so running the tests requires no manual steps.
 *
 * This class if fully AI generated
 */
final class TestDatabase extends Extension {

    /**
     * @var array<string, string>
     */
    public static array $events = [
        Events::SUITE_BEFORE => 'prepare',
    ];

    /**
     * The event is fired for each suite, while the preparation is needed only once per process
     */
    private static bool $isPrepared = false;

    public function prepare(): void {
        if (self::$isPrepared) {
            return;
        }

        /** @var array{dsn: string, username: string, password: string} $db */
        $db = ConfigLoader::load('console')['components']['db'];
        if (!preg_match('/^(?<server>.+);dbname=(?<name>[^;]+)$/', $db['dsn'], $matches)) {
            throw new RuntimeException("Cannot extract the database name from the DSN \"{$db['dsn']}\"");
        }

        $name = $matches['name'];
        if ($name === getenv('DB_DATABASE')) {
            throw new RuntimeException("The tests database must differ from the application's one \"{$name}\", since the tests wipe all the data");
        }

        self::createDatabase($matches['server'], $name, $db['username'], $db['password']);
        self::applyMigrations();

        self::$isPrepared = true;
    }

    private static function createDatabase(string $serverDsn, string $name, string $username, string $password): void {
        $pdo = new PDO($serverDsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $quotedName = '`' . str_replace('`', '``', $name) . '`';
        try {
            // The user is able to create the "test_*" databases thanks to the default MariaDB grants
            $pdo->exec("CREATE DATABASE IF NOT EXISTS {$quotedName} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Cannot create the tests database {$quotedName}. Either create it manually or grant the privileges:\n"
                . "GRANT ALL PRIVILEGES ON {$quotedName}.* TO '{$username}'@'%';",
                previous: $e,
            );
        }
    }

    private static function applyMigrations(): void {
        $command = [PHP_BINARY, dirname(__DIR__, 3) . '/yii', 'migrate/up', '--interactive=0'];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, null, array_merge(getenv(), [
            'YII_ENV' => 'test',
        ]));
        if ($process === false) {
            throw new RuntimeException('Cannot run the migrations for the tests database');
        }

        $output = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        if (proc_close($process) !== 0) {
            throw new RuntimeException("Cannot apply the migrations to the tests database:\n{$output}");
        }
    }

}
