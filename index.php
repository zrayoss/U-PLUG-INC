<?php
require __DIR__ . "/assets/config/dbconfig.php";
session_start();

if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome'] === true) {
  $departmentCode = $_SESSION['department_code'] ?? 'default';
}

if (isset($_SESSION['user_id'])) {
  header("Location: home.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>U-Plug Login / Sign Up</title>
  <link rel="stylesheet" href="assets/css/index.css">
  <script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js" defer></script>
  <link rel="icon" href="/assets/images/client/UplugLogo.png" type="image/png">
</head>
<body>

  <div id="welcome-screen">
    <div class="welcome-message">
      <img src="assets/images/client/UplugLogo.png" alt="Uplug Logo"><br>
      <?php if (isset($_SESSION['department_code'])): ?>
        <img src="assets/images/client/department/<?= htmlspecialchars($_SESSION['department_code']) ?>.png" 
             alt="<?= htmlspecialchars($_SESSION['department_code']) ?> Logo">
      <?php endif; ?>
      <img id="loading" src="assets/images/client/Loading.gif" alt="Loading">
    </div>
  </div>

  <div class="auth-container">
    <!-- LOGIN CARD -->
    <div class="glass-card" id="login-card">
      <h2>Login</h2>
      <form id="login-form" class="auth-form" autocomplete="off" method="POST" novalidate>
        <div class="form-group">
          <select name="login_role" id="login_role" required>
            <option value="" disabled selected>Select Account Type</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
            <option value="admin">Administrator</option>
          </select>
        </div>
        <div class="form-group">
          <input type="text" name="login_email" id="login_email" placeholder="Email" required>
        </div>
        <div class="form-group password-wrapper">
          <input type="password" name="login_password" id="login-password" placeholder="Password" required>
          <span class="toggle-password" data-target="login-password">
            <img src="assets/images/client/hidden_password.png" alt="Show Password" class="eye-icon">
          </span>
        </div>
        <button type="submit" class="login-btn">Login</button>
        <p id="login-message" style="color:red; text-align:center; margin-top:10px;"></p>
      </form>

      <div class="switch-link">
        <span>Don't have an account?</span>
        <button id="show-signup" type="button">Sign Up</button>
      </div>
    </div>

    <!-- SIGNUP CARD -->
    <div class="glass-card" id="signup-card" style="display:none;">
      <h2>Sign Up</h2>
      <form action="assets/server/signup-process.php" class="auth-form" id="signup-form" autocomplete="off" method="POST" novalidate>
        <div class="form-group">
          <select name="signup_role" id="signup_role" required>
            <option value="" disabled selected>Select Account Type</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
          </select>
        </div>
        <div class="form-group">
          <select name="department" id="department" required>
            <option value="" disabled selected>Select Department</option>
            <option value="SHS">SHS - Senior Highschool</option>
            <option value="CITE">CITE - College of Information Technology Education</option>
            <option value="CCJE">CCJE - College of Criminal Justice Education</option>
            <option value="CAHS">CAHS - College of Allied Health Sciences</option>
            <option value="CAS">CAS - College of Arts and Sciences</option>
            <option value="CEA">CEA - College of Engineering and Architecture</option>
            <option value="CELA">CELA - College of Education and Liberal Arts</option>
            <option value="CMA">CMA - College of Management and Accountancy</option>
            <option value="COL">COL - College of Law</option>
          </select>
        </div>
        <div class="form-group"><input type="text" name="first_name" placeholder="First Name" id="first_name" required></div>
        <div class="form-group"><input type="text" name="last_name" placeholder="Last Name" id="last_name" required></div>
        <div class="form-group"><input type="email" name="signup_email" placeholder="Email" id="signup_email" required></div>
        <div class="form-group password-wrapper">
          <input type="password" name="signup_password" placeholder="Password" id="signup-password" required>
          <span class="toggle-password" data-target="signup-password">
            <img src="assets/images/client/hidden_password.png" alt="Show Password" class="eye-icon">
          </span>
        </div>
        <div class="form-group password-wrapper">
          <input type="password" name="password_confirmation" placeholder="Confirm Password" id="password_confirmation" required>
          <span class="toggle-password" data-target="password_confirmation">
            <img src="assets/images/client/hidden_password.png" alt="Show Password" class="eye-icon">
          </span>
        </div>
        <button type="submit" class="signup-btn">Sign Up</button>
      </form>

      <div class="switch-link">
        <span>Already have an account?</span>
        <button id="show-login" type="button">Login</button>
      </div>
    </div>
  </div>

  <!-- ================= JS ================= -->
  <script>
    // Switch Login/Signup Cards
    document.getElementById('show-signup').onclick = () => {
      document.getElementById('login-card').style.display = 'none';
      document.getElementById('signup-card').style.display = 'flex';
    };
    document.getElementById('show-login').onclick = () => {
      document.getElementById('login-card').style.display = 'flex';
      document.getElementById('signup-card').style.display = 'none';
    };

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(toggle => {
      toggle.addEventListener('click', () => {
        const targetId = toggle.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = toggle.querySelector('img');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.src = isHidden ? 'assets/images/client/show_password.png' : 'assets/images/client/hidden_password.png';
        icon.alt = isHidden ? 'Hide Password' : 'Show Password';
      });
    });

    // ✅ AJAX LOGIN HANDLER
    document.getElementById("login-form").addEventListener("submit", async function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const messageBox = document.getElementById("login-message");
      messageBox.textContent = "";

      try {
        const response = await fetch("assets/server/login-process.php", {
          method: "POST",
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          window.location.href = result.redirect;
        } else {
          messageBox.textContent = result.message;
        }
      } catch (error) {
        messageBox.textContent = "An error occurred. Please try again.";
      }
    });
  </script>
</body>
</html>
