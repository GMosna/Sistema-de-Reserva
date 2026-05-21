<?php
namespace App\Models;

use App\Database;

abstract class Model
{
    protected static string $table  = '';
    protected static string $pk     = 'id';
    protected array $attributes     = [];

    // ── Connection ────────────────────────────────────────────────────

    protected static function db(): \mysqli
    {
        return Database::get();
    }

    // ── Hydration ─────────────────────────────────────────────────────

    public static function hydrate(array $row): static
    {
        $instance = new static();
        $instance->attributes = $row;
        return $instance;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    // ── Type inference ────────────────────────────────────────────────

    private static function inferTypes(array $params): string
    {
        return implode('', array_map(function (mixed $v): string {
            if (is_int($v))   return 'i';
            if (is_float($v)) return 'd';
            return 's';
        }, $params));
    }

    // ── Query helpers ─────────────────────────────────────────────────

    public static function find(int $id): ?static
    {
        $t    = static::$table;
        $pk   = static::$pk;
        $stmt = self::db()->prepare("SELECT * FROM `{$t}` WHERE `{$pk}` = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? static::hydrate($row) : null;
    }

    public static function all(string $orderBy = ''): array
    {
        $t   = static::$table;
        $sql = "SELECT * FROM `{$t}`" . ($orderBy ? " ORDER BY {$orderBy}" : '');
        $res = self::db()->query($sql);
        return $res ? array_map(fn($r) => static::hydrate($r), $res->fetch_all(MYSQLI_ASSOC)) : [];
    }

    /**
     * @param string $conditions  SQL after WHERE, e.g. "status = ? AND sala_id = ?"
     * @param array  $params      Values for placeholders
     * @param string $extra       Extra SQL appended after WHERE clause (ORDER BY, LIMIT…)
     */
    public static function where(string $conditions, array $params = [], string $extra = ''): array
    {
        $t    = static::$table;
        $sql  = "SELECT * FROM `{$t}` WHERE {$conditions}" . ($extra ? " {$extra}" : '');
        $stmt = self::db()->prepare($sql);
        if ($params) {
            $types = self::inferTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_map(fn($r) => static::hydrate($r), $rows);
    }

    public static function first(string $conditions, array $params = []): ?static
    {
        $results = static::where($conditions, $params, 'LIMIT 1');
        return $results[0] ?? null;
    }

    // ── Writes ────────────────────────────────────────────────────────

    /**
     * INSERT a row. Keys must match column names.
     * Returns last insert ID.
     */
    public static function insert(array $data): int
    {
        $t       = static::$table;
        $columns = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = self::db()->prepare("INSERT INTO `{$t}` ({$columns}) VALUES ({$placeholders})");
        $values = array_values($data);
        $stmt->bind_param(self::inferTypes($values), ...$values);
        $stmt->execute();
        $id = self::db()->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * UPDATE row by primary key.
     */
    public static function update(int $id, array $data): bool
    {
        $t    = static::$table;
        $pk   = static::$pk;
        $set  = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $stmt = self::db()->prepare("UPDATE `{$t}` SET {$set} WHERE `{$pk}` = ?");
        $values = [...array_values($data), $id];
        $stmt->bind_param(self::inferTypes($values), ...$values);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected >= 0;
    }

    public static function delete(int $id): bool
    {
        $t    = static::$table;
        $pk   = static::$pk;
        $stmt = self::db()->prepare("DELETE FROM `{$t}` WHERE `{$pk}` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    // ── Validation ────────────────────────────────────────────────────

    /**
     * Override in subclasses. Return array of error strings; empty = valid.
     */
    public function validate(): array
    {
        return [];
    }
}
