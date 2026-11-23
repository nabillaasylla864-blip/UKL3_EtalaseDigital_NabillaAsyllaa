<?php
/** Hanya izinkan link http/https (mencegah javascript: dsb). */
function valid_http_url(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true);
}

/**
 * Validasi & simpan gambar project. Mengembalikan nama file baru,
 * atau null (dan mengisi $error) jika gagal.
 */
function save_project_image(array $file, string $uploadDir, ?string &$error): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = "Pilih gambar karya digital terlebih dahulu!";
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = "Ukuran berkas gambar tidak boleh lebih dari 2MB!";
        return null;
    }

    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info    = @getimagesize($file['tmp_name']);
    if ($info === false || !isset($allowed[$info[2]])) {
        $error = "Format gambar harus berupa JPG, JPEG, PNG, atau WEBP!";
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid('project_', true) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . '/' . $newName)) {
        $error = "Gagal mengunggah berkas gambar ke server.";
        return null;
    }
    return $newName;
}

function delete_project_image(string $uploadDir, string $fileName): void {
    $path = rtrim($uploadDir, '/\\') . '/' . basename($fileName);
    if ($fileName !== '' && is_file($path)) {
        unlink($path);
    }
}

/**
 * Hapus user (hanya jika rolenya ada di $allowedRoles) beserta file gambar project miliknya.
 */
function delete_user_with_files(PDO $pdo, int $id, array $allowedRoles): bool {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    if (!$user || !in_array($user['role'], $allowedRoles, true)) {
        return false;
    }

    $imgs = $pdo->prepare("SELECT gambar FROM projects WHERE user_id = :id");
    $imgs->execute([':id' => $id]);
    $files = $imgs->fetchAll(PDO::FETCH_COLUMN);

    $photoStmt = $pdo->prepare("SELECT foto FROM users WHERE id = :id");
    $photoStmt->execute([':id' => $id]);
    $photo = $photoStmt->fetchColumn();

    $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $del->execute([':id' => $id]);

    foreach ($files as $f) {
        delete_project_image(UPLOAD_DIR, $f);
    }
    delete_profile_image(UPLOAD_DIR, $photo ?: null);
    return true;
}

/** True jika error PDO adalah duplikat (email sudah terdaftar). */
function is_duplicate_error(PDOException $e): bool {
    return ($e->errorInfo[1] ?? 0) === 1062;
}

function save_profile_image(array $file, string $uploadDir, ?string &$error): ?string {
    $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($uploadError !== UPLOAD_ERR_OK) {
        $error = "Gagal mengunggah foto profil!";
        return null;
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        $error = "Ukuran foto profil tidak boleh lebih dari 2MB!";
        return null;
    }

    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($file['tmp_name'] ?? '');
    if ($info === false || !isset($allowed[$info[2]])) {
        $error = "Foto profil harus berupa JPG, PNG, atau WEBP!";
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newName = uniqid('profile_', true) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . '/' . $newName)) {
        $error = "Gagal menyimpan foto profil ke server!";
        return null;
    }
    return $newName;
}

function delete_profile_image(string $uploadDir, ?string $fileName): void {
    if ($fileName) {
        $path = rtrim($uploadDir, '/\\') . '/' . basename($fileName);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

/** Buat URL foto yang konsisten, juga mendukung path lama "../uploads/nama.jpg". */
function profile_image_url(?string $fileName, string $base = ''): ?string {
    if (!$fileName) {
        return null;
    }
    $safeName = basename(str_replace('\\', '/', $fileName));
    return $base . 'uploads/' . rawurlencode($safeName);
}
