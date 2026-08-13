<?php

/**
 * RoleModel
 * Model CRUD untuk `roles` + pengelolaan matriks `role_menu_access`.
 */
class RoleModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->query(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM t_users u WHERE u.role_id = r.id) AS total_user
             FROM roles r
             ORDER BY r.id"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array|false
    {
        $stmt = $this->conn->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM roles WHERE code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM roles WHERE code = ?");
            $stmt->execute([$code]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(string $code, string $name, string $description): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO roles (code, name, description, is_system) VALUES (?, ?, ?, 0)"
        );
        $stmt->execute([$code, $name, $description]);
        return (int) $this->conn->lastInsertId();
    }

    public function update(int $id, string $name, string $description): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE roles SET name = ?, description = ? WHERE id = ?"
        );
        return $stmt->execute([$name, $description, $id]);
    }

    /** Role bawaan (is_system=1) dari dokumen kebutuhan tidak boleh dihapus */
    public function delete(int $id): bool|string
    {
        $role = $this->getById($id);
        if (!$role) {
            return 'Role tidak ditemukan.';
        }
        if ((int) $role['is_system'] === 1) {
            return 'Role bawaan (dari dokumen kebutuhan) tidak bisa dihapus, hanya bisa diedit izinnya.';
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM t_users WHERE role_id = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return 'Role masih dipakai oleh pengguna lain, pindahkan dulu pengguna tersebut ke role lain.';
        }

        $stmt = $this->conn->prepare("DELETE FROM roles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ------------------------------------------------------------
    // MATRIKS IZIN (role_menu_access)
    // ------------------------------------------------------------

    /** Semua menu + status izin (0/1) untuk 1 role, untuk render form matrix */
    public function getAccessMatrix(int $roleId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT m.id AS menu_id, m.code, m.label, m.icon,
                    COALESCE(rma.can_view, 0)   AS can_view,
                    COALESCE(rma.can_create, 0) AS can_create,
                    COALESCE(rma.can_edit, 0)   AS can_edit,
                    COALESCE(rma.can_delete, 0) AS can_delete
             FROM menus m
             LEFT JOIN role_menu_access rma ON rma.menu_id = m.id AND rma.role_id = ?
             ORDER BY m.sort_order"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Simpan matriks izin sekaligus (replace-all) untuk 1 role.
     * $permissions: [ menu_id => ['view'=>0/1,'create'=>0/1,'edit'=>0/1,'delete'=>0/1], ... ]
     */
    public function savePermissions(int $roleId, array $permissions): bool
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO role_menu_access (role_id, menu_id, can_view, can_create, can_edit, can_delete)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_create = VALUES(can_create),
                                          can_edit = VALUES(can_edit), can_delete = VALUES(can_delete)"
            );

            foreach ($permissions as $menuId => $p) {
                $stmt->execute([
                    $roleId,
                    (int) $menuId,
                    !empty($p['view']) ? 1 : 0,
                    !empty($p['create']) ? 1 : 0,
                    !empty($p['edit']) ? 1 : 0,
                    !empty($p['delete']) ? 1 : 0,
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('[RoleModel::savePermissions] ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * MenuModel
 * Model sederhana untuk membaca daftar menu (dipakai sidebar & RBAC).
 */
class MenuModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        return $this->conn->query("SELECT * FROM menus ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
    }
}
