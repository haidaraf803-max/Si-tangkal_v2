<?php

/**
 * NotificationModel
 * Notifikasi bisa ditujukan ke 1 user (`user_id`) atau ke semua
 * pemegang 1 role (`role_id`) — dipakai alur pemangkasan berjenjang:
 * pengajuan baru -> semua Petugas Survey, hasil survey -> Validator,
 * validasi disetujui -> Tim Tangkas, eksekusi selesai -> Validator & Admin.
 */
class NotificationModel
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function notifyUser(int $userId, string $title, string $message, ?string $link = null, ?int $pengajuanId = null): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO notifications (user_id, pengajuan_id, title, message, link) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $pengajuanId, $title, $message, $link]);
    }

    public function notifyRole(int $roleId, string $title, string $message, ?string $link = null, ?int $pengajuanId = null): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO notifications (role_id, pengajuan_id, title, message, link) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$roleId, $pengajuanId, $title, $message, $link]);
    }

    /** Notifikasi untuk user yang sedang login: miliknya sendiri + milik role-nya */
    public function getForUser(int $userId, int $roleId, int $limit = 10): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM notifications
             WHERE user_id = :uid OR role_id = :rid
             ORDER BY created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':rid', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUnread(int $userId, int $roleId): int
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR role_id = ?) AND is_read = 0"
        );
        $stmt->execute([$userId, $roleId]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $id): bool
    {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function markAllRead(int $userId, int $roleId): bool
    {
        $stmt = $this->conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? OR role_id = ?"
        );
        return $stmt->execute([$userId, $roleId]);
    }
}
