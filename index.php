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
<div class="user-header">
  <div class="user-info" onclick="toggleUserBox()">
    <img src="avatar.png" alt="User" class="user-icon">
    <span class="user-name">Nguyen Van A</span>
  </div>
  <div class="user-box" id="userBox">
    <button onclick="editProfile()">Chỉnh sửa thông tin</button>
    <button onclick="logout()">Logout</button>
  </div>
</div>

    <!-- Header -->
    <div id="header" class="header-img">
      <div id="headerMedia" style="width: 100%; height: 100%;"></div>
      <button class="header-prev" onclick="prevImage()">&#10094;</button>
      <button class="header-next" onclick="nextImage()">&#10095;</button>
      <div id="headerLabel" class="header-overlay">Bảo tàng gần bạn</div>
    </div>

    <!-- Điểm số -->
    <div class="score-section">
      <h2>Điểm của bạn: <span id="userScore">189</span></h2>
      <p id="userAddress" class="small-muted">Đang lấy vị trí… (hãy cho phép vị trí)</p>
    </div>

    <!-- Map -->
    <div class="map-section">
      <div id="map"></div>
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
      <div class="nav-icon">�</div>
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
    const BASE_URL = "/EXEProject";
    // JS
const userBox = document.getElementById('userBox');
const userInfo = document.querySelector('.user-info');

// Quản lý toggle box
document.addEventListener('click', (e) => {
  // Click vào avatar → toggle
  if (userInfo.contains(e.target)) {
    userBox.classList.toggle('active');
    return;
  }

  // Click vào box → ẩn
  if (userBox.contains(e.target)) {
    userBox.classList.remove('active');
    return;
  }

  // Click ngoài → ẩn
  if (userBox.classList.contains('active')) {
    userBox.classList.remove('active');
  }
});


function editProfile() {
  window.location.href = '/KyUcViet/editProfile.html';
}

function logout() {
  window.location.href = '/KyUcViet/login.html';
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
      window.location.href = '/KyUcViet/map.html';
      break;
    case 'checkin':
      window.location.href = '/KyUcViet/checkin.html';
      break;
    case 'leaderboard':
      window.location.href = '/KyUcViet/leaderboard.html';
      break;
    case 'profile':
      window.location.href = '/KyUcViet/profile.html';
      break;
    default:
      console.log('Unknown page:', page);
  }
}

let map, userMarker, directionsService, directionsRenderer, userLocation;
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

// --- Highlight marker ---
function highlightMarker(selectedMarker, museums) {
  const defaultIcon = {
    url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png",
    scaledSize: new google.maps.Size(32, 32)
  };
  const highlightedIcon = {
    url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png",
    scaledSize: new google.maps.Size(40, 40)
  };
  museums.forEach(m => {
    if (m.marker) {
      m.marker.setIcon(m.marker === selectedMarker ? highlightedIcon : defaultIcon);
    }
  });
}

// --- Render danh sách ---
function renderMuseumList(museums) {
  const container = document.getElementById('museumList');
  container.innerHTML = '';
  museums.forEach(m => {
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
      <div class="museum-row">
        ${mediaHTML}
        <div>
          <h3>${m.MuseumName}</h3>
          <div class="small-muted">${m.distanceText || ''}</div>
        </div>
      </div>
    `;

    card.addEventListener('click', () => {
      setHeaderMuseum(m);
      highlightMarker(m.marker, museums);
      if (m.marker) map.panTo(m.marker.getPosition());
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

// --- Locate user + render map ---
async function locateAndUpdate(museums) {
  const addressEl = document.getElementById('userAddress');
  if (!navigator.geolocation) {
    addressEl.textContent = 'Trình duyệt không hỗ trợ vị trí.';
    renderMuseumList(museums);
    return;
  }

  navigator.geolocation.getCurrentPosition(async pos => {
    const lat = parseFloat(pos.coords.latitude);
    const lon = parseFloat(pos.coords.longitude);
    userLocation = { lat, lng: lon };

    const addr = await reverseGeocode(lat, lon);
    addressEl.textContent = addr ? 'Địa chỉ (gần): ' + addr : `Vị trí: ${lat.toFixed(5)}, ${lon.toFixed(5)}`;

    // --- Init map ---
    map = new google.maps.Map(document.getElementById("map"), {
      center: userLocation,
      zoom: 14
    });

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({ map: map });

    // --- Marker user ---
    userMarker = new google.maps.Marker({
      position: userLocation,
      map: map,
      title: "Vị trí của bạn",
      icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
    });

    // --- Marker museums ---
    const withDist = museums.map(m => {
      const latNum = parseFloat(m.Latitude);
      const lonNum = parseFloat(m.Longitude);
      if (!isNaN(latNum) && !isNaN(lonNum)) {
        const marker = new google.maps.Marker({
          position: { lat: latNum, lng: lonNum },
          map: map,
          title: m.MuseumName,
          icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
        });

        m.marker = marker;

        marker.addListener("click", () => {
          setHeaderMuseum(m);
          highlightMarker(marker, withDist);
          map.panTo(marker.getPosition());
        });

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
    addressEl.textContent = 'Không lấy được vị trí.';
    renderMuseumList(museums);
  });
}

// --- Init ---
async function init() {
  const res = await fetch('getMuseums.php');
  const museums = await res.json();
  locateAndUpdate(museums);
  renderMuseumList(museums);
}
</script>


  <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOVYRIgupAurZup5y1PRh8Ismb1A3lLao&callback=init"></script>
</body>
</html>
