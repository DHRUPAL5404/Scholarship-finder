# Student Dashboard - Scholarship Finder

## Overview
A responsive, feature-rich student dashboard for the ScholarMatch scholarship finder platform. This dashboard allows students to discover, search, filter, and apply for scholarships based on their profile information.

## Features Implemented

### 1. Frontend Features
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices
- **Modern UI**: Gradient background, card-based layout with smooth animations
- **Search & Filter System**:
  - Search by scholarship title or description
  - Filter by category (SC/ST/OBC/General)
  - Filter by education level (UG/PG/Diploma)
  - Filter by state/district
  - Sort options: Deadline (nearest first), Title (A-Z), Newest First
- **Card View Display**:
  - Scholarship title and amount
  - Short description
  - Deadline with urgency indicator
  - Eligibility status with color coding
  - Issues/problems with eligibility (if any)
  - Quick action buttons
- **Color Coding**:
  - 🟢 Green: Eligible
  - 🟡 Yellow: Partial (requires documents)
  - 🔴 Red: Not Eligible
- **Pagination**: Navigate through multiple pages of results (10 per page)
- **Alerts**: 
  - Warning if scholarships are expiring within 7 days
  - Alert to complete profile for accurate eligibility
- **Responsive Header**: Welcome message, profile link, logout option

### 2. Backend Features (PHP)
- **Session Management**: User authentication verification
- **Profile Fetching**: Retrieves complete student profile data
- **Dynamic Eligibility Checking**: 
  - Real-time comparison with scholarship requirements
  - Calculates eligibility percentage
  - Identifies specific issues blocking eligibility
- **Smart Filtering**: Multi-criteria filtering with proper SQL sanitization
- **Pagination Support**: Efficient database queries with limit/offset
- **Error Handling**: Graceful handling of missing data and errors

### 3. Eligibility Algorithm
Compares student profile with scholarship requirements:
- **Marks Check**: Minimum marks requirement
- **Family Income Check**: Maximum family income limit
- **Category Check**: SC/ST/OBC/General category matching
- **Education Level Check**: Ensures education level compatibility
- **State Check**: Geographic eligibility
- **Eligibility Score Calculation**: 
  - 80%+ = Eligible (Green)
  - 50-79% = Partial (Yellow)
  - <50% = Not Eligible (Red)

## File Structure

```
scholarmatch/
├── student_dashboard.php              # Main dashboard page
├── get_scholarship_eligibility.php    # API for eligibility data
├── SCHOLARSHIP_DATABASE_SQL.sql       # Database setup queries
├── profile.php                        # Student profile management
├── db.php                             # Database connection
└── ... (other supporting files)
```

## Installation & Setup

### 1. Database Setup
Run the following SQL queries to set up the required tables:

```sql
-- Create scholarships table
CREATE TABLE scholarships (
    scholarship_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    amount INT,
    category VARCHAR(50),
    education_level VARCHAR(100),
    min_marks DECIMAL(5,2) DEFAULT 0,
    max_family_income INT DEFAULT 0,
    state_id INT,
    deadline DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create applications table
CREATE TABLE scholarship_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    scholarship_id INT NOT NULL,
    user_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'
);

-- Add indexes for performance
CREATE INDEX idx_scholarship_status ON scholarships(status);
CREATE INDEX idx_scholarship_deadline ON scholarships(deadline);
CREATE INDEX idx_scholarship_state ON scholarships(state_id);
```

### 2. Update Student Profile Table
Ensure your student_profile table has these columns:
```sql
ALTER TABLE student_profile 
ADD COLUMN full_name VARCHAR(255),
ADD COLUMN email VARCHAR(255),
ADD COLUMN disability_type VARCHAR(100),
ADD COLUMN parent_name VARCHAR(255),
ADD COLUMN parent_occupation VARCHAR(255),
ADD COLUMN parent_contact VARCHAR(20);
```

### 3. Access the Dashboard
Navigate to: `http://localhost/scholarmatch/student_dashboard.php`

## Usage

### For Students
1. **Login** to your student account
2. **Complete Profile** (if not done) for accurate eligibility
3. **Browse Scholarships**: View all available scholarships
4. **Use Filters**: Narrow down by category, education level, state
5. **Search**: Find specific scholarships by title or description
6. **Check Eligibility**: See your eligibility status with details
7. **Apply**: Click "Apply Now" for eligible scholarships

