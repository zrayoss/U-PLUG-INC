<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require __DIR__ . "/assets/config/dbconfig.php";
} else {
    header("Location: index.php");
    exit();
}


$currentUser = $_SESSION['user_id'];  
$stmt = $conn->prepare("SELECT post_id, title, create_date, edited_at, toast_status, toast_message FROM posts WHERE toast_status = 1 AND author_id != ?");
$stmt->bind_param("s", $currentUser);
$stmt->execute();
$result = $stmt->get_result();

$toastPosts = [];
while ($row = $result->fetch_assoc()) {
    $toastPosts[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>U-Plug Campus Map</title>
  <link rel="stylesheet" href="assets/css/map.css">
  <link rel="icon" href="assets/images/client/UplugLogo.png" type="image/png">
  <script src="assets/javascript/toast-notif.js" defer></script>
</head>
<body>

<div id="toastContainer" class="toast-container">
  <?php foreach ($toastPosts as $post): ?>
    <div class="toast" data-post-id="<?= $post['post_id'] ?>">
      <span><?= htmlspecialchars($post['toast_message']) ?></span><br>
      <small style="opacity: 0.8;">
        <?= empty($post['edited_at'])
          ? date("F j, Y - h:i A", strtotime($post['create_date']))
          : "Edited at: " . date("F j, Y - h:i A", strtotime($post['edited_at'])) ?>
      </small>
      <button class="dismiss-toast">X</button>
    </div>
  <?php endforeach; ?>
</div>

<nav class="navbar">
  <div class="nav-left">
    <div class="logo">U-Plug</div>
    <div class="nav-links">
      <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <a href="admin.php">Home</a>
        <a href="posts.php">Posts</a>
      <?php else: ?>
        <a href="home.php">Home</a>
        <a href="news.php">News</a>
      <?php endif; ?>
      
      <a href="map.php" class="active">Map</a>
      <a href="messaging.php">Messages</a>
      <a href="profile.php">Profile</a>
      <a href="logout.php">Logout</a>  
    </div>
  </div>
  <div class="nav-right">
    <div class="search-wrapper">
      <input type="text" id="searchInput" placeholder="Search profiles by name..." autocomplete="off">
      <div id="searchResults"></div>
    </div>
  </div>
</nav>

<main class="map-main">
  <section class="map-controls">
    <h2>Campus Map</h2>
    <label for="building-select">Choose a building:</label>
    <select id="building-select">
      <option value="">-- Select a building --</option>
      <option value="student-plaza">Student Plaza</option>
      <option value="riverside-building">Riverside Building</option>
      <option value="mba">MBA</option>
      <option value="north-hall">North Hall</option>
      <option value="gym">Gym</option>
      <option value="basic-ed">Basic Ed</option>
      <option value="ptc">PTC</option>
      <option value="csdl-its">CSDL ITS</option>
      <option value="fvr">FVR</option>
      <option value="cma">CMA</option>
      <option value="phinma-garden">Phinma Garden</option>
      <option value="main-entrance-gate">Main Entrance Gate</option>
    </select>
  </section>
  <section class="map-display">
    <div id="building-info" class="building-info">
      <p>Select a building to view details and images.</p>
      <div class="left-image"></div>
      <div class="right-images"></div>
    </div>
  </section>
</main>

<!-- Modal for full-size image -->
<div id="imageModal" class="modal">
  <span class="close">&times;</span>
  <img class="modal-content" id="modalImage">
</div>

<!-- FOR JS -->
<script>
const buildingData = {
  "student-plaza": {
    description: "Student Plaza: Main student activity area.",
    images: [
      "assets/images/buildings/sp.jpg",
      "assets/images/buildings/sp1.jpg",
      "assets/images/buildings/sp3.jpg"
    ]
  },
  "riverside-building": {
    description: "Riverside Building: Classrooms and offices.",
    images: [
      "assets/images/buildings/rs2.jpg",
      "assets/images/buildings/rs1.jpg",
      "assets/images/buildings/rs3.jpg"
    ]
  },
  "mba": {
    description: "MBA: Engineering building.",
    images: [
      "assets/images/buildings/mba.jpg",
      "assets/images/buildings/mba1.jpg",
      "assets/images/buildings/mba2.jpg"
    ]
  },
  "north-hall": {
    description: "North Hall: Academic classrooms.",
    images: [
      "assets/images/buildings/nh.jpg",
      "assets/images/buildings/nh1.jpg",
      "assets/images/buildings/nh2.jpg"
    ]
  },
  "gym": {
    description: "Gym: Sports and PE activities.",
    images: [
      "assets/images/buildings/gym.jpg",
      "assets/images/buildings/gym1.jpg",
      "assets/images/buildings/gym2.jpg"
    ]
  },
  "basic-ed": {
    description: "Basic Ed: Basic Education Department.",
    images: [
      "assets/images/buildings/BE.jpg",
      "assets/images/buildings/BE1.jpg",
      "assets/images/buildings/BE2.jpg"
    ]
  },
  "ptc": {
    description: "PTC: Professional Training Center.",
    images: [
      "assets/images/buildings/cite1.jpg",
      "assets/images/buildings/cite2.jpg",
      "assets/images/buildings/cite3.jpg"
    ]
  },
  "csdl-its": {
    description: "CSDL ITS: Computer Science and IT Services.",
    images: [
      "assets/images/buildings/csdl-its/image1.jpg",
      "assets/images/buildings/csdl-its/image2.jpg",
      "assets/images/buildings/csdl-its/image3.jpg"
    ]
  },
  "fvr": {
    description: "FVR: Faculty and admin offices.",
    images: [
      "assets/images/buildings/fvr.jpg",
      "assets/images/buildings/fvr1.jpg",
      "assets/images/buildings/fvr2.jpg"
    ]
  },
  "cma": {
    description: "CMA: College of Management and Accountancy.",
    images: [
      "assets/images/buildings/cmain.jpg",
      "assets/images/buildings/cma.jpg",
      "assets/images/buildings/cma2.jpg"
    ]
  },
  "phinma-garden": {
    description: "Phinma Garden: Campus green space.",
    images: [
      "assets/images/buildings/pg.jpg",
      "assets/images/buildings/os.jpg",
      "assets/images/buildings/os2.png"
    ]
  },
  "main-entrance-gate": {
    description: "Main Entrance Gate: Campus entry point.",
    images: [
      "assets/images/buildings/me1.jpg",
      "assets/images/buildings/me2.jpg",
      "assets/images/buildings/main-entrance-gate/image3.jpg"
    ]
  }
};

let currentImageIndex = 0;
let currentBuildingKey = '';

window.addEventListener('DOMContentLoaded', () => {
  const infoDiv = document.getElementById('building-info');
  infoDiv.innerHTML = '';

  const campusImg = document.createElement('img');
  campusImg.src = 'assets/images/buildings/sb.jpg'; 
  campusImg.alt = 'Campus Overview';
  campusImg.className = 'campus-full'; 

  infoDiv.appendChild(campusImg);
});

document.getElementById('building-select').addEventListener('change', function () {
  const val = this.value;
  const infoDiv = document.getElementById('building-info');
  infoDiv.innerHTML = '';

  if (val && buildingData[val]) {
    const data = buildingData[val];

    const descPara = document.createElement('p');
    descPara.textContent = data.description;
    descPara.style.width = '100%';
    descPara.style.textAlign = 'center';
    descPara.style.marginBottom = '1rem';
    infoDiv.appendChild(descPara);

    const leftDiv = document.createElement('div');
    leftDiv.className = 'left-image';
    const rightDiv = document.createElement('div');
    rightDiv.className = 'right-images';

    data.images.forEach((imgSrc, index) => {
      const img = document.createElement('img');
      img.src = imgSrc;
      img.alt = `${val} Image ${index + 1}`;
      img.style.cursor = 'pointer';
      img.onerror = () => {
        img.src = 'assets/images/buildings/placeholder.jpg';
      };

      img.addEventListener('click', function () {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        modal.style.display = 'block';
        modalImg.src = this.src;
        currentImageIndex = index;
        currentBuildingKey = val;
      });

      if (index === 0) {
        leftDiv.appendChild(img);
      } else {
        rightDiv.appendChild(img);
      }
    });

    infoDiv.appendChild(leftDiv);
    infoDiv.appendChild(rightDiv);
  } else {
    const campusImg = document.createElement('img');
    campusImg.src = 'assets/images/buildings/sb.jpg';
    campusImg.alt = 'Campus Overview';
    campusImg.className = 'campus-full';
    infoDiv.appendChild(campusImg);
  }
});

// Modal logic
const modal = document.getElementById('imageModal');
const closeBtn = document.getElementsByClassName('close')[0];

closeBtn.onclick = () => modal.style.display = 'none';
modal.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };

document.addEventListener('keydown', function (event) {
  if (modal.style.display !== 'block') return;

  if (event.key === 'Escape') {
    modal.style.display = 'none';
  }

  if (!buildingData[currentBuildingKey]) return;
  const images = buildingData[currentBuildingKey].images;

  if (event.key === 'ArrowLeft') {
    currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
  } else if (event.key === 'ArrowRight') {
    currentImageIndex = (currentImageIndex + 1) % images.length;
  } else {
    return;
  }

  document.getElementById('modalImage').src = images[currentImageIndex];
});

// Toast notifications
fetch('assets/server/load-toasts.php')
  .then(res => res.json())
  .then(data => {
    if (Array.isArray(data)) {
      data.forEach(toast => {
        showToast(toast.message, toast.type, 'poll', toast.link);
        fetch('assets/server/ack-toast.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `message=${encodeURIComponent(toast.message)}`
        });
      });
    }
  });


const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');

searchInput.addEventListener('input', function () {
  const query = this.value.trim();
  if (query.length === 0) {
    searchResults.style.display = 'none';
    searchResults.innerHTML = '';
    return;
  }

  fetch('assets/server/search-profile.php?q=' + encodeURIComponent(query))
    .then(res => res.text())
    .then(html => {
      searchResults.innerHTML = html;
      searchResults.style.display = 'block';
    });
});

function viewProfile(userId) {
  window.location.href = 'assets/server/view-profile.php?user_id=' + encodeURIComponent(userId);
}
</script>


</body>
</html>   