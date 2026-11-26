SET FOREIGN_KEY_CHECKS = 0;

-- M2: users, adoption_applications

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    city VARCHAR(60) NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS adoption_applications (
    application_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    pet_id INT UNSIGNED NOT NULL,
    home_type ENUM('apartment', 'house', 'house_garden', 'farm') NOT NULL,
    has_other_pets TINYINT(1) NOT NULL DEFAULT 0,
    experience VARCHAR(500) NULL,
    motivation TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(255) NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (application_id),
    UNIQUE KEY unique_user_pet (user_id, pet_id),
    CONSTRAINT fk_applications_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_applications_pet
        FOREIGN KEY (pet_id) REFERENCES pets (pet_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_applications_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test kullanicilari (Tum test hesaplari icin sifre: Test1234!)
INSERT INTO users (full_name, email, password_hash, phone, city, role, is_active) VALUES
('PetPal Admin', 'admin@petpal.test', '$2y$12$hj4BlDcKBjg.hdWKPBuzOu1W6Ky8y0Lqx/ps6T59w9q5yB/kNa3jS', '+90 555 000 0001', 'Istanbul', 'admin', 1),
('Deniz Yilmaz', 'user1@petpal.test', '$2y$12$3/C7DAmKXROy5RfW0bgY..ZXnHbzIHlpnNofDGvFXcZ3BulmIfH.G', '+90 555 000 0002', 'Ankara', 'user', 1),
('Ece Kaya', 'user2@petpal.test', '$2y$12$/MO7uDaGPhu0qgfDoh0vIu4HnI/DDymSux7yGNAzAmESZL7t/ifK.', '+90 555 000 0003', 'Izmir', 'user', 1);

-- M3: contact_messages

CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    CONSTRAINT fk_contact_messages_user
        FOREIGN KEY (user_id) REFERENCES users (user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample contact messages
INSERT INTO contact_messages (user_id, name, email, subject, body, is_read) VALUES
(2, 'Deniz Yilmaz', 'user1@petpal.test', 'Question about adoption process', 'Hello, how long does it usually take for an adoption application to be reviewed after submission?', 0),
(NULL, 'Liam Brooks', 'liam.brooks@example.test', 'Volunteering at the shelter', 'Hi PetPal team, I would love to help out on weekends. Do you accept new volunteers at the Istanbul shelter?', 0),
(3, 'Ece Kaya', 'user2@petpal.test', 'Vaccination records', 'Thank you for the quick response earlier. Will we receive the full vaccination booklet when picking up an adopted pet?', 1);

SET FOREIGN_KEY_CHECKS = 1;
