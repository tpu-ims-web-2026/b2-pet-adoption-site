-- =====================================================================
-- PetPal - database.sql
-- Her üye kendi bloğunu ekler; son sıralamayı Member 3 yapar
-- (yabancı anahtarla başvurulan tablolar önce gelmeli).
-- Kural: bu dosyada CREATE DATABASE veya USE satırı OLMAZ (InfinityFree için).
-- =====================================================================

SET NAMES utf8mb4;                                   -- Türkçe karakterler (ı, ş, ğ, İ) doğru aktarılsın


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

-- =====================================================================
-- M1: pets, pet_categories                         (Sahibi: Member 1)
-- ÖNEMLİ: pets.created_by sütunu users tablosuna bağlı olduğu için
-- bu blok, Member 2'nin users tablosundan SONRA çalıştırılmalıdır.
-- =====================================================================

-- ---------------------------------------------------------------------
-- pet_categories: hayvan türleri (Dog, Cat, Bird, Rabbit, Other)
-- Kullanan dosyalar: pets.php (kategori filtresi), admin/pet_form.php (tür seçimi)
-- ---------------------------------------------------------------------
CREATE TABLE pet_categories (
    category_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,   -- birincil anahtar
    name         VARCHAR(50)  NOT NULL,                  -- tür adı, örn. 'Dog'
    description  VARCHAR(255) NULL,                      -- kısa açıklama (boş olabilir)
    PRIMARY KEY (category_id),                           -- her kategori tek bir id ile bulunur
    UNIQUE KEY uq_pet_categories_name (name)             -- aynı isimde iki kategori olamaz
) ENGINE=InnoDB                                          -- yabancı anahtar ve transaction için InnoDB şart
  DEFAULT CHARSET=utf8mb4                                -- emoji ve Türkçe dahil tüm karakterler
  COLLATE=utf8mb4_unicode_ci;                            -- sıralama/karşılaştırma kuralı (yönergedeki gibi)


