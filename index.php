<?php
require 'auth_check.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smart NFC - Vietnamese Memories</title>
  <link rel="icon" type="image/png" href="logo.PNG" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="container">
    <!-- HTML -->
<div class="user-header" id="userHeader">
  <!-- User logged in state -->
  <div id="userLoggedIn" class="user-logged-in" style="display: none;">
    <div class="user-info">
      <img id="userAvatar" src="avatar/default.png" alt="User" class="user-icon">
      <span id="userName" class="user-name">Loading...</span>
    </div>
    <div class="user-points">
      <span id="userPoints">0đ</span>
    </div>
  </div>
  
  <!-- User not logged in state -->
  <div id="usernotLoggedIn" class="user-not-logged-in" style="display: none;">
    <div class="login-prompt">
      <span class="login-text">Khám phá bảo tàng cùng chúng tôi</span>
    </div>
    <button class="login-btn" onclick="goToLogin()">
      🔑 Đăng nhập
    </button>
  </div>
</div>

    <!-- Header -->
    <div id="header" class="header-img">
      <div id="headerMedia" style="width: 100%; height: 100%;"></div>
      <button class="header-prev" onclick="prevImage()">&#10094;</button>
      <button class="header-next" onclick="nextImage()">&#10095;</button>
      <div id="headerLabel" class="header-overlay">Bảo tàng gần bạn</div>
    </div>

    <!-- Danh sách bảo tàng -->
    <div id="museumList" class="museum-list"></div>
  </div>

  <!-- Bottom Navigation Bar -->
  <div class="bottom-nav">
    <div class="nav-item active" onclick="navigateToPage('home')">
      <div class="nav-icon">🏠</div>
      <div class="nav-label">Home</div>
    </div>
    <div class="nav-item" onclick="navigateToPage('map')">
      <div class="nav-icon">🗺️</div>
      <div class="nav-label">Map</div>
    </div>
    <div class="nav-item" onclick="navigateToPage('checkin')">
      <div class="nav-icon">📍</div>
      <div class="nav-label">Check-in</div>
    </div>
    <div class="nav-item" onclick="navigateToPage('leaderboard')">
      <div class="nav-icon">🏆</div>
      <div class="nav-label">Bảng xếp hạng</div>
    </div>
    <div class="nav-item" onclick="navigateToPage('profile')">
      <div class="nav-icon">👤</div>
      <div class="nav-label">Profile</div>
    </div>
  </div>

  <script>
    const BASE_URL = "";
    // JS
// Load user info on page load
async function loadUserInfo() {
  try {
    const response = await fetch('profile/getUserInfo.php');
    if (response.ok) {
      const data = await response.json();
      
      if (data.loggedIn) {
        // User is logged in - show user info
        showUserLoggedInState(data);
      } else {
        // User not logged in - show login button
        showUserNotLoggedInState();
      }
    } else {
      showUserNotLoggedInState();
    }
  } catch (error) {
    console.error('Error loading user info:', error);
    showUserNotLoggedInState();
  }
}

// Show user logged in state
function showUserLoggedInState(data) {
  document.getElementById('userLoggedIn').style.display = 'flex';
  document.getElementById('usernotLoggedIn').style.display = 'none';
  
  // Update user avatar
  const avatarElement = document.getElementById('userAvatar');
  avatarElement.src = data.avatarRelative || 'avatar/default.png';
  
  // Update user name
  const nameElement = document.getElementById('userName');
  nameElement.textContent = data.username || 'Guest User';
  
  // Apply role-based styling
  // First, remove any existing role classes
  nameElement.classList.remove('admin', 'customerpre', 'customer');
  avatarElement.classList.remove('admin', 'customerpre', 'customer');
  
  // Add appropriate class based on role
  const role = data.role ? data.role.toLowerCase() : 'customer';
  nameElement.classList.add(role);
  avatarElement.classList.add(role);
  
  // Update user points
  const pointsElement = document.getElementById('userPoints');
  pointsElement.textContent = (data.score || 0);
}

// Show user not logged in state
function showUserNotLoggedInState() {
  document.getElementById('userLoggedIn').style.display = 'none';
  document.getElementById('usernotLoggedIn').style.display = 'flex';
}

