<?php

/**
 * PDO.php
 *
 * This file is part of InitPHP Cache.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 InitPHP
 * @license    https://github.com/InitPHP/Cache/blob/main/LICENSE  MIT
 * @link       https://github.com/InitPHP/Cache
 */

declare(strict_types=1);

namespace InitPHP\Cache\Handler;

use DateInterval;
use InitPHP\Cache\BaseHandler;
use InitPHP\Cache\Exception\CacheException;
use PDO as PdoConnection;
use PDOException;
use PDOStatement;

use function is_numeric;
use function preg_match;
use function serialize;
use function str_replace;
use function time;
use function unserialize;

/**
 * Database cache handler backed by PDO.
 *
 * Items live in a single table with a unique "name" column, an optional
 * absolute-expiry "ttl" column and a serialised "data" column. The handler is
 * portable: driver-specific tuning (MySQL charset/collation) runs only on
 * MySQL, and all other statements use plain ANSI SQL so SQLite and PostgreSQL
 * work too.
 *
 * Options:
 *  - prefix    (string)      Key prefix and clear() filter. Default "cache_".
 *  - dsn       (string)      PDO DSN. Default "mysql:host=localhost;dbname=test".
 *  - username  (string|null) Connection user.
 *  - password  (string|null) Connection password.
 *  - charset   (string)      MySQL only. Default "utf8mb4".
 *  - collation (string)      MySQL only. Default "utf8mb4_general_ci".
 *  - table     (string)      Table name (letters, digits, underscores). Default "cache".
 *
 * Suggested schema (MySQL):
 *  CREATE TABLE `cache` (
 *      `name` VARCHAR(255) NOT NULL,
 *      `ttl`  INT NULL DEFAULT NULL,
 *      `data` TEXT NOT NULL,
 *      UNIQUE (`name`)
 *  );
 */
class PDO extends BaseHandler
{
    /**
     * @var array<string, mixed>
     */
    protected array $handlerOptions = [
        'dsn'       => 'mysql:host=localhost;dbname=test',
        'username'  => null,
        'password'  => null,
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_general_ci',
        'table'     => 'cache',
    ];

    protected ?PdoConnection $pdo = null;

    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->read($key);

        return $row === false ? $default : $row['data'];
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $name = $this->name($key);
        $seconds = $this->ttlToSeconds($ttl);
        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }
        $expiresAt = $seconds === null ? null : time() + $seconds;
        $table = $this->table();

        $pdo = $this->getPDO();
        try {
            $pdo->beginTransaction();
            $delete = $pdo->prepare('DELETE FROM ' . $table . ' WHERE name = ?');
            $insert = $pdo->prepare('INSERT INTO ' . $table . ' (name, ttl, data) VALUES (?, ?, ?)');
            if ($delete === false || $insert === false) {
                $pdo->rollBack();

                return false;
            }
            $delete->execute([$name]);
            $result = $insert->execute([$name, $expiresAt, serialize($value)]);
            $pdo->commit();

            return $result;
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->statement('DELETE FROM ' . $this->table() . ' WHERE name = ?', [$this->name($key)]) !== false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $prefix = $this->getOption('prefix', '');
        $pattern = $this->likePattern(\is_string($prefix) ? $prefix : '');
        $sql = 'DELETE FROM ' . $this->table() . " WHERE name LIKE ? ESCAPE '\\'";

        return $this->statement($sql, [$pattern]) !== false;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->read($key) !== false;
    }

    /**
     * @inheritDoc
     */
    public function isSupported(): bool
    {
        return \extension_loaded('pdo');
    }

    /**
     * @return PdoConnection
     * @throws CacheException If the connection cannot be established.
     */
    protected function getPDO(): PdoConnection
    {
        if ($this->pdo instanceof PdoConnection) {
            return $this->pdo;
        }
        try {
            $pdo = new PdoConnection(
                $this->optionString('dsn'),
                $this->nullableString('username'),
                $this->nullableString('password'),
                [
                    PdoConnection::ATTR_ERRMODE            => PdoConnection::ERRMODE_EXCEPTION,
                    PdoConnection::ATTR_DEFAULT_FETCH_MODE => PdoConnection::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new CacheException('PDO cache connection error: ' . $e->getMessage(), 0, $e);
        }
        if ($pdo->getAttribute(PdoConnection::ATTR_DRIVER_NAME) === 'mysql') {
            $charset = $this->optionString('charset', 'utf8mb4');
            $collation = $this->optionString('collation', 'utf8mb4_general_ci');
            $pdo->exec("SET NAMES '" . $charset . "' COLLATE '" . $collation . "'");
        }

        return $this->pdo = $pdo;
    }

    /**
     * Reads a row, deleting it first when it has expired.
     *
     * @param string $key
     * @return array{ttl: int|null, data: mixed}|false
     * @throws CacheException
     */
    private function read(string $key): array|false
    {
        $name = $this->name($key);
        $statement = $this->statement('SELECT ttl, data FROM ' . $this->table() . ' WHERE name = ?', [$name]);
        if ($statement === false) {
            return false;
        }
        $row = $statement->fetch(PdoConnection::FETCH_ASSOC);
        if (!\is_array($row)) {
            return false;
        }
        $expiresAt = is_numeric($row['ttl'] ?? null) ? (int) $row['ttl'] : null;
        if ($expiresAt !== null && $expiresAt < time()) {
            $this->delete($key);

            return false;
        }
        $data = $row['data'] ?? null;

        return [
            'ttl'  => $expiresAt,
            'data' => @unserialize(\is_string($data) ? $data : 'N;'),
        ];
    }

    /**
     * Prepares and executes a statement, returning it on success or false on a
     * driver error.
     *
     * @param string $sql
     * @param list<mixed> $params
     * @return PDOStatement|false
     * @throws CacheException
     */
    private function statement(string $sql, array $params = []): PDOStatement|false
    {
        try {
            $statement = $this->getPDO()->prepare($sql);
            if ($statement === false) {
                return false;
            }

            return $statement->execute($params) ? $statement : false;
        } catch (PDOException) {
            return false;
        }
    }

    /**
     * @return string The validated table name.
     * @throws CacheException If the option contains anything but [A-Za-z0-9_].
     */
    private function table(): string
    {
        $table = $this->getOption('table', 'cache');
        if (!\is_string($table) || preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            throw new CacheException('The PDO cache "table" option must contain only letters, digits and underscores.');
        }

        return $table;
    }

    /**
     * Escapes LIKE wildcards in $prefix and appends "%", so a prefix such as
     * "cache_" matches "cache_*" literally rather than treating "_" as a
     * single-character wildcard.
     *
     * @param string $prefix
     * @return string
     */
    private function likePattern(string $prefix): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix) . '%';
    }

    /**
     * @param string $key
     * @return string|null
     */
    private function nullableString(string $key): ?string
    {
        $value = $this->getOption($key);

        return \is_scalar($value) ? (string) $value : null;
    }
}
