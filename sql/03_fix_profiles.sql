-- Fix: Insert blank applicant profiles for all applicant users who have none
INSERT INTO applicant_profiles (user_id, full_name, phone)
SELECT u.user_id, u.email, ''
FROM users u
WHERE u.role = 'applicant'
AND NOT EXISTS (SELECT 1 FROM applicant_profiles p WHERE p.user_id = u.user_id);

COMMIT;

-- Verify
SELECT u.user_id, u.email, u.role,
       NVL(TO_CHAR(p.profile_id),'MISSING') AS profile,
       NVL(TO_CHAR(d.department_id),'MISSING') AS dept
FROM users u
LEFT JOIN applicant_profiles p ON u.user_id = p.user_id
LEFT JOIN departments d ON u.user_id = d.user_id
ORDER BY u.user_id;

EXIT;
