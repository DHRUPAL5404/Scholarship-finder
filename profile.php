<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
$success_message = '';
$error_message = '';

if(isset($_POST['save_profile'])){
    $stmt = $conn->prepare("SELECT profile_id FROM student_profile WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    $full_name        = $_POST['full_name'];
    $email            = trim($_POST['email'] ?? '');
    $education_level  = $_POST['education_level'];
    $marks            = intval($_POST['marks']);
    $family_income    = intval($_POST['family_income']);
    $category         = $_POST['category'];
    $gender           = $_POST['gender'];
    $state_id         = intval($_POST['state_id']);
    $district_id      = intval($_POST['district_id']);
    $institution_type = $_POST['institution_type'];
    $age              = intval($_POST['age']);
    $disability_type  = $_POST['disability_type'] ?? 'None';
    $disability_percent = intval($_POST['disability_percent'] ?? 0);
    $minority_status  = $_POST['minority_status'];
    $parent_name      = $_POST['parent_name'] ?? '';
    $parent_occupation= $_POST['parent_occupation'] ?? '';
    $parent_contact   = $_POST['parent_contact'] ?? '';
    $course           = $_POST['course'] ?? '';
    $current_year     = $_POST['current_year'] ?? '';

    // Keep course column populated from selected course dropdowns too
    $course_final = trim($course);
    if($course_final === '') {
        if(!empty($_POST['undergrad_course'])) {
            $course_final = trim($_POST['undergrad_course']);
        } elseif(!empty($_POST['postgrad_course'])) {
            $course_final = trim($_POST['postgrad_course']);
        } elseif(!empty($_POST['phd_course'])) {
            $course_final = trim($_POST['phd_course']);
        } elseif(!empty($_POST['diploma_course'])) {
            $course_final = trim($_POST['diploma_course']);
        } elseif(!empty($_POST['tenth_stream'])) {
            $course_final = trim($_POST['tenth_stream']);
        }
    }

    // Build education_final string
    $education_final = $education_level;
    if($education_level === 'Below 10th' && !empty($_POST['below_10th_level'])) {
        $education_final = $_POST['below_10th_level'];
    } elseif($education_level === '10th Pass(SSC)' && !empty($_POST['tenth_stream'])) {
        $tenth_stream = $_POST['tenth_stream'];
        if($tenth_stream === 'Diploma' && !empty($_POST['diploma_course'])) {
            $education_final = '10th Pass - Diploma - ' . $_POST['diploma_course'];
        } else {
            $education_final = '10th Pass - ' . $tenth_stream;
        }
    } elseif($education_level === 'Undergraduate') {
        $ustream = $_POST['undergrad_stream'] ?? '';
        $sgroup  = $_POST['science_group'] ?? '';
        $ucourse = $_POST['undergrad_course'] ?? '';
        if($ustream === 'Science' && $sgroup && $ucourse) {
            $education_final = "Undergraduate - Science - $sgroup - $ucourse";
        } elseif($ustream && $ucourse) {
            $education_final = "Undergraduate - $ustream - $ucourse";
        } elseif($ustream) {
            $education_final = "Undergraduate - $ustream";
        }
    } elseif($education_level === 'Postgraduate' && !empty($_POST['postgrad_course'])) {
        $education_final = $_POST['postgrad_course'];
    } elseif($education_level === 'PhD' && !empty($_POST['phd_course'])) {
        $education_final = $_POST['phd_course'];
    }

    if($exists) {
        $stmt = $conn->prepare("UPDATE student_profile SET
            full_name=?, email=?, education_level=?, marks=?, family_income=?,
            category=?, gender=?, state_id=?, district_id=?, institution_type=?,
            age=?, disability_type=?, disability_percent=?, minority_status=?,
            parent_name=?, parent_occupation=?, parent_contact=?, course=?, current_year=?
            WHERE user_id=?");
        $stmt->bind_param("sssiissiisisissssssi",
            $full_name, $email, $education_final, $marks, $family_income,
            $category, $gender, $state_id, $district_id, $institution_type,
            $age, $disability_type, $disability_percent, $minority_status,
            $parent_name, $parent_occupation, $parent_contact, $course_final, $current_year,
            $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO student_profile
            (user_id, full_name, email, education_level, marks, family_income,
             category, gender, state_id, district_id, institution_type, age,
             disability_type, disability_percent, minority_status,
             parent_name, parent_occupation, parent_contact, course, current_year)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssiissiisisisssssss",
            $user_id, $full_name, $email, $education_final, $marks, $family_income,
            $category, $gender, $state_id, $district_id, $institution_type, $age,
            $disability_type, $disability_percent, $minority_status,
            $parent_name, $parent_occupation, $parent_contact, $course_final, $current_year);
    }

    if($stmt->execute()) {
        error_log("Profile saved successfully for user_id: $user_id"); // Log success
        header("Location: student_dashboard.php");
        exit();
    } else {
        $error_message = "Database Error: " . $stmt->error . " (SQLSTATE: " . $stmt->sqlstate . ")";
        error_log("Profile save FAILED for user_id: $user_id - " . $stmt->error); // Log failure
    }
    $stmt->close();
}


// Fetch existing profile
$stmt = $conn->prepare("SELECT * FROM student_profile WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_edit = !empty($profile); // true = edit mode, false = create mode

// For state/district auto-select pass to JS
$saved_state_id    = $profile['state_id'] ?? 0;
$saved_district_id = $profile['district_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Create' ?> Profile - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/student.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/validation.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="container profile-page">

    <!-- Header shows Edit vs Create mode -->
    <h2><?= $is_edit ? '✏️ Edit Your Profile' : '📝 Complete Your Profile' ?></h2>

    <?php if($is_edit): ?>
    <div style="background:#e8f5e9;border-left:4px solid #4caf50;padding:10px 16px;margin-bottom:20px;border-radius:4px;">
        ✅ Your profile is already saved. You can update any field below and save again.
    </div>
    <?php else: ?>
    <div style="background:#fff3e0;border-left:4px solid #ff9800;padding:10px 16px;margin-bottom:20px;border-radius:4px;">
        ⚠️ Please complete your profile to see accurate scholarship eligibility.
    </div>
    <?php endif; ?>

    <?php if($error_message): ?>
    <div style="color:red;background:#f8d7da;padding:10px;margin-bottom:20px;border-radius:5px;border-left:4px solid #dc3545;font-weight:500;">
        <strong>❌ Error:</strong> <?= htmlspecialchars($error_message) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="profile.php" class="profile-form">

        Full Name:
        <input type="text" name="full_name" placeholder="Full Name"
            value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" required><br><br>

        Email:
        <input type="email" name="email" placeholder="Email"
            value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required><br><br>

        Education Level:
        <select id="education_level" name="education_level" onchange="try{toggleBelow10thDropdown();}catch(e){console.error(e);}" required>
            <option value="">Select</option>
            <?php
            $edu = $profile['education_level'] ?? '';
            $edu_options = ['Below 10th','10th Pass(SSC)','Undergraduate','Postgraduate','PhD','Other'];
            foreach($edu_options as $opt):
                // Match broadly so "Undergraduate - Science - ..." still selects "Undergraduate"
                $sel = (strpos($edu, str_replace('(SSC)','',$opt)) !== false) ? 'selected' : '';
            ?>
            <option value="<?= $opt ?>" <?= $sel ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <!-- Below 10th -->
        <div id="below_10th_dropdown" style="display:none;">
            <label>Select Standard:</label>
            <select name="below_10th_level">
                <option value="">Select Standard</option>
                <option value="Primary School (Std 1-8)" <?= (strpos($edu,'Primary School')!==false)?'selected':'' ?>>Primary School (Std 1–8)</option>
                <option value="Secondary School - Appearing (Std 9-10)" <?= (strpos($edu,'Secondary School')!==false)?'selected':'' ?>>Secondary School – Appearing (Std 9–10)</option>
            </select><br><br>
        </div>

        <!-- 10th Pass Stream -->
        <div id="tenth_stream_dropdown" style="display:none;">
            <label>Select Stream:</label>
            <select id="tenth_stream" name="tenth_stream" onchange="toggleDiplomaDropdown()">
                <option value="">Select Stream</option>
                <?php foreach(['Science','Commerce','Arts','Diploma'] as $s): ?>
                <option value="<?= $s ?>" <?= (strpos($edu,"10th Pass - $s")!==false)?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select><br><br>
            <div id="diploma_course_dropdown" style="display:none;">
                <label>Select Diploma Course:</label>
                <select id="diploma_course" name="diploma_course">
                    <option value="">Select Diploma Course</option>
                    <?php
                    $diplomas = ['Diploma in Engineering (Polytechnic)','Diploma in Computer Engineering / IT',
                                 'Diploma in Mechanical Engineering','Diploma in Electrical Engineering',
                                 'Diploma in Civil Engineering','Diploma in Electronics / EC','Other'];
                    foreach($diplomas as $d):
                    ?>
                    <option value="<?= $d ?>" <?= (strpos($edu,$d)!==false)?'selected':'' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select><br><br>
            </div>
        </div>

        <!-- Undergraduate Stream -->
        <div id="undergrad_stream_dropdown" style="display:none;">
            <label>Select Stream:</label>
            <select id="undergrad_stream" name="undergrad_stream" onchange="toggleScienceGroupDropdown()">
                <option value="">Select Stream</option>
                <?php foreach(['Science','Commerce','Arts'] as $s): ?>
                <option value="<?= $s ?>" <?= (strpos($edu,"Undergraduate - $s")!==false)?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select><br><br>
            <div id="science_group_dropdown" style="display:none;">
                <label>Select Science Group:</label>
                <select id="science_group" name="science_group" onchange="toggleTwelfthCourseDropdown()">
                    <option value="">Select Group</option>
                    <option value="Group A" <?= (strpos($edu,'Group A')!==false)?'selected':'' ?>>Group A (Physics, Chemistry, Mathematics)</option>
                    <option value="Group B" <?= (strpos($edu,'Group B')!==false)?'selected':'' ?>>Group B (Physics, Chemistry, Biology)</option>
                </select><br><br>
            </div>
            <div id="undergrad_course_dropdown" style="display:none;">
                <label>Select Course:</label>
                <select id="undergrad_course" name="undergrad_course">
                    <option value="">Select Course</option>
                </select><br><br>
            </div>
        </div>

        <!-- Postgraduate Course -->
        <div id="postgrad_course_dropdown" style="display:none;">
            <label>Select Postgraduate Course:</label>
            <select id="postgrad_course" name="postgrad_course">
                <option value="">Select Course</option>
                <?php
                $pg_courses = ['M.E. / M.Tech (Engineering)','M.Sc (Science)','M.Com (Commerce)',
                    'M.A (Arts / Humanities)','MCA (Computer Applications)',
                    'MBA (Master of Business Administration)',"M.Voc (Vocational Master's)",
                    'M.Ed (Education)','LLM (Law)','M.Pharm (Pharmacy)','M.Sc Nursing',
                    'MS (Medical / Clinical)','MPH (Public Health)','MHA (Hospital Administration)',
                    'MSW (Social Work)','M.Des (Design)','M.Phil',
                    "Integrated Master's Program",'M.Tech (AI / ML / Data Science / Cyber Security)',
                    'M.Sc (Data Science / AI / Analytics)','PG Diploma',
                    "Distance / Online Master's Degree",'Other'];
                foreach($pg_courses as $c):
                ?>
                <option value="<?= $c ?>" <?= ($edu===$c)?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select><br><br>
        </div>

        <!-- PhD Course -->
        <div id="phd_course_dropdown" style="display:none;">
            <label>Select PhD Course:</label>
            <select id="phd_course" name="phd_course">
                <option value="">Select Course</option>
                <?php
                $phd_courses = ['PhD in Computer Science / IT','PhD in Mechanical Engineering',
                    'PhD in Civil Engineering','PhD in Electrical Engineering',
                    'PhD in Electronics & Communication','PhD in Chemical Engineering',
                    'PhD in Biotechnology Engineering','PhD in Environmental Engineering',
                    'PhD in Aerospace / Aeronautical Engineering','PhD in Data Science / AI / ML',
                    'PhD in Mathematics','PhD in Physics','PhD in Chemistry',
                    'PhD in Biology / Life Sciences','PhD in Environmental Science',
                    'PhD in Economics','PhD in Management / Business Administration',
                    'PhD in Commerce','Other'];
                foreach($phd_courses as $c):
                ?>
                <option value="<?= $c ?>" <?= ($edu===$c)?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select><br><br>
        </div>

        <!-- Course / Year for Other -->
        <div id="course_year_fields" style="display:none;">
            <input type="text" id="course" name="course" placeholder="Course"
                value="<?= htmlspecialchars($profile['course'] ?? '') ?>"><br><br>
            <input type="text" id="current_year" name="current_year" placeholder="Current Year"
                value="<?= htmlspecialchars($profile['current_year'] ?? '') ?>"><br><br>
        </div>

        Marks (%):
        <input type="number" name="marks" placeholder="Marks (%)" min="0" max="100"
            value="<?= htmlspecialchars($profile['marks'] ?? '') ?>" required><br><br>

        Family Income (₹ per year):
        <input type="number" name="family_income" placeholder="Annual Family Income"
            value="<?= htmlspecialchars($profile['family_income'] ?? '') ?>" required><br><br>

        Category:
        <select name="category" required>
            <option value="">Select Category</option>
            <?php
            $cats = ['General (GEN / UR)','Other Backward Class (OBC)','Scheduled Caste (SC)','Scheduled Tribe (ST)'];
            foreach($cats as $c):
            ?>
            <option value="<?= $c ?>" <?= (($profile['category']??'')===$c)?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select><br><br>

        Gender:
        <select name="gender" required>
            <option value="">Select</option>
            <?php foreach(['Male','Female','Other'] as $g): ?>
            <option value="<?= $g ?>" <?= (($profile['gender']??'')===$g)?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select><br><br>

        State:
        <select id="state" name="state_id" required>
            <option value="">Select State</option>
        </select><br><br>

        District:
        <select id="district" name="district_id" required>
            <option value="">Select District</option>
        </select><br><br>

        Institution Type:
        <select name="institution_type" required>
            <option value="">Select Institution Type</option>
            <?php foreach(['Government','Private','Government-Aided','Autonomous','University'] as $it): ?>
            <option value="<?= $it ?>" <?= (($profile['institution_type']??'')===$it)?'selected':'' ?>><?= $it ?></option>
            <?php endforeach; ?>
        </select><br><br>

        Age:
        <input type="number" name="age" placeholder="Age" min="5" max="100"
            value="<?= htmlspecialchars($profile['age'] ?? '') ?>" required><br><br>

        Disability Type:
        <select name="disability_type">
            <option value="">Select Disability Type (if any)</option>
            <?php
            $dis_types = ['None','Physical Disability','Visual Impairment','Hearing Impairment',
                'Speech & Language Disability','Intellectual Disability','Mental Illness',
                'Multiple Disabilities','Specific Learning Disability','Other'];
            foreach($dis_types as $d):
            ?>
            <option value="<?= $d ?>" <?= (($profile['disability_type']??'')===$d)?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
        </select><br><br>

        Disability Percent (if any):
        <input type="number" name="disability_percent" placeholder="0-100" min="0" max="100"
            value="<?= htmlspecialchars($profile['disability_percent'] ?? 0) ?>"><br><br>

        Minority Status:
        <select name="minority_status" required>
            <option value="">Select</option>
            <option value="Yes" <?= (($profile['minority_status']??'')==='Yes')?'selected':'' ?>>Yes</option>
            <option value="No"  <?= (($profile['minority_status']??'')==='No')?'selected':'' ?>>No</option>
        </select><br><br>

        Parent / Guardian Name:
        <input type="text" name="parent_name" placeholder="Parent / Guardian Name"
            value="<?= htmlspecialchars($profile['parent_name'] ?? '') ?>"><br><br>

        Parent / Guardian Occupation:
        <input type="text" name="parent_occupation" placeholder="Parent / Guardian Occupation"
            value="<?= htmlspecialchars($profile['parent_occupation'] ?? '') ?>"><br><br>

        Parent / Guardian Contact:
        <input type="tel" name="parent_contact" placeholder="Mobile / Phone"
            value="<?= htmlspecialchars($profile['parent_contact'] ?? '') ?>"><br><br>

        <button type="submit" name="save_profile" value="1">
            <?= $is_edit ? '💾 Update Profile' : '✅ Save Profile' ?>
        </button>

    </form>
</div>

<?php include "includes/footer.php"; ?>

<script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
<script>
// Saved values from PHP for auto-selecting state/district
const SAVED_STATE_ID    = <?= intval($saved_state_id) ?>;
const SAVED_DISTRICT_ID = <?= intval($saved_district_id) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // ── State dropdown: load all states, then auto-select saved one ──
    fetch('get_states.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('state').innerHTML += html;
            if(SAVED_STATE_ID > 0) {
                document.getElementById('state').value = SAVED_STATE_ID;
                // After selecting state, load its districts then auto-select saved district
                loadDistricts(SAVED_STATE_ID, SAVED_DISTRICT_ID);
            }
        })
        .catch(err => console.warn('Error loading states:', err));

    // ── State change by user ──
    document.getElementById('state').addEventListener('change', function() {
        loadDistricts(this.value, 0);
    });

    // ── Trigger education dropdowns on load ──
    toggleBelow10thDropdown();
});

function loadDistricts(stateId, autoSelectDistrictId) {
    if(!stateId) {
        document.getElementById('district').innerHTML = '<option value="">Select District</option>';
        return;
    }
    fetch('get_districts.php?state_id=' + stateId)
        .then(res => res.text())
        .then(html => {
            document.getElementById('district').innerHTML =
                '<option value="">Select District</option>' + html;
            // Auto-select the saved district if provided
            if(autoSelectDistrictId > 0) {
                document.getElementById('district').value = autoSelectDistrictId;
            }
        })
        .catch(err => {
            console.warn('Error loading districts:', err);
            document.getElementById('district').innerHTML = '<option value="">Error loading districts</option>';
        });
}

function toggleBelow10thDropdown() {
    try {
        const edu = document.getElementById('education_level').value;
        const divs = {
            'below_10th':    ['Below 10th'],
            'tenth_stream':  ['10th Pass(SSC)'],
            'undergrad_stream': ['Undergraduate'],
            'postgrad_course':  ['Postgraduate'],
            'phd_course':       ['PhD'],
            'course_year':      ['Other']
        };

        hide('below_10th_dropdown');
        hide('tenth_stream_dropdown');
        hide('undergrad_stream_dropdown');
        hide('postgrad_course_dropdown');
        hide('phd_course_dropdown');
        hide('course_year_fields');

        if(edu === 'Below 10th')      show('below_10th_dropdown');
        else if(edu === '10th Pass(SSC)') { show('tenth_stream_dropdown'); try{toggleDiplomaDropdown();}catch(e){} }
        else if(edu === 'Undergraduate')  { show('undergrad_stream_dropdown'); try{toggleScienceGroupDropdown();}catch(e){} }
        else if(edu === 'Postgraduate')   show('postgrad_course_dropdown');
        else if(edu === 'PhD')            show('phd_course_dropdown');
        else if(edu === 'Other')          show('course_year_fields');
    } catch(e) {
        console.warn('Education dropdown error:', e);
    }
}

function toggleDiplomaDropdown() {
    try {
        const val = document.getElementById('tenth_stream').value;
        val === 'Diploma' ? show('diploma_course_dropdown') : hide('diploma_course_dropdown');
    } catch(e) {
        console.warn('Diploma dropdown error:', e);
    }
}

function toggleScienceGroupDropdown() {
    try {
        const stream = document.getElementById('undergrad_stream').value;
        hide('science_group_dropdown');
        hide('undergrad_course_dropdown');

        if(stream === 'Science') {
            show('science_group_dropdown');
            toggleTwelfthCourseDropdown();
        } else if(stream === 'Commerce' || stream === 'Arts') {
            show('undergrad_course_dropdown');
            updateCoursesForStream(stream);
        }
    } catch(e) {
        console.warn('Science group dropdown error:', e);
    }
}

function toggleTwelfthCourseDropdown() {
    try {
        const stream = document.getElementById('undergrad_stream').value;
        const group  = document.getElementById('science_group').value;
        if(stream === 'Science' && group) {
            show('undergrad_course_dropdown');
            updateCoursesForScienceGroup(group);
        }
    } catch(e) {
        console.warn('Twelfth course dropdown error:', e);
    }
}

function updateCoursesForScienceGroup(group) {
    try {
        const map = {
            'Group A': ['B.E. / B.Tech (Engineering)','BCA (Computer Applications)','B.Sc (Science)'],
            'Group B': ['MBBS / BDS / BAMS / BHMS (Medical)','B.Sc Nursing','B.Pharm (Pharmacy)','B.Sc (Science)']
        };
        fillCourseSelect('undergrad_course', map[group] || []);
    } catch(e) {
        console.warn('Course update error:', e);
    }
}

function updateCoursesForStream(stream) {
    try {
        const map = {
            'Commerce': ['B.Com (Commerce)','BBA (Business Administration)','Hotel Management / Tourism','B.Ed (Integrated)'],
            'Arts':     ['B.A (Arts / Humanities)','Fashion Designing / Fine Arts','LLB (Law - 5 Year)','Hotel Management / Tourism','B.Ed (Integrated)']
        };
        fillCourseSelect('undergrad_course', map[stream] || []);
    } catch(e) {
        console.warn('Stream update error:', e);
    }
}

function fillCourseSelect(id, courses) {
    try {
        // Preserve previously saved value if it exists
        const saved = document.getElementById(id).dataset.saved || '';
        let html = '<option value="">Select Course</option>';
        courses.forEach(c => {
            html += `<option value="${c}" ${c === saved ? 'selected' : ''}>${c}</option>`;
        });
        document.getElementById(id).innerHTML = html;
    } catch(e) {
        console.warn('Course select error:', e);
    }
}

function show(id) { const el = document.getElementById(id); if(el) el.style.display = 'block'; }
function hide(id) { const el = document.getElementById(id); if(el) el.style.display = 'none'; }

// Ensure form can always be submitted
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.profile-form');
    if(form) {
        const btn = form.querySelector('button[type="submit"]');
        if(btn) {
            btn.addEventListener('click', function(e) {
                // Allow form submission (disable e.preventDefault)
                if(form.checkValidity() === false) {
                    e.preventDefault();
                    e.stopPropagation();
                } else {
                    btn.disabled = false; // Ensure button stays enabled
                }
            });
        }
    }
});
</script>

</body>
</html>

