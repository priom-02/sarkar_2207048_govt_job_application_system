-- ============================================================
-- Government Job Application System
-- Seed Data (02_seed_data.sql)
-- ============================================================

-- ── Admin User (password: Admin@1234) ───────────────────────
-- password_hash is bcrypt of 'Admin@1234'
INSERT INTO users (email, password_hash, role, is_active)
VALUES (
    'admin@govtjob.bd',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    1
);

-- ── Job Categories ───────────────────────────────────────────
INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Education', 'Teaching and academic positions', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Health', 'Medical and healthcare positions', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Engineering', 'Civil, mechanical, electrical engineering', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Information Technology', 'Software, networking and IT positions', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Administration', 'Administrative and management positions', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Police & Defense', 'Law enforcement and defense positions', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Agriculture', 'Farming and agricultural positions', 1);

INSERT INTO job_categories (category_name, description, is_active)
VALUES ('Finance & Banking', 'Finance, accounting and banking positions', 1);

COMMIT;
