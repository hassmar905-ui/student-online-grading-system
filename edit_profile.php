<?php
require_once 'config.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = '';

// Get current data
if ($role == 'student') {
    $query = "SELECT s.*, u.email, u.username 
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              WHERE s.user_id = $user_id";
} elseif ($role == 'teacher') {
    $query = "SELECT t.*, u.email, u.username 
              FROM teachers t 
              JOIN users u ON t.user_id = u.id 
              WHERE t.user_id = $user_id";
} else {
    $query = "SELECT email, username FROM users WHERE id = $user_id";
}

$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    
    // Check if username/email changed
    if ($username != $user['username']) {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username already taken!';
        }
    }
    
    if ($email != $user['email']) {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Email already in use!';
        }
    }
    
    if (!$error) {
        mysqli_begin_transaction($conn);
        
        try {
            // Update users table
            mysqli_query($conn, "UPDATE users SET username = '$username', email = '$email' WHERE id = $user_id");
            
            // Update role table
            if ($role == 'student') {
                $dob = sanitize($_POST['dob']);
                $address = sanitize($_POST['address']);
                $phone = sanitize($_POST['phone']);
                $course = sanitize($_POST['course']);
                
                mysqli_query($conn, "UPDATE students SET 
                    full_name = '$full_name',
                    dob = '$dob',
                    address = '$address',
                    phone = '$phone',
                    course = '$course'
                    WHERE user_id = $user_id");
            } elseif ($role == 'teacher') {
                $address = sanitize($_POST['address']);
                $phone = sanitize($_POST['phone']);
                $department = sanitize($_POST['department']);
                $salary = sanitize($_POST['salary']);
                
                mysqli_query($conn, "UPDATE teachers SET 
                    full_name = '$full_name',
                    address = '$address',
                    phone = '$phone',
                    department = '$department',
                    salary = '$salary'
                    WHERE user_id = $user_id");
            }
            
            mysqli_commit($conn);
            
            // Update session
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            setMessage('Profile updated successfully!');
            redirect('view_profile.php');
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<?php
$page_title = 'Edit Profile';
include 'header.php';
?>

<div class="form-container">
    <div class="form-header">
        <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-grid">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
            </div>
        </div>
        
        <?php if ($role == 'student'): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?php echo $user['dob']; ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo $user['phone']; ?>">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="course">
                        <option value="">Select Department</option>
                        <option value="Computer Science" <?php echo ($user['course'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Information Technology" <?php echo ($user['course'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Software Engineering" <?php echo ($user['course'] == 'Software Engineering') ? 'selected' : ''; ?>>Software Engineering</option>
                        <option value="Mathematics" <?php echo ($user['course'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                        <option value="Physics" <?php echo ($user['course'] == 'Physics') ? 'selected' : ''; ?>>Physics</option>
                        <option value="Chemistry" <?php echo ($user['course'] == 'Chemistry') ? 'selected' : ''; ?>>Chemistry</option>
                        <option value="Biology" <?php echo ($user['course'] == 'Biology') ? 'selected' : ''; ?>>Biology</option>
                        <option value="Business Administration" <?php echo ($user['course'] == 'Business Administration') ? 'selected' : ''; ?>>Business Administration</option>
                        <option value="Accounting" <?php echo ($user['course'] == 'Accounting') ? 'selected' : ''; ?>>Accounting</option>
                        <option value="Economics" <?php echo ($user['course'] == 'Economics') ? 'selected' : ''; ?>>Economics</option>
                        <option value="Engineering" <?php echo ($user['course'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                        <option value="Medicine" <?php echo ($user['course'] == 'Medicine') ? 'selected' : ''; ?>>Medicine</option>
                        <option value="Nursing" <?php echo ($user['course'] == 'Nursing') ? 'selected' : ''; ?>>Nursing</option>
                        <option value="Law" <?php echo ($user['course'] == 'Law') ? 'selected' : ''; ?>>Law</option>
                        <option value="Education" <?php echo ($user['course'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3"><?php echo $user['address']; ?></textarea>
            </div>
            
        <?php elseif ($role == 'teacher'): ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo $user['department']; ?>">
                </div>
                <div class="form-group">
                    <label>Salary</label>
                    <input type="number" name="salary" step="0.01" value="<?php echo $user['salary']; ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo $user['phone']; ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3"><?php echo $user['address']; ?></textarea>
            </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <a href="view_profile.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>