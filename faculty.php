<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

require_once "assets/config/dbconfig.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>U-Plug | Faculty Users</title>
  <link rel="stylesheet" href="assets/css/admin-dashboard.css">
  <link rel="stylesheet" href="assets/css/faculty.css">
</head>

<body>
  <div class="layout" id="layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo">Administrator</div>
      <ul class="nav">
        <li><a href="admin.php" class="nav-link">Dashboard</a></li>
        <li class="divider">User Settings</li>
        <li><a href="faculty.php" class="nav-link active">Faculty Users</a></li>
        <li><a href="student.php" class="nav-link">Student Users</a></li>
        <li><a href="posts.php" class="nav-link">Posts</a></li>
        <li class="divider">Settings</li>
        <li><a href="#" class="nav-link">About</a></li>
        <li><a href="assets/server/logout-process.php" class="nav-link">Logout</a></li>
      </ul>
    </aside>

    <!-- Burger -->
    <button id="burger" class="burger">&#9776;</button>

    <!-- Main Content -->
    <main class="main-content" id="main">
      <div class="scrollable-panel">
        <h1 class="page-title">Faculty Users</h1>

        <!-- 🔍 Filter + Search Bar -->
        <div class="filter-bar">
          <div class="filter-group">
            <label for="department-filter">Filter by Department:</label>
            <select id="department-filter">
              <option value="all">All Departments</option>
              <?php
              $departments = $conn->query("SELECT DISTINCT department FROM faculty_users ORDER BY department ASC");
              while ($dep = $departments->fetch_assoc()) {
                echo "<option value='{$dep['department']}'>{$dep['department']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="search-group">
            <label for="search-input">Search:</label>
            <input type="text" id="search-input" placeholder="Search faculty name...">
          </div>
        </div>

        <!-- Department Panels -->
        <section id="faculty-panels" class="faculty-panels">
          <?php
          $deptQuery = $conn->query("SELECT DISTINCT department FROM faculty_users ORDER BY department ASC");
          if ($deptQuery->num_rows > 0) {
            while ($deptRow = $deptQuery->fetch_assoc()) {
              $dept = htmlspecialchars($deptRow['department']);
              echo "<section class='panel' id='{$dept}-panel'>";
              echo "<div class='panel-header'>
                      <h2>{$dept}</h2>
                      <button class='expand-btn' data-target='{$dept}'>＋</button>
                    </div>";

              echo "<ul class='list collapsible' id='{$dept}-list'>";
              $facultyQuery = $conn->query("SELECT * FROM faculty_users WHERE department='$dept' ORDER BY full_name ASC");
              while ($fac = $facultyQuery->fetch_assoc()) {
                $email = htmlspecialchars($fac['email']);
                $name = htmlspecialchars($fac['full_name']);
                $deptName = htmlspecialchars($fac['department']);
                $status = "Active";
                echo "
                <li>
                  <div>{$name}</div>
                  <div class='actions'>
                    <button class='edit-btn'
                            data-name='{$name}'
                            data-dept='{$deptName}'
                            data-email='{$email}'
                            data-status='{$status}'>
                      View
                    </button>
                    <button class='delete-btn' data-id='{$fac['seq_id']}'>Delete</button>
                  </div>
                </li>";
              }
              echo "</ul></section>";
            }
          } else {
            echo "<p class='no-results'>No faculty data found.</p>";
          }
          ?>
        </section>

        <div class="footer">U-Plug ©2025. All rights reserved.</div>
      </div>
    </main>
  </div>

  <!-- === Faculty View Modal === -->
  <div id="viewModal" class="modal">
    <div class="modal-content large">
      <span class="close-btn" id="closeViewModal">&times;</span>
      <h2>Faculty Information</h2>
      <div class="modal-body">
        <p><strong>Name:</strong> <span id="facultyName"></span></p>
        <p><strong>Department:</strong> <span id="facultyDept"></span></p>
        <p><strong>Email:</strong> <span id="facultyEmail"></span></p>
        <p><strong>Status:</strong> <span id="facultyStatus"></span></p>
      </div>
    </div>
  </div>

  <!-- === Delete Confirmation Modal === -->
  <div id="deleteModal" class="modal">
    <div class="modal-content small">
      <span class="close-btn" id="closeDeleteModal">&times;</span>
      <h3>Confirm Deletion</h3>
      <p>Are you sure you want to delete this faculty member?</p>
      <div class="modal-actions">
        <button id="confirm-delete">Delete</button>
        <button id="cancel-delete">Cancel</button>
      </div>
    </div>
  </div>

  <script>
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");
  const burger = document.getElementById("burger");

  // Sidebar toggle
  burger.addEventListener("click", () => sidebar.classList.toggle("hidden"));
  document.addEventListener("click", (e) => {
    if (!sidebar.contains(e.target) && !burger.contains(e.target)) {
      sidebar.classList.add("hidden");
    }
  });

  // Expand / collapse panels
  document.querySelectorAll(".expand-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.target;
      const panel = document.getElementById(`${targetId}-panel`);
      const expanded = panel.classList.toggle("expanded");
      btn.textContent = expanded ? "−" : "＋";
    });
  });

  // Search & Filter
  const searchInput = document.getElementById("search-input");
  const departmentFilter = document.getElementById("department-filter");

  function filterFaculty() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const selectedDept = departmentFilter.value;

    document.querySelectorAll(".panel").forEach(panel => {
      const panelDept = panel.id.replace("-panel", "");
      const facultyItems = panel.querySelectorAll("li");
      let panelHasVisibleItems = false;

      const deptMatches = (selectedDept === "all" || panelDept === selectedDept);

      if (deptMatches) {
        facultyItems.forEach(item => {
          const facultyName = item.querySelector("div").textContent.toLowerCase();
          const matchesSearch = facultyName.includes(searchTerm);
          item.style.display = matchesSearch ? "flex" : "none";
          if (matchesSearch) panelHasVisibleItems = true;
        });
      } else {
        facultyItems.forEach(item => item.style.display = "none");
      }

      panel.style.display = panelHasVisibleItems ? "block" : "none";
    });
  }

  searchInput.addEventListener("input", filterFaculty);
  departmentFilter.addEventListener("change", filterFaculty);

  // View Modal
  const viewModal = document.getElementById("viewModal");
  document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      document.getElementById("facultyName").textContent = btn.dataset.name;
      document.getElementById("facultyDept").textContent = btn.dataset.dept;
      document.getElementById("facultyEmail").textContent = btn.dataset.email;
      document.getElementById("facultyStatus").textContent = btn.dataset.status;
      viewModal.style.display = "flex";
    });
  });

  // === DELETE FEATURE ===
  const deleteModal = document.getElementById("deleteModal");
  let facultyToDelete = null;

  // Open delete modal
  document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      facultyToDelete = btn.dataset.id;
      deleteModal.style.display = "flex";
    });
  });

  // Confirm delete
  document.getElementById("confirm-delete").addEventListener("click", () => {
    if (!facultyToDelete) return;

    fetch("delete_faculty.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(facultyToDelete)
    })
    .then(res => res.text())
    .then(response => {
      if (response.trim() === "success") {
        // Remove deleted faculty from the list
        const li = document.querySelector(`button.delete-btn[data-id='${facultyToDelete}']`)?.closest("li");
        if (li) li.remove();

        // Optional: show confirmation
        alert("Faculty member deleted successfully.");
      } else {
        alert("Failed to delete faculty. (" + response + ")");
      }
      deleteModal.style.display = "none";
    })
    .catch(err => {
      alert("Error deleting faculty.");
      console.error(err);
    });
  });

  // Close modals
  document.getElementById("closeViewModal").onclick = () => viewModal.style.display = "none";
  document.getElementById("closeDeleteModal").onclick = () => deleteModal.style.display = "none";
  document.getElementById("cancel-delete").onclick = () => deleteModal.style.display = "none";
  window.onclick = e => {
    if (e.target === viewModal) viewModal.style.display = "none";
    if (e.target === deleteModal) deleteModal.style.display = "none";
  };
});
</script>
</body>
</html>