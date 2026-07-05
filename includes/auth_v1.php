<?php
/**
 * includes/auth.php
 * ---------------------------------------------------------
 * Kelas Auth terpusat — SATU-SATUNYA logika login/session
 * untuk seluruh aplikasi (halaman publik maupun Admin).
 *
 * Login dilakukan lewat /login.php (di luar folder khusus).
 * Setelah berhasil, Auth::attempt() mengisi DUA bentuk data
 * session sekaligus supaya seluruh bagian aplikasi (yang lama
 * memakai $_SESSION['admin'], maupun yang baru memakai
 * Auth::user()) tetap selaras dan tidak perlu login dua kali:
 *
 *   - $_SESSION['admin']          -> dipakai Admin/* (legacy)
 *   - $_SESSION['sitangkal_user'] -> dipakai includes/header.php (publik)
 * ---------------------------------------------------------
 */

// Jika file ini dimuat langsung (bukan lewat /config.php), pastikan
// config utama & koneksi DB tetap ikut termuat agar tidak error.
if (!function_exists('getPDO')) {
    require_once __DIR__ . '/../config.php';
}

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        try {
            $pdo = getPDO();

            $stmt = $pdo->prepare("SELECT * FROM t_users WHERE Username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            /*
             * Password di database ternyata tersimpan sebagai MD5 hash
             * (contoh data: 32 karakter hex), bukan plaintext seperti
             * asumsi kode versi sebelumnya — sehingga login tidak akan
             * pernah berhasil sebelum ini. Di sini dicocokkan terhadap
             * MD5(password) DULU (sesuai data yang ada), lalu fallback
             * ke perbandingan plaintext untuk jaga-jaga kalau suatu saat
             * ada akun yang passwordnya belum di-hash.
             *
             * Jika nanti password dipindah ke password_hash() (disarankan,
             * jauh lebih aman daripada MD5), ganti pengecekan di bawah
             * menjadi:
             *   if (!password_verify($password, $user['Password'])) { return false; }
             */
            $storedPassword = $user['Password'];
            $isMd5Match       = hash_equals($storedPassword, md5($password));
            $isPlaintextMatch = hash_equals($storedPassword, $password);

            if (!$isMd5Match && !$isPlaintextMatch) {
                return false;
            }

            // Bentuk lama (dipakai di seluruh halaman Admin/*)
            $_SESSION['admin'] = $user;

            // Bentuk baru/ringkas (dipakai includes/header.php di halaman publik)
            $_SESSION['sitangkal_user'] = [
                'id'       => $user['UserId'] ?? null,
                'username' => $user['Username'] ?? $username,
                'name'     => $user['Name'] ?? $username,
                'email'    => $user['Email'] ?? null,
                'role'     => $user['Type'] ?? null,
            ];

            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin']) || isset($_SESSION['sitangkal_user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['sitangkal_user'] ?? null;
    }

    public static function admin(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin'], $_SESSION['sitangkal_user']);
        session_unset();
        session_destroy();
    }

    /**
     * Wajibkan login. $redirectTo dihitung relatif terhadap file
     * pemanggil — default mengarah ke /login.php di root project
     * (login SEKARANG SELALU di luar folder, tidak lagi di /login/).
     */
    public static function requireLogin(string $redirectTo = 'login.php'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}