### Eligibility Details
When viewing a scholarship, you'll see:
- **Eligibility Status**: Green (eligible), Yellow (partial), Red (not eligible)
- **Issues List**: Specific reasons why you may not be fully eligible
- **Suggested Actions**: What you need to do to become eligible

## API Endpoints

### Get Scholarship Eligibility
**URL**: `get_scholarship_eligibility.php?id=[scholarship_id]`
**Method**: GET
**Response**:
```json
{
    "scholarship_id": 1,
    "title": "Merit Scholarship",
    "eligibility": {
        "status": "eligible",
        "percentage": 95,
        "issues": [],
        "details": ["✓ Category eligible", "✓ Marks eligible", "✓ Income eligible"]
    }
}
```

## Database Queries Reference

### Get All Eligible Scholarships
```sql
SELECT s.* FROM scholarships s
INNER JOIN student_profile sp ON 1=1
WHERE s.status = 'active' 
AND sp.marks >= s.min_marks
AND sp.family_income <= s.max_family_income
AND (s.category = 'General' OR s.category = sp.category)
AND s.deadline >= CURDATE()
ORDER BY s.deadline ASC;
```

### Check Scholarships Expiring Soon
```sql
SELECT * FROM scholarships
WHERE status='active' 
AND deadline <= DATE_ADD(NOW(), INTERVAL 7 DAY)
AND deadline >= NOW()
ORDER BY deadline ASC;
```

### Search and Filter
```sql
SELECT * FROM scholarships s
LEFT JOIN states st ON s.state_id = st.state_id
WHERE s.status = 'active'
AND (s.title LIKE '%[search]%' OR s.description LIKE '%[search]%')
AND (s.category = '[category]' OR '[category]' = '')
AND (s.education_level LIKE '%[education]%' OR '[education]' = '')
ORDER BY s.deadline ASC
LIMIT [offset], 10;
```

## Styling & Customization

### Color Scheme
- Primary: `#667eea` (Purple)
- Secondary: `#764ba2` (Dark Purple)
- Success: `#28a745` (Green)
- Warning: `#ffc107` (Yellow)
- Danger: `#dc3545` (Red)

### Responsive Breakpoints
- Desktop: Full card grid layout
- Tablet: Responsive grid
- Mobile: Single column layout

## Performance Optimization

### Database Indexes
```sql
CREATE INDEX idx_scholarship_status ON scholarships(status);
CREATE INDEX idx_scholarship_deadline ON scholarships(deadline);
CREATE INDEX idx_student_profile_user ON student_profile(user_id);
```

### Pagination
- 10 scholarships per page
- Efficient LIMIT/OFFSET queries
- Navigation buttons for easy browsing

### Caching Opportunities
- Cache state list (rarely changes)
- Cache category options
- Cache education levels

## Security Features

- **Session Validation**: Checks user is logged in as student
- **SQL Injection Prevention**: Uses prepared statements and `mysqli_real_escape_string()`
- **XSS Prevention**: Uses `htmlspecialchars()` for output encoding
- **Authorization**: Only students can view their eligibility

## Future Enhancement Ideas

1. **PDF Export**: Download scholarship details as PDF
2. **Email Notifications**: Alert students about deadline extensions
3. **Application Tracking**: Track submitted applications
4. **Favorites**: Save favorite scholarships for later
5. **Scholarship Recommendations**: AI-based recommendations based on profile
6. **Document Management**: Upload and manage application documents
7. **Mobile App**: Native iOS/Android application
8. **Advanced Analytics**: Dashboard analytics for students
9. **Comparison Tool**: Compare multiple scholarships side-by-side
10. **Direct Application**: Apply without leaving the dashboard

## Troubleshooting

### No scholarships showing
- Ensure scholarships table is populated
- Check scholarship status = 'active'
- Verify deadline is not in the past

### Eligibility not showing correctly
- Complete your student profile
- Check min_marks and max_family_income values in scholarships table
- Verify category and state data

### Filters not working
- Clear browser cache
- Check database connection
- Verify filter parameters are being passed

## Browser Compatibility
- Chrome/Edge: ✅ Full support
- Firefox: ✅ Full support
- Safari: ✅ Full support
- Mobile browsers: ✅ Full support

## Support & Documentation
For issues or questions, contact the development team or check the database documentation in `SCHOLARSHIP_DATABASE_SQL.sql`.

---
**Last Updated**: January 28, 2026
**Version**: 1.0
