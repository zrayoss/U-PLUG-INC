<?php
session_start();
require_once "assets/config/dbconfig.php";

// Arrays for each post category
$facultyPosts = [];
$studentPosts = [];

// Fetch all posts ordered by newest first
$sql = "SELECT * FROM posts ORDER BY COALESCE(edited_at, create_date) DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

while ($post = $result->fetch_assoc()) {
    $authorId = $post['author_id'];
    $authorName = "Unknown Author";
    $authorDept = "Unknown Department";

    // Determine which table to look in
    if (strpos($authorId, 'FAC-') === 0) {
        $stmt = $conn->prepare("SELECT full_name, department FROM faculty_users WHERE faculty_id = ?");
    } elseif (strpos($authorId, 'STU-') === 0) {
        $stmt = $conn->prepare("SELECT full_name, department FROM student_users WHERE student_id = ?");
    } else {
        $stmt = null;
    }

    if ($stmt) {
        $stmt->bind_param("s", $authorId);
        $stmt->execute();
        $stmt->bind_result($authorName, $authorDept);
        $stmt->fetch();
        $stmt->close();
    }

    // Add info to post array
    $post['author_name'] = $authorName ?: "Unknown Author";
    $post['author_department'] = $authorDept ?: "Unknown Department";

    // Categorize by prefix (faculty vs student)
    if (strpos($authorId, 'FAC-') === 0) {
        $facultyPosts[] = $post;
    } else {
        $studentPosts[] = $post;
    }
}

$conn->close();
?>

   
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>U-Plug | Posts</title>
  <link rel="stylesheet" href="assets/css/admin-dashboard.css">
  <link rel="stylesheet" href="assets/css/posts.css">

</head>
<body>
  <div class="layout" id="layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo">Administrator</div>
      <ul class="nav">
        <li><a href="admin.php" class="nav-link">Dashboard</a></li>
        <li class="divider">User Settings</li>
        <li><a href="faculty.php" class="nav-link">Faculty Users</a></li>
        <li><a href="student.php" class="nav-link">Student Users</a></li>
        <li><a href="posts.php" class="nav-link active">Posts</a></li>
        <li class="divider">Settings</li>
        <li><a href="#" class="nav-link">About</a></li>
        <li><a href="assets/server/logout-process.php" class="nav-link">Logout</a></li>
      </ul>
    </aside>

    <!-- Burger -->
    <button id="burger" class="burger">&#9776;</button>

    <!-- Main -->
    <main class="main-content" id="main">
      <div class="scrollable-panel">
        <h1 class="page-title">Manage Posts</h1>

        <!-- FACULTY POSTS -->
        <section class="panel">
          <div class="panel-header">
            <h2>Faculty Posts</h2>
            <button class="expand-btn" data-target="faculty-posts">＋</button>
          </div>

          <div class="panel-body" id="faculty-posts">
            <div class="filter-container">
              <select id="faculty-filter" class="main-filter">
                <option value="all">All</option>
                <option value="official">Official</option>
                <option value="personal">Personal</option>
                <option value="department">Department</option>
              </select>

              <select id="faculty-department" class="sub-filter hide-filter">
                <option value="CITE">CITE</option>
                <option value="CCJE">CCJE</option>
                <option value="CAHS">CAHS</option>
                <option value="CAS">CAS</option>
                <option value="CEA">CEA</option>
                <option value="CELA">CELA</option>
                <option value="CMA">CMA</option>
                <option value="COL">COL</option>
              </select>
            </div>

            <div class="post-list" id="faculty-posts-list">
              <?php if (count($facultyPosts) === 0): ?>
                <div class="no-posts">No posts found.</div>
              <?php else: ?>
                <?php foreach ($facultyPosts as $post): ?>
                  <div class="post-card" data-id="<?= $post['post_id'] ?>">
                    <div class="post-cell dep"><?= htmlspecialchars($post['author_department']) ?></div>
                    <div class="post-cell name"><?= htmlspecialchars($post['author_name'] ?? 'Unknown Author') ?></div>
                    <div class="post-cell content">
                      <div class="title"><?= htmlspecialchars($post['title']) ?></div>
                      <div class="text"><?= htmlspecialchars($post['content']) ?></div>
                    </div>
                    <div class="post-cell actions">
                      <button class="view-btn">View</button>
                      <button class="delete-btn">Delete</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <!-- STUDENT POSTS -->
        <section class="panel">
          <div class="panel-header">
            <h2>Student Posts</h2>
            <button class="expand-btn" data-target="student-posts">＋</button>
          </div>

          <div class="panel-body" id="student-posts">
            <div class="filter-container">
              <select id="student-filter" class="main-filter">
                <option value="all">All</option>
                <option value="official">Official</option>
                <option value="personal">Personal</option>
                <option value="department">Department</option>
              </select>

              <select id="student-department" class="sub-filter hide-filter">
                <option value="CITE">CITE</option>
                <option value="CCJE">CCJE</option>
                <option value="CAHS">CAHS</option>
                <option value="CAS">CAS</option>
                <option value="CEA">CEA</option>
                <option value="CELA">CELA</option>
                <option value="CMA">CMA</option>
                <option value="COL">COL</option>
              </select>
            </div>

            <div class="post-list" id="student-posts-list">
              <?php if (count($studentPosts) === 0): ?>
                <div class="no-posts">No posts found.</div>
              <?php else: ?>
                <?php foreach ($studentPosts as $post): ?>
                  <div class="post-card" data-id="<?= $post['post_id'] ?>">
                    <div class="post-cell dep"><?= htmlspecialchars($post['author_department']) ?></div>
                    <div class="post-cell name"><?= htmlspecialchars($post['author_name'] ?? 'Unknown Author') ?></div>
                    <div class="post-cell content">
                      <div class="title"><?= htmlspecialchars($post['title']) ?></div>
                      <div class="text"><?= htmlspecialchars($post['content']) ?></div>
                    </div>
                    <div class="post-cell actions">
                      <button class="view-btn">View</button>
                      <button class="delete-btn" onclick="openDeleteModal('<?= $post['post_id'] ?>')">Delete</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <div class="footer">U-Plug ©2025. All rights reserved.</div>
      </div>
    </main>
  </div>

  <!-- View Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content large">
      <span class="close-btn" id="closeViewModal">&times;</span>
      <h2>Post Details</h2>
      <div class="modal-body">
        <p><strong>Department:</strong> <span id="viewDept"></span></p>
        <p><strong>Author:</strong> <span id="viewAuthor"></span></p>
        <p><strong>Title:</strong> <span id="viewTitle"></span></p>
        <p><strong>Content:</strong></p>
        <p id="viewContent" class="content-box"></p>
      </div>
    </div>
  </div>