-- ---------------------------------------------------------------------
-- pets: sahiplendirilecek hayvanlar
-- Kullanan dosyalar: index.php, pets.php, pet.php, admin/pets.php,
-- admin/pet_form.php, admin/pet_delete.php, includes/footer.php,
-- apply.php (M2) ve admin/application.php (M3, onay transaction'ı)
-- ---------------------------------------------------------------------
CREATE TABLE pets (
    pet_id       INT UNSIGNED      NOT NULL AUTO_INCREMENT,                 -- birincil anahtar (pet.php?id=...)
    category_id  INT UNSIGNED      NOT NULL,                                -- hangi türde (pet_categories'e bağlı)
    name         VARCHAR(60)       NOT NULL,                                -- hayvanın adı
    breed        VARCHAR(80)       NULL,                                    -- cinsi; bilinmiyorsa NULL
    gender       ENUM('male','female','unknown') NOT NULL,                  -- cinsiyet (pets.php filtresi)
    age_months   SMALLINT UNSIGNED NOT NULL,                                -- yaş AY olarak; sayfada "1 year 3 months" diye gösterilir
    size         ENUM('small','medium','large')  NOT NULL,                  -- boyut (pets.php filtresi)
    city         VARCHAR(60)       NOT NULL,                                -- bulunduğu şehir (pets.php filtresi)
    description  TEXT              NOT NULL,                                -- detay sayfasındaki açıklama
    vaccinated   TINYINT(1)        NOT NULL DEFAULT 0,                      -- aşılı mı: 1 = evet, 0 = hayır
    image        VARCHAR(255)      NULL,                                    -- SADECE dosya adı, örn. 'a3f9c1.jpg' (klasör: uploads/pets/)
    status       ENUM('available','adopted','hidden') NOT NULL DEFAULT 'available', -- 'adopted' sadece admin onayıyla atanır (imza özelliği)
    created_by   INT UNSIGNED      NULL,                                    -- hayvanı ekleyen admin (users'a bağlı)
    created_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,      -- eklenme zamanı (ana sayfada "en yeni 6" için)
    updated_at   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,             -- her güncellemede otomatik değişir

    PRIMARY KEY (pet_id),                                                   -- her hayvan tek bir id ile bulunur
    KEY idx_pets_status (status),                                           -- WHERE status = ? sorgularını hızlandırır
    KEY idx_pets_city (city),                                               -- şehir filtresini hızlandırır
    KEY idx_pets_created (created_at),                                      -- ORDER BY created_at DESC için

    CONSTRAINT fk_pets_category                                             -- tür bağlantısı
        FOREIGN KEY (category_id) REFERENCES pet_categories (category_id)
        ON DELETE RESTRICT                                                  -- içinde hayvan olan kategori silinemez
        ON UPDATE CASCADE,                                                  -- kategori id'si değişirse burada da değişir
    CONSTRAINT fk_pets_created_by                                           -- ekleyen admin bağlantısı
        FOREIGN KEY (created_by) REFERENCES users (user_id)
        ON DELETE SET NULL                                                  -- admin silinirse hayvan kalır, created_by NULL olur
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Örnek veriler: kategoriler
-- id'ler sabit verildi; pets.php'deki filtre değerleri (1-5) bunlarla aynı.
-- ---------------------------------------------------------------------
INSERT INTO pet_categories (category_id, name, description) VALUES
    (1, 'Dog',    'Dogs of every size, from puppies to seniors'),   -- köpek
    (2, 'Cat',    'Cats and kittens'),                              -- kedi
    (3, 'Bird',   'Budgies, canaries and other birds'),             -- kuş
    (4, 'Rabbit', 'Rabbits that need a calm home'),                 -- tavşan
    (5, 'Other',  'Turtles, hamsters and other small animals');     -- diğer


-- ---------------------------------------------------------------------
-- Örnek veriler: 12 hayvan (tüm isimler, şehirler ve hikâyeler uydurma)
-- id'ler sabit verildi ki Member 2 ve 3'ün örnek başvuruları bunlara bağlanabilsin.
--   1-8, 11, 12  -> available (sitede görünür, başvuru alır)
--   9  (Mırmır)  -> adopted   (Member 2/3'ün örnek verisinde bu hayvan için
--                              'approved' bir başvuru olmalı, yoksa tutarsız olur)
--   10 (Gölge)   -> hidden    (sadece admin görür)
-- image hepsinde NULL: sayfalar images/no-photo.png gösterir.
-- created_by NULL: admin hesabı users tablosuna eklenince UPDATE ile doldurulabilir.
-- ---------------------------------------------------------------------
INSERT INTO pets
    (pet_id, category_id, name, breed, gender, age_months, size, city, description, vaccinated, image, status, created_by, created_at)
VALUES
    (1,  1, 'Luna',    'Golden Retriever mix', 'female',  15, 'medium', 'İzmir',
         'Luna is a friendly and playful dog who loves long walks.\nShe gets along well with children and other dogs.',
         1, NULL, 'available', NULL, '2026-11-20 10:00:00'),
    (2,  2, 'Karamel', 'Tabby',                'male',    10, 'small',  'Ankara',
         'A curious young cat who follows people around the house and purrs loudly.',
         1, NULL, 'available', NULL, '2026-11-19 14:30:00'),
    (3,  4, 'Pamuk',   'Angora',               'female',  18, 'small',  'İstanbul',
         'A soft white rabbit who likes fresh greens and a quiet corner.',
         1, NULL, 'available', NULL, '2026-11-18 09:15:00'),
    (4,  2, 'Zeytin',  NULL,                   'female',  36, 'small',  'Bursa',
         'A calm black cat. She is shy at first but very loving once she trusts you.',
         1, NULL, 'available', NULL, '2026-11-17 16:45:00'),
    (5,  3, 'Boncuk',  'Budgerigar',           'unknown',  7, 'small',  'Antalya',
         'A cheerful green budgie who sings every morning.',
         0, NULL, 'available', NULL, '2026-11-16 11:20:00'),
    (6,  1, 'Duman',   'Anatolian Shepherd mix','male',    48, 'large',  'İzmir',
         'A gentle giant who needs a house with a garden and daily exercise.',
         1, NULL, 'available', NULL, '2026-11-15 13:00:00'),
    (7,  1, 'Fıstık',  'Terrier mix',          'female',   5, 'small',  'Ankara',
         'An energetic puppy who is learning to walk on a lead.',
         0, NULL, 'available', NULL, '2026-11-14 10:30:00'),
    (8,  2, 'Tarçın',  'British Shorthair mix','male',    24, 'medium', 'İstanbul',
         'A relaxed cat who enjoys naps on the sofa and gentle brushing.',
         1, NULL, 'available', NULL, '2026-11-13 15:10:00'),
    (9,  2, 'Mırmır',  NULL,                   'male',    30, 'small',  'Ankara',
         'A calm cat who enjoys sleeping in the sun.',
         1, NULL, 'adopted',   NULL, '2026-11-05 12:00:00'),
    (10, 1, 'Gölge',   'Kangal',               'male',     8, 'large',  'Bursa',
         'Still being checked by the vet, not ready for adoption yet.',
         0, NULL, 'hidden',    NULL, '2026-11-21 09:00:00'),
    (11, 5, 'Tosbik',  'Red-eared slider',     'unknown', 60, 'small',  'Antalya',
         'A slow but curious turtle who needs a large tank.',
         0, NULL, 'available', NULL, '2026-11-12 17:40:00'),
    (12, 1, 'Paşa',    'Beagle',               'male',    84, 'medium', 'İstanbul',
         'An older, calm dog looking for a quiet home to spend his golden years.',
         1, NULL, 'available', NULL, '2026-11-11 08:50:00');

-- M2 (devam): adoption_applications

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

