-- Etalase Digital Stella (gabungan)
CREATE DATABASE IF NOT EXISTS DB_Etalase_Digital
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE DB_Etalase_Digital;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(30) UNIQUE NOT NULL,
    kelas_jurusan VARCHAR(50) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'siswa') DEFAULT 'siswa',
    foto VARCHAR(255) NULL
);

SET @kolom_foto = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE users ADD COLUMN foto VARCHAR(255) NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'foto'
);
PREPARE tambah_kolom_foto FROM @kolom_foto;
EXECUTE tambah_kolom_foto;
DEALLOCATE PREPARE tambah_kolom_foto;

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_project VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    gambar VARCHAR(255) NOT NULL,
    link_project VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Akun default (password semuanya: password123) -- segera ganti setelah login pertama
INSERT IGNORE INTO users (nis, kelas_jurusan, nama_lengkap, email, password, role) VALUES
('12345', 'XII RPL 1', 'Super Admin Stella', 'superadmin@stella.sch.id', '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'super_admin'),
('67890', 'XII RPL 2', 'Admin Stella', 'admin@stella.sch.id', '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'admin'),
('54321', 'XII TKJ 1', 'Siswa Contoh', 'siswa@stella.sch.id', '$2y$10$7C8r/agxCXnosP8mnSlZbOsWoUaW06UUJtuIFkUHuShVNBJ2pz0S.', 'siswa');