<!-- ✅ MERGED DELETE MODAL -->
<div id="deleteModal" class="modal" aria-hidden="true">
  <div class="modal-content small">
    <button type="button" class="close-btn" id="closeDeleteModal" aria-label="Close" onclick="closeDeleteModal()">✕</button>
    <h3>Confirm Deletion</h3>
    <p>Are you sure you want to delete this post?</p>

    <!-- The working form (uses PHP backend logic) -->
    <form id="deleteForm" method="POST" action="delete-posts.php">
      <input type="hidden" name="redirect_to" value="posts.php">
      <input type="hidden" name="id" id="id">

      <div class="modal-actions">
        <button type="submit" id="confirm-delete" class="confirm-btn">Delete</button>
        <button type="button" id="cancel-delete" class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

 <script>
  
document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM loaded, initializing scripts..."); // Debugging (remove after testing)

    // Sidebar toggle
    const sidebar = document.getElementById("sidebar");
    const burger = document.getElementById("burger");
    burger.addEventListener("click", () => {
        sidebar.classList.toggle("hidden");
        // Adjust main content margin on toggle for better UX (optional)
        const main = document.getElementById("main");
        if (window.innerWidth > 900) {
            main.style.marginLeft = sidebar.classList.contains("hidden") ? "0" : "280px";
        }
    });

    // Expand/collapse panels
    document.querySelectorAll(".expand-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const targetId = btn.dataset.target;
            const target = document.getElementById(targetId);
            if (!target) {
                console.error(`Target element with ID '${targetId}' not found.`);
                return;
            }
            const expanded = target.classList.toggle("active");
            btn.textContent = expanded ? "−" : "＋";
            btn.classList.toggle("expanded", expanded); // Sync with CSS rotation
            console.log(`Panel ${targetId} toggled: ${expanded ? 'expanded' : 'collapsed'}`);
        });
    });

    // === FILTER FUNCTIONALITY ===
    // Helper function to filter posts in a given list
    function openDeleteModal(postId) {
  document.getElementById("delete_post_id").value = postId;
  document.getElementById("deleteModal").style.display = "block";
}

