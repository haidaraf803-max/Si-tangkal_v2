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
        $sql = "SELECT u.*, r.name AS role_name, r.code AS role_code
                FROM t_users u
                LEFT JOIN roles r ON r.id = u.role_id";
        $params = [];

        if ($keyword !== '') {
            $sql .= " WHERE u.Username LIKE :kw OR u.Name LIKE :kw OR u.Email LIKE :kw OR u.Type LIKE :kw";
            $params[':kw'] = "%{$keyword}%";
        }

        $sql .= " ORDER BY u.UserId DESC";

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

    /**
     * $roleId: id dari tabel `roles` (RBAC). $type tetap disimpan sebagai
     * label tampilan (kompatibilitas kode lama yang membaca t_users.Type),
     * otomatis diselaraskan dengan nama role di Admin/users.php.
     */
    public function create(string $username, string $password, string $email, string $type, string $name, ?int $roleId = null): bool
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO t_users (Username, Password, Email, Type, Name, role_id, CreatedDate)
             VALUES (:username, :password, :email, :type, :name, :role_id, NOW())"
        );

        return $stmt->execute([
            ':username' => $username,
            ':password' => md5($password),
            ':email'    => $email,
            ':type'     => $type,
            ':name'     => $name,
            ':role_id'  => $roleId,
        ]);
    }

    /**
     * Update data user. Jika $password kosong, password lama tidak diubah.
     */
    public function update(int $id, string $username, string $password, string $email, string $type, string $name, ?int $roleId = null): bool
    {
        if ($password !== '') {
            $stmt = $this->conn->prepare(
                "UPDATE t_users SET Username = :username, Password = :password, Email = :email, Type = :type, Name = :name, role_id = :role_id
                 WHERE UserId = :id"
            );
            return $stmt->execute([
                ':username' => $username,
                ':password' => md5($password),
                ':email'    => $email,
                ':type'     => $type,
                ':name'     => $name,
                ':role_id'  => $roleId,
                ':id'       => $id,
            ]);
        }

        $stmt = $this->conn->prepare(
            "UPDATE t_users SET Username = :username, Email = :email, Type = :type, Name = :name, role_id = :role_id
             WHERE UserId = :id"
        );
        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':type'     => $type,
            ':name'     => $name,
            ':role_id'  => $roleId,
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
