<?php

/**
 * Rbac.php
 * ---------------------------------------------------------
 * Pengecekan akses berbasis Role & Menu (role_menu_access).
 * Dipakai di seluruh Admin/*.php dan api/* yang butuh cek izin
 * granular (view / create / edit / delete) sesuai peran user.
 *
 * CATATAN implementasi mengikuti dokumen kebutuhan (skema
 * aplikasi 23 Juli 2026):
 *  - Superadmin selalu bypass (akses penuh ke semua menu).
 *  - "Petugas Penanaman" untuk sementara memakai menu 'monitoring'
 *    untuk input laporan penanaman, karena dokumen belum
 *    menyediakan menu khusus "Penanaman" yang terpisah dari
 *    "Pemeliharaan" pada Admin panel yang sudah ada. Jika nanti
 *    dibuatkan menu khusus, cukup tambah baris baru di tabel
 *    `menus` + `role_menu_access`, tidak perlu ubah kode ini.
 * ---------------------------------------------------------
 */

class Rbac
{
    /** Cache in-memory per request supaya tidak query berulang */
    private static ?array $permissionCache = null;
    private static ?int $cachedRoleId = null;

    /**
     * Ambil role_id user yang sedang login.
     * Fallback: jika kolom role_id belum terisi (user lama sebelum
     * migrasi RBAC dijalankan / belum dipetakan), anggap sebagai
     * 'admin' (role_id=2, view-only) — paling aman, bukan superadmin.
     */
    public static function currentRoleId(PDO $pdo): int
    {
        $admin = $_SESSION['admin'] ?? null;
        if (!$admin) {
            return 0;
        }

        if (!empty($admin['role_id'])) {
            return (int) $admin['role_id'];
        }

        // Belum ada role_id di session (login sebelum migrasi RBAC).
        // Ambil langsung dari DB sekali, simpan balik ke session.
        $stmt = $pdo->prepare("SELECT role_id FROM t_users WHERE UserId = ?");
        $stmt->execute([$admin['UserId'] ?? 0]);
        $roleId = (int) ($stmt->fetchColumn() ?: 2);

        $_SESSION['admin']['role_id'] = $roleId;
        return $roleId;
    }

    public static function isSuperadmin(PDO $pdo): bool
    {
        return self::currentRoleId($pdo) === 1;
    }

    /**
     * Kode role user yang sedang login (mis. 'petugas_survey', 'validator',
     * 'tim_tangkas'). Dipakai untuk aksi yang memang khusus 1 peran saja
     * di alur pemangkasan berjenjang, di luar cek can_view/create/edit/delete.
     */
    public static function currentRoleCode(PDO $pdo): ?string
    {
        $roleId = self::currentRoleId($pdo);
        if ($roleId <= 0) {
            return null;
        }
        $stmt = $pdo->prepare("SELECT code FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $code = $stmt->fetchColumn();
        return $code ?: null;
    }

    /**
     * Ambil seluruh matriks izin (menu_code => [view,create,edit,delete])
     * untuk role tertentu.
     */
    public static function getPermissionsForRole(PDO $pdo, int $roleId): array
    {
        $stmt = $pdo->prepare(
            "SELECT m.code, rma.can_view, rma.can_create, rma.can_edit, rma.can_delete
             FROM menus m
             LEFT JOIN role_menu_access rma ON rma.menu_id = m.id AND rma.role_id = ?
             ORDER BY m.sort_order"
        );
        $stmt->execute([$roleId]);

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['code']] = [
                'view'   => (bool) $row['can_view'],
                'create' => (bool) $row['can_create'],
                'edit'   => (bool) $row['can_edit'],
                'delete' => (bool) $row['can_delete'],
            ];
        }
        return $result;
    }

    /** Matriks izin untuk user yang sedang login (di-cache per request) */
    public static function myPermissions(PDO $pdo): array
    {
        $roleId = self::currentRoleId($pdo);

        if (self::$permissionCache !== null && self::$cachedRoleId === $roleId) {
            return self::$permissionCache;
        }

        if ($roleId === 1) {
            // Superadmin: bypass, semua menu = akses penuh.
            $stmt = $pdo->query("SELECT code FROM menus");
            $full = [];
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $code) {
                $full[$code] = ['view' => true, 'create' => true, 'edit' => true, 'delete' => true];
            }
            self::$permissionCache = $full;
        } else {
            self::$permissionCache = self::getPermissionsForRole($pdo, $roleId);
        }

        self::$cachedRoleId = $roleId;
        return self::$permissionCache;
    }

    /**
     * Cek izin: Rbac::can($pdo, 'pengajuan', 'edit')
     * $action: 'view' | 'create' | 'edit' | 'delete'
     */
    public static function can(PDO $pdo, string $menuCode, string $action = 'view'): bool
    {
        if (self::isSuperadmin($pdo)) {
            return true;
        }

        $perms = self::myPermissions($pdo);
        return (bool) ($perms[$menuCode][$action] ?? false);
    }

    /**
     * Wajibkan izin tertentu, atau hentikan eksekusi (redirect / 403).
     * Taruh persis setelah Auth::requireLogin() di tiap halaman Admin.
     */
    public static function requireAccess(PDO $pdo, string $menuCode, string $action = 'view'): void
    {
        if (self::can($pdo, $menuCode, $action)) {
            return;
        }

        http_response_code(403);
        $backHref = 'index.php';
        echo <<<HTML
        <!doctype html>
        <html lang="id"><head><meta charset="utf-8">
        <title>Akses Ditolak - Si-TANGKAL</title>
        <style>
            body{font-family:system-ui,Arial,sans-serif;background:#f8f9fa;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
            .box{background:#fff;padding:2.5rem;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);text-align:center;max-width:420px;}
            .box i{font-size:2.5rem;color:#dc3545;}
            .box a{color:#198754;text-decoration:none;font-weight:600;}
        </style></head>
        <body>
            <div class="box">
                <div style="font-size:3rem;">&#128274;</div>
                <h4>Akses Ditolak</h4>
                <p style="color:#6c757d;">Peran Anda tidak memiliki izin "{$action}" untuk menu ini.</p>
                <a href="{$backHref}">&larr; Kembali ke Dashboard</a>
            </div>
        </body></html>
        HTML;
        exit;
    }

    /**
     * Daftar menu yang boleh dilihat user saat ini (dipakai membangun
     * sidebar secara dinamis). Mengembalikan array baris tabel `menus`
     * terurut sort_order, hanya yang can_view = true.
     */
    public static function accessibleMenus(PDO $pdo): array
    {
        $roleId = self::currentRoleId($pdo);

        if ($roleId === 1) {
            $stmt = $pdo->query("SELECT * FROM menus ORDER BY sort_order");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $pdo->prepare(
            "SELECT m.* FROM menus m
             INNER JOIN role_menu_access rma ON rma.menu_id = m.id
             WHERE rma.role_id = ? AND rma.can_view = 1
             ORDER BY m.sort_order"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
