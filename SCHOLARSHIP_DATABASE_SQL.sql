-- ============================================
-- SQL QUERIES FOR SCHOLARSHIP ELIGIBILITY
-- ============================================

-- 1. CREATE SCHOLARSHIPS TABLE (if not exists)
CREATE TABLE IF NOT EXISTS scholarships (
    scholarship_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    amount INT,
    category VARCHAR(50), -- General, SC, ST, OBC
    education_level VARCHAR(100), -- Undergraduate, Postgraduate, etc.
    min_marks DECIMAL(5,2) DEFAULT 0,
    max_family_income INT DEFAULT 0,
    state_id INT,
    deadline DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(state_id)
);

-- 2. CREATE SCHOLARSHIP_APPLICATIONS TABLE
CREATE TABLE IF NOT EXISTS scholarship_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    scholarship_id INT NOT NULL,
    user_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes LONGTEXT,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(scholarship_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 3. ELIGIBILITY CHECK QUERY - Get all eligible scholarships for a student
SELECT 
    s.scholarship_id,
    s.title,
    s.amount,
    s.deadline,
    s.category,
    s.education_level,
    DATEDIFF(s.deadline, NOW()) as days_remaining,
    CASE 
        WHEN DATEDIFF(s.deadline, NOW()) <= 7 AND DATEDIFF(s.deadline, NOW()) >= 0 THEN 'urgent'
        WHEN DATEDIFF(s.deadline, NOW()) < 0 THEN 'expired'
        ELSE 'active'
    END as deadline_status,
    CASE
        WHEN sp.marks >= s.min_marks 
             AND sp.family_income <= s.max_family_income
             AND (s.category = 'General' OR s.category = sp.category OR sp.category LIKE CONCAT('%', s.category, '%'))
             AND (s.education_level IS NULL OR sp.education_level LIKE CONCAT('%', s.education_level, '%'))
             AND (s.state_id IS NULL OR s.state_id = sp.state_id)
        THEN 'eligible'
        WHEN sp.marks >= s.min_marks 
             AND sp.family_income <= s.max_family_income
        THEN 'partial'
        ELSE 'not_eligible'
    END as eligibility_status
FROM scholarships s
INNER JOIN student_profile sp ON 1=1
WHERE 
    s.status = 'active' 
    AND sp.user_id = [USER_ID]
    AND s.deadline >= CURDATE()
ORDER BY s.deadline ASC;

-- 4. ELIGIBILITY CHECK - Detailed breakdown for a specific scholarship
SELECT 
    s.scholarship_id,
    s.title,
    s.min_marks,
    s.max_family_income,
    s.category,
    s.education_level,
    s.state_id,
    sp.marks,
    sp.family_income,
    sp.category as student_category,
    sp.education_level as student_education,
    sp.state_id as student_state,
    CASE WHEN sp.marks >= s.min_marks THEN 'PASS' ELSE 'FAIL' END as marks_check,
    CASE WHEN sp.family_income <= s.max_family_income THEN 'PASS' ELSE 'FAIL' END as income_check,
    CASE WHEN s.category = 'General' OR s.category = sp.category THEN 'PASS' ELSE 'FAIL' END as category_check,
    CASE WHEN s.education_level IS NULL OR sp.education_level LIKE CONCAT('%', s.education_level, '%') THEN 'PASS' ELSE 'FAIL' END as education_check,
    CASE WHEN s.state_id IS NULL OR s.state_id = sp.state_id THEN 'PASS' ELSE 'FAIL' END as state_check
FROM scholarships s
INNER JOIN student_profile sp ON sp.user_id = [USER_ID]
WHERE s.scholarship_id = [SCHOLARSHIP_ID];

-- 5. GET SCHOLARSHIPS EXPIRING WITHIN 7 DAYS
SELECT 
    scholarship_id,
    title,
    deadline,
    DATEDIFF(deadline, NOW()) as days_left,
    amount
FROM scholarships
WHERE 
    status = 'active'
    AND deadline <= DATE_ADD(NOW(), INTERVAL 7 DAY)
    AND deadline >= NOW()
ORDER BY deadline ASC;

-- 6. SEARCH AND FILTER SCHOLARSHIPS
SELECT 
    s.scholarship_id,
    s.title,
    s.description,
    s.amount,
    s.deadline,
    s.category,
    s.education_level,
    st.state_name
FROM scholarships s
LEFT JOIN states st ON s.state_id = st.state_id
WHERE 
    s.status = 'active'
    AND (s.title LIKE CONCAT('%', [SEARCH_TERM], '%') 
         OR s.description LIKE CONCAT('%', [SEARCH_TERM], '%'))
    AND (s.category = [CATEGORY] OR [CATEGORY] = '')
    AND (s.education_level LIKE CONCAT('%', [EDUCATION], '%') OR [EDUCATION] = '')
    AND (s.state_id = [STATE_ID] OR [STATE_ID] = '')
ORDER BY 
    CASE 
        WHEN [SORT] = 'deadline' THEN s.deadline
        WHEN [SORT] = 'title' THEN s.title
        WHEN [SORT] = 'newest' THEN s.created_date
    END
LIMIT [OFFSET], [LIMIT];

-- 7. STUDENT PROFILE UPDATE (Add new columns if missing)
ALTER TABLE student_profile 
ADD COLUMN IF NOT EXISTS full_name VARCHAR(255),
ADD COLUMN IF NOT EXISTS email VARCHAR(255),
ADD COLUMN IF NOT EXISTS disability_type VARCHAR(100),
ADD COLUMN IF NOT EXISTS parent_name VARCHAR(255),
ADD COLUMN IF NOT EXISTS parent_occupation VARCHAR(255),
ADD COLUMN IF NOT EXISTS parent_contact VARCHAR(20);

-- 8. APPLICATION TRACKING
SELECT 
    sa.application_id,
    sa.scholarship_id,
    s.title,
    s.amount,
    sa.application_date,
    sa.status
FROM scholarship_applications sa
INNER JOIN scholarships s ON sa.scholarship_id = s.scholarship_id
WHERE sa.user_id = [USER_ID]
ORDER BY sa.application_date DESC;

-- 9. ELIGIBILITY PERCENTAGE CALCULATION
SELECT 
    s.scholarship_id,
    s.title,
    CASE
        WHEN sp.marks >= s.min_marks THEN 30
        ELSE 0
    END +
    CASE
        WHEN sp.family_income <= s.max_family_income THEN 25
        ELSE 0
    END +
    CASE
        WHEN s.category = 'General' OR s.category = sp.category THEN 20
        ELSE 0
    END +
    CASE
        WHEN s.education_level IS NULL OR sp.education_level LIKE CONCAT('%', s.education_level, '%') THEN 15
        ELSE 0
    END +
    CASE
        WHEN s.state_id IS NULL OR s.state_id = sp.state_id THEN 10
        ELSE 0
    END as eligibility_percentage
FROM scholarships s
INNER JOIN student_profile sp ON sp.user_id = [USER_ID]
WHERE s.status = 'active';

-- ============================================
-- USEFUL INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_scholarship_status ON scholarships(status);
CREATE INDEX idx_scholarship_deadline ON scholarships(deadline);
CREATE INDEX idx_scholarship_category ON scholarships(category);
CREATE INDEX idx_scholarship_education ON scholarships(education_level);
CREATE INDEX idx_scholarship_state ON scholarships(state_id);
CREATE INDEX idx_student_profile_user ON student_profile(user_id);
CREATE INDEX idx_applications_user ON scholarship_applications(user_id);
CREATE INDEX idx_applications_scholarship ON scholarship_applications(scholarship_id);
