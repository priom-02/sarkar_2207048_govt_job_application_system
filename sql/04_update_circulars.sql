-- Alter job_circulars table to add columns from the ER diagram

ALTER TABLE job_circulars ADD (
    salary_min             NUMBER(10,2),
    salary_max             NUMBER(10,2),
    education_requirement  VARCHAR2(500),
    experience_requirement VARCHAR2(500),
    application_deadline   DATE
);

-- Copy existing data to preserve integrity
UPDATE job_circulars SET application_deadline = deadline WHERE application_deadline IS NULL;
UPDATE job_circulars SET salary_min = 0, salary_max = 0 WHERE salary_min IS NULL;

COMMIT;
