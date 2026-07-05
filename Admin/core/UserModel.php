<?php

/**
 * UserModel
 * Model CRUD untuk tabel `t_users`.
 * Password disimpan sebagai MD5 agar selaras dengan pengecekan
 * login di includes/auth.php (Auth::attempt membandingkan dengan
 * MD5(password) terlebih dahulu).
 */
class UserModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(string $keyword = ''): array
    {
        $sql = "SELECT * FROM t_users";
        $params = [];

        if ($keyword !== '') {
            $sql .= " WHERE Username LIKE :kw OR Name LIKE :kw OR Email LIKE :kw OR Type LIKE :kw";
            $params[':kw'] = "%{$keyword}%";
        }

        $sql .= " ORDER BY UserId DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM t_users WHERE UserId = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM t_users WHERE Username = ? AND UserId != ?");
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM t_users WHERE Username = ?");
            $stmt->execute([$username]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $username, string $password, string $email, string $type, string $name): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO t_users (Username, Password, Email, Type, Name, CreatedDate)
             VALUES (:username, :password, :email, :type, :name, NOW())"
        );

        return $stmt->execute([
            ':username' => $username,
            ':password' => md5($password),
            ':email'    => $email,
            ':type'     => $type,
            ':name'     => $name,
        ]);
    }

    /**
     * Update data user. Jika $password kosong, password lama tidak diubah.
     */
    public function update(int $id, string $username, string $password, string $email, string $type, string $name): bool
    {
        if ($password !== '') {
            $stmt = $this->conn->prepare(
                "UPDATE t_users SET Username = :username, Password = :password, Email = :email, Type = :type, Name = :name
                 WHERE UserId = :id"
            );
            return $stmt->execute([
                ':username' => $username,
                ':password' => md5($password),
                ':email'    => $email,
                ':type'     => $type,
                ':name'     => $name,
                ':id'       => $id,
            ]);
        }

        $stmt = $this->conn->prepare(
            "UPDATE t_users SET Username = :username, Email = :email, Type = :type, Name = :name
             WHERE UserId = :id"
        );
        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':type'     => $type,
            ':name'     => $name,
            ':id'       => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM t_users WHERE UserId = ?");
        return $stmt->execute([$id]);
    }

    public function countAll(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM t_users")->fetchColumn();
    }
}