// Go to login page
function goToLogin() {
  // Kiểm tra xem có token không (có thể từ localStorage, sessionStorage, hoặc URL parameter)
  const urlParams = new URLSearchParams(window.location.search);
  const tokenFromUrl = urlParams.get('token');
  const tokenFromStorage = localStorage.getItem('nfc_token') || sessionStorage.getItem('nfc_token');
  
  if (tokenFromUrl || tokenFromStorage) {
    // Có token, chuyển đến trang login
    const token = tokenFromUrl || tokenFromStorage;
    window.location.href = `/login.php?token=${token}`;
  } else {
    // Không có token, chuyển đến trang thông báo NFC
    window.location.href = '/nfc_required.html';
  }
}

// --- Navigation ---
function navigateToPage(page) {
  // Remove active class from all nav items
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
  });
  
  // Add active class to clicked item
  event.target.closest('.nav-item').classList.add('active');
  
  // Navigate to different pages
  switch(page) {
    case 'home':
      // Already on home page, just refresh or scroll to top
      window.scrollTo(0, 0);
      break;
    case 'map':
      window.location.href = '/map.html';
      break;
    case 'checkin':
      window.location.href = '/checkin/checkin.html';
      break;
    case 'leaderboard':
      window.location.href = '/leaderboard/leaderboard.html';
      break;
    case 'profile':
      window.location.href = '/profile/profile.html';
      break;
    default:
      console.log('Unknown page:', page);
  }
}

let userLocation;
let currentIndex = 0;
let headerImages = [];