function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
}

    function filterPosts(postListId, mainFilterId, subFilterId) {
        const postCards = document.querySelectorAll(`#${postListId} .post-card`);
        const mainFilterValue = document.getElementById(mainFilterId).value;
        const subFilterValue = document.getElementById(subFilterId).value.toLowerCase(); // Ensure lowercase for comparison

        postCards.forEach(card => {
            const department = card.querySelector('.dep').textContent.trim().toLowerCase();
            const postType = card.dataset.postType || 'personal'; // Assume 'personal' if not set
            let show = false;

            if (mainFilterValue === 'all') {
                show = true;
            } else if (mainFilterValue === 'official') {
                show = postType.toLowerCase() === 'official';
            } else if (mainFilterValue === 'personal') {
                show = postType.toLowerCase() === 'personal';
            } else if (mainFilterValue === 'department') {
                show = department === subFilterValue; // Now both are lowercase
            }

            card.style.display = show ? 'grid' : 'none'; // Use 'grid' to match layout
        });
    }

    // Toggle sub-filter visibility and filter on main filter change
    function handleMainFilterChange(mainFilterId, subFilterId, postListId) {
        const mainFilter = document.getElementById(mainFilterId);
        const subFilter = document.getElementById(subFilterId);

        mainFilter.addEventListener('change', () => {
            if (mainFilter.value === 'department') {
                subFilter.classList.remove('hide-filter');
            } else {
                subFilter.classList.add('hide-filter');
                subFilter.value = subFilter.options[0].value;
            }
            filterPosts(postListId, mainFilterId, subFilterId);
        });
    }

    // Filter on sub-filter change
    function handleSubFilterChange(subFilterId, postListId, mainFilterId) {
        const subFilter = document.getElementById(subFilterId);
        subFilter.addEventListener('change', () => {
            filterPosts(postListId, mainFilterId, subFilterId);
        });
    }

    // Initialize filters for faculty posts
    handleMainFilterChange('faculty-filter', 'faculty-department', 'faculty-posts-list');
    handleSubFilterChange('faculty-department', 'faculty-posts-list', 'faculty-filter');

    // Initialize filters for student posts
    handleMainFilterChange('student-filter', 'student-department', 'student-posts-list');
    handleSubFilterChange('student-department', 'student-posts-list', 'student-filter');

    // Initial filter application
    filterPosts('faculty-posts-list', 'faculty-filter', 'faculty-department');
    filterPosts('student-posts-list', 'student-filter', 'student-department');

    // === VIEW POST ===
    document.addEventListener("click", e => {
        if (e.target.classList.contains("view-btn")) {
            const card = e.target.closest(".post-card");
            document.getElementById("viewDept").textContent = card.querySelector(".dep").textContent;
            document.getElementById("viewAuthor").textContent = card.querySelector(".name").textContent;
            document.getElementById("viewTitle").textContent = card.querySelector(".title").textContent;
            document.getElementById("viewContent").textContent = card.querySelector(".text").textContent;
            document.getElementById("viewModal").style.display = "flex";
        }
    });

    // === DELETE POST (Modal-based only - removed conflicting confirm() handler) ===
    let postToDelete = null;
    document.addEventListener("click", e => {
        if (e.target.classList.contains("delete-btn")) {
            postToDelete = e.target.closest(".post-card");
            document.getElementById("deleteModal").style.display = "flex";
        }
    });

    document.getElementById("confirm-delete").addEventListener("click", () => {
        if (!postToDelete) return;
        const id = postToDelete.dataset.id;

        fetch("delete_post.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + encodeURIComponent(id)  // Note: Changed to "id" to match your original; adjust if your PHP expects "post_id"
        })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === "success") {
                postToDelete.remove();
                alert("Post deleted successfully!");
            } else {
                alert("Failed to delete post: " + response);
            }
            document.getElementById("deleteModal").style.display = "none";
        })
        .catch(err => {
            alert("Error deleting post.");
            console.error(err);
        });
    });

    document.getElementById("cancel-delete").addEventListener("click", () => {
        document.getElementById("deleteModal").style.display = "none";
    });

    // Close modals
    document.querySelectorAll(".close-btn").forEach(btn => {
        btn.addEventListener("click", () => btn.closest(".modal").style.display = "none");
    });
    window.addEventListener("click", e => {
        if (e.target.classList.contains("modal")) e.target.style.display = "none";
    });
});
</script>
</body>
</html>