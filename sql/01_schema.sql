-- ============================================================
-- Government Job Application System
-- Oracle 11g XE Schema (01_schema.sql)
-- User: govtjob
-- ============================================================

-- ── 1. USERS ────────────────────────────────────────────────
CREATE SEQUENCE seq_users START WITH 1 INCREMENT BY 1;

CREATE TABLE users (
    user_id        NUMBER(10)    PRIMARY KEY,
    email          VARCHAR2(150) NOT NULL UNIQUE,
    password_hash  VARCHAR2(255) NOT NULL,
    role           VARCHAR2(20)  NOT NULL CHECK (role IN ('admin','applicant','department')),
    is_active      NUMBER(1)     DEFAULT 1 CHECK (is_active IN (0,1)),
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE TRIGGER trg_users_id
BEFORE INSERT ON users FOR EACH ROW
BEGIN
    :NEW.user_id := seq_users.NEXTVAL;
END;
/

-- ── 2. DEPARTMENTS ──────────────────────────────────────────
CREATE SEQUENCE seq_departments START WITH 1 INCREMENT BY 1;

CREATE TABLE departments (
    department_id   NUMBER(10)    PRIMARY KEY,
    user_id         NUMBER(10)    NOT NULL REFERENCES users(user_id),
    department_name VARCHAR2(200) NOT NULL,
    contact_email   VARCHAR2(150),
    contact_phone   VARCHAR2(20),
    address         VARCHAR2(500),
    is_approved     NUMBER(1)     DEFAULT 0 CHECK (is_approved IN (0,1)),
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE TRIGGER trg_departments_id
BEFORE INSERT ON departments FOR EACH ROW
BEGIN
    :NEW.department_id := seq_departments.NEXTVAL;
END;
/

-- ── 3. JOB_CATEGORIES ───────────────────────────────────────
CREATE SEQUENCE seq_job_categories START WITH 1 INCREMENT BY 1;

CREATE TABLE job_categories (
    category_id   NUMBER(10)    PRIMARY KEY,
    category_name VARCHAR2(100) NOT NULL UNIQUE,
    description   VARCHAR2(500),
    is_active     NUMBER(1)     DEFAULT 1 CHECK (is_active IN (0,1))
);

CREATE OR REPLACE TRIGGER trg_job_categories_id
BEFORE INSERT ON job_categories FOR EACH ROW
BEGIN
    :NEW.category_id := seq_job_categories.NEXTVAL;
END;
/

-- ── 4. JOB_CIRCULARS ────────────────────────────────────────
CREATE SEQUENCE seq_job_circulars START WITH 1 INCREMENT BY 1;

CREATE TABLE job_circulars (
    circular_id      NUMBER(10)    PRIMARY KEY,
    department_id    NUMBER(10)    NOT NULL REFERENCES departments(department_id),
    category_id      NUMBER(10)    REFERENCES job_categories(category_id),
    job_title        VARCHAR2(200) NOT NULL,
    total_vacancies  NUMBER(5)     DEFAULT 1,
    requirements     CLOB,
    salary_range     VARCHAR2(100),
    deadline         DATE          NOT NULL,
    location         VARCHAR2(200),
    status           VARCHAR2(20)  DEFAULT 'draft' CHECK (status IN ('draft','published','closed','cancelled')),
    application_fee  NUMBER(10,2)  DEFAULT 0,
    published_at     TIMESTAMP,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE TRIGGER trg_job_circulars_id
BEFORE INSERT ON job_circulars FOR EACH ROW
BEGIN
    :NEW.circular_id := seq_job_circulars.NEXTVAL;
END;
/

-- ── 5. APPLICANT_PROFILES ───────────────────────────────────
CREATE SEQUENCE seq_applicant_profiles START WITH 1 INCREMENT BY 1;

CREATE TABLE applicant_profiles (
    profile_id     NUMBER(10)   PRIMARY KEY,
    user_id        NUMBER(10)   NOT NULL UNIQUE REFERENCES users(user_id),
    full_name      VARCHAR2(150),
    father_name    VARCHAR2(150),
    mother_name    VARCHAR2(150),
    dob            DATE,
    gender         VARCHAR2(10) CHECK (gender IN ('male','female','other')),
    national_id    VARCHAR2(20),
    phone          VARCHAR2(20),
    present_address  VARCHAR2(500),
    permanent_address VARCHAR2(500),
    photo_path     VARCHAR2(300),
    signature_path VARCHAR2(300),
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE TRIGGER trg_applicant_profiles_id
BEFORE INSERT ON applicant_profiles FOR EACH ROW
BEGIN
    :NEW.profile_id := seq_applicant_profiles.NEXTVAL;
END;
/

-- ── 6. APPLICANT_EDUCATIONS ─────────────────────────────────
CREATE SEQUENCE seq_applicant_educations START WITH 1 INCREMENT BY 1;

CREATE TABLE applicant_educations (
    education_id       NUMBER(10)    PRIMARY KEY,
    profile_id         NUMBER(10)    NOT NULL REFERENCES applicant_profiles(profile_id),
    degree_level       VARCHAR2(50)  NOT NULL,
    institution_name   VARCHAR2(200) NOT NULL,
    board_or_university VARCHAR2(150),
    passing_year       NUMBER(4),
    gpa_or_grade       VARCHAR2(20),
    certificate_path   VARCHAR2(300)
);

CREATE OR REPLACE TRIGGER trg_applicant_educations_id
BEFORE INSERT ON applicant_educations FOR EACH ROW
BEGIN
    :NEW.education_id := seq_applicant_educations.NEXTVAL;
END;
/

-- ── 7. APPLICANT_EXPERIENCES ────────────────────────────────
CREATE SEQUENCE seq_applicant_experiences START WITH 1 INCREMENT BY 1;

CREATE TABLE applicant_experiences (
    experience_id     NUMBER(10)    PRIMARY KEY,
    profile_id        NUMBER(10)    NOT NULL REFERENCES applicant_profiles(profile_id),
    organization_name VARCHAR2(200) NOT NULL,
    designation       VARCHAR2(150),
    start_date        DATE,
    end_date          DATE,
    responsibilities  CLOB
);

CREATE OR REPLACE TRIGGER trg_applicant_experiences_id
BEFORE INSERT ON applicant_experiences FOR EACH ROW
BEGIN
    :NEW.experience_id := seq_applicant_experiences.NEXTVAL;
END;
/

-- ── 8. APPLICATIONS ─────────────────────────────────────────
CREATE SEQUENCE seq_applications START WITH 1 INCREMENT BY 1;

CREATE TABLE applications (
    application_id     NUMBER(10)   PRIMARY KEY,
    profile_id         NUMBER(10)   NOT NULL REFERENCES applicant_profiles(profile_id),
    circular_id        NUMBER(10)   NOT NULL REFERENCES job_circulars(circular_id),
    status             VARCHAR2(20) DEFAULT 'pending' CHECK (status IN ('pending','verified','shortlisted','rejected','selected')),
    roll_number        VARCHAR2(30),
    merit_position     NUMBER(6),
    verification_note  VARCHAR2(500),
    submitted_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (profile_id, circular_id)
);

CREATE OR REPLACE TRIGGER trg_applications_id
BEFORE INSERT ON applications FOR EACH ROW
BEGIN
    :NEW.application_id := seq_applications.NEXTVAL;
END;
/

-- ── 9. PAYMENTS ─────────────────────────────────────────────
CREATE SEQUENCE seq_payments START WITH 1 INCREMENT BY 1;

CREATE TABLE payments (
    payment_id       NUMBER(10)    PRIMARY KEY,
    application_id   NUMBER(10)    NOT NULL REFERENCES applications(application_id),
    transaction_id   VARCHAR2(100) NOT NULL,
    amount           NUMBER(10,2)  NOT NULL,
    payment_method   VARCHAR2(30)  CHECK (payment_method IN ('bkash','nagad','rocket','bank','card')),
    status           VARCHAR2(20)  DEFAULT 'pending' CHECK (status IN ('pending','verified','rejected')),
    paid_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR2(100)
);

CREATE OR REPLACE TRIGGER trg_payments_id
BEFORE INSERT ON payments FOR EACH ROW
BEGIN
    :NEW.payment_id := seq_payments.NEXTVAL;
END;
/

-- ── 10. EXAM_SCHEDULES ──────────────────────────────────────
CREATE SEQUENCE seq_exam_schedules START WITH 1 INCREMENT BY 1;

CREATE TABLE exam_schedules (
    exam_id       NUMBER(10)    PRIMARY KEY,
    circular_id   NUMBER(10)    NOT NULL REFERENCES job_circulars(circular_id),
    exam_type     VARCHAR2(30)  CHECK (exam_type IN ('written','viva','practical','mcq')),
    exam_date     DATE          NOT NULL,
    exam_time     VARCHAR2(10),
    exam_center   VARCHAR2(300),
    instructions  CLOB,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

CREATE OR REPLACE TRIGGER trg_exam_schedules_id
BEFORE INSERT ON exam_schedules FOR EACH ROW
BEGIN
    :NEW.exam_id := seq_exam_schedules.NEXTVAL;
END;
/

-- ── 11. EXAM_RESULTS ────────────────────────────────────────
CREATE SEQUENCE seq_exam_results START WITH 1 INCREMENT BY 1;

CREATE TABLE exam_results (
    result_id       NUMBER(10)   PRIMARY KEY,
    application_id  NUMBER(10)   NOT NULL REFERENCES applications(application_id),
    exam_id         NUMBER(10)   NOT NULL REFERENCES exam_schedules(exam_id),
    marks_obtained  NUMBER(6,2),
    total_marks     NUMBER(6,2),
    result_status   VARCHAR2(20) CHECK (result_status IN ('pass','fail','absent')),
    published_at    TIMESTAMP
);

CREATE OR REPLACE TRIGGER trg_exam_results_id
BEFORE INSERT ON exam_results FOR EACH ROW
BEGIN
    :NEW.result_id := seq_exam_results.NEXTVAL;
END;
/

-- ── 12. NOTIFICATIONS ───────────────────────────────────────
CREATE SEQUENCE seq_notifications START WITH 1 INCREMENT BY 1;

CREATE TABLE notifications (
    notification_id   NUMBER(10)    PRIMARY KEY,
    user_id           NUMBER(10)    NOT NULL REFERENCES users(user_id),
    type              VARCHAR2(50),
    message           VARCHAR2(1000) NOT NULL,
    is_read           NUMBER(1)     DEFAULT 0 CHECK (is_read IN (0,1)),
    sent_at           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    related_entity_id NUMBER(10)
);

CREATE OR REPLACE TRIGGER trg_notifications_id
BEFORE INSERT ON notifications FOR EACH ROW
BEGIN
    :NEW.notification_id := seq_notifications.NEXTVAL;
END;
/

COMMIT;