// --- Haversine ---
function haversineDistance(lat1, lon1, lat2, lon2) {
  const R = 6371000;
  const toRad = v => v * Math.PI / 180;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// --- Update header image/video ---
function updateHeaderImage() {
  const mediaBox = document.getElementById('headerMedia');
  mediaBox.innerHTML = '';
  const prevBtn = document.querySelector('.header-prev');
  const nextBtn = document.querySelector('.header-next');

  if (headerImages.length > 0) {
    const m = headerImages[currentIndex];
    const url = BASE_URL + m.file_path;
    let el;
    if (m.mime_type.startsWith("video") || url.match(/\.(mp4|webm|ogg)$/i)) {
      el = document.createElement('video');
      el.src = url;
      el.autoplay = true;
      el.loop = true;
      el.muted = true;
      el.playsInline = true;
      el.controls = true;
    } else if (m.mime_type.startsWith("image") || url.match(/\.(jpg|jpeg|png|gif)$/i)) {
      el = document.createElement('img');
      el.src = url;
    } else {
      el = document.createElement('div');
      el.innerText = "Không hỗ trợ định dạng: " + m.mime_type;
    }
    el.style.width = 'auto';
    el.style.height = '100%';
    el.style.margin = '0 auto';
    el.style.display = 'block';
    mediaBox.appendChild(el);
  } else {
    mediaBox.innerHTML = `<img src="placeholder.png" style="width:100%;height:100%;object-fit:cover;">`;
  }

  prevBtn.style.display = headerImages.length <= 1 ? "none" : "block";
  nextBtn.style.display = headerImages.length <= 1 ? "none" : "block";
}

function prevImage() {
  if (headerImages.length === 0) return;
  currentIndex = (currentIndex - 1 + headerImages.length) % headerImages.length;
  updateHeaderImage();
}

function nextImage() {
  if (headerImages.length === 0) return;
  currentIndex = (currentIndex + 1) % headerImages.length;
  updateHeaderImage();
}

function setHeaderMuseum(m) {
  headerImages = m.media && m.media.length > 0 ? m.media : [];
  currentIndex = 0;
  updateHeaderImage();
  document.getElementById('headerLabel').innerText = m.MuseumName;
}



// --- Render danh sách ---
function renderMuseumList(museums) {
  const container = document.getElementById('museumList');
  container.innerHTML = '';
  museums.forEach((m, index) => {
    const card = document.createElement('div');
    card.className = 'museum-card';
    let mediaHTML = '';
    if (m.media && m.media.length > 0) {
      const firstMedia = m.media[0];
      if (firstMedia.mime_type.startsWith("image")) {
        mediaHTML = `<img class="museum-thumb" src="${BASE_URL+firstMedia.file_path}" alt="${m.MuseumName}">`;
      } else {
        mediaHTML = `<img class="museum-thumb" src="video-placeholder.png" alt="${m.MuseumName}">`;
      }
    } else {
      mediaHTML = `<img class="museum-thumb" src="placeholder.png" alt="${m.MuseumName}">`;
    }

    card.innerHTML = `
      <div class="museum-row" onclick="toggleMuseumCard(event, ${index})">
        ${mediaHTML}
        <div class="museum-info">
          <h3>${m.MuseumName}</h3>
          <div class="small-muted">${m.distanceText || ''}</div>
        </div>
        <div class="expand-arrow">▼</div>
      </div>
      <div class="museum-details" id="museum-details-${index}">
        <div class="action-buttons">
          <button class="action-btn detail-btn" onclick="viewMuseumDetails(event, ${m.MuseumID})">
            📋 Xem chi tiết
          </button>
          <button class="action-btn direction-btn" onclick="getDirections(event, ${m.MuseumID}, '${m.MuseumName}', ${m.Latitude}, ${m.Longitude})">
            🧭 Chỉ đường
          </button>
        </div>
      </div>
    `;

    // Set header image when clicking on museum name (not the expand button)
    const museumInfo = card.querySelector('.museum-info');
    museumInfo.addEventListener('click', (e) => {
      e.stopPropagation();
      setHeaderMuseum(m);
    });

    container.appendChild(card);
  });
}

// --- Reverse geocode ---
async function reverseGeocode(lat, lon) {
  try {
    const res = await fetch(`proxy.php?lat=${lat}&lon=${lon}`);
    if (!res.ok) return null;
    const json = await res.json();
    return json.display_name || null;
  } catch (err) {
    console.error("Lỗi reverseGeocode:", err);
    return null;
  }
}

// --- Locate user + calculate distances ---
async function locateAndUpdate(museums) {
  if (!navigator.geolocation) {
    console.warn('Geolocation not supported');
    renderMuseumList(museums);
    return;
  }

  navigator.geolocation.getCurrentPosition(async pos => {
    const lat = parseFloat(pos.coords.latitude);
    const lon = parseFloat(pos.coords.longitude);
    userLocation = { lat, lng: lon };

    // --- Calculate distances and sort museums ---
    const withDist = museums.map(m => {
      const latNum = parseFloat(m.Latitude);
      const lonNum = parseFloat(m.Longitude);
      if (!isNaN(latNum) && !isNaN(lonNum)) {
        m.distance = haversineDistance(lat, lon, latNum, lonNum);
        m.distanceText = m.distance >= 1000 ? (m.distance / 1000).toFixed(2) + ' km' : Math.round(m.distance) + ' m';
      }
      return m;
    });

    withDist.sort((a, b) => (a.distance || 9999999) - (b.distance || 9999999));
    setHeaderMuseum(withDist[0]);
    renderMuseumList(withDist);

  }, err => {
    console.warn('Geolocation error', err);
    renderMuseumList(museums);
  });
}

// --- Toggle museum card ---
function toggleMuseumCard(event, index) {
  event.stopPropagation();
  const card = event.target.closest('.museum-card');
  const details = document.getElementById(`museum-details-${index}`);
  
  // Close all other cards first
  document.querySelectorAll('.museum-card').forEach(otherCard => {
    if (otherCard !== card) {
      otherCard.classList.remove('expanded');
    }
  });
  
  // Toggle current card
  card.classList.toggle('expanded');
}

// --- View museum details ---
function viewMuseumDetails(event, museumId) {
  event.stopPropagation();
  
  // Check if user is logged in first
  fetch('profile/getUserInfo.php')
    .then(response => response.json())
    .then(data => {
      if (data.loggedIn) {
        // User is logged in - allow access to museum details
        window.location.href = `/museum.html?id=${museumId}&fromIndex=true`;
      } else {
        // User not logged in - show alert and redirect to login
        alert('Bạn cần đăng nhập để xem chi tiết bảo tàng!');
        window.location.href = '/nfc_required.html';
      }
    })
    .catch(error => {
      console.error('Error checking login status:', error);
      alert('Bạn cần đăng nhập để xem chi tiết bảo tàng!');
      window.location.href = '/nfc_required.html';
    });
}

// --- Get directions ---
function getDirections(event, museumId, museumName, lat, lng) {
  event.stopPropagation();
  // Navigate to map with parameters for highlighting and routing
  const params = new URLSearchParams({
    highlight: museumId,
    name: museumName,
    lat: lat,
    lng: lng,
    route: 'true'
  });
  window.location.href = `/map.html?${params.toString()}`;
}

// --- Init ---
async function init() {
  // Load user info
  await loadUserInfo();
  
  // Load museums
  const res = await fetch('getMuseums.php');
  const museums = await res.json();
  locateAndUpdate(museums);
  renderMuseumList(museums);
}
</script>


  <script>
    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>
