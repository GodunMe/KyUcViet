<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Smart NFC - Vietnamese Memories</title>
  <link rel="icon" type="image/png" href="logo.PNG" />
  <link rel="stylesheet" href="style.css" />
  <style>
    .header-img { position: relative; overflow: hidden; width: 100%; height: 300px; }
    .header-overlay {
      position: absolute; bottom: 10px; left: 10px;
      color: white; background: rgba(0,0,0,0.5);
      padding: 5px 10px; border-radius: 8px; z-index: 10;
    }
    .header-prev, .header-next {
      position: absolute; top: 50%; transform: translateY(-50%);
      background: rgba(0,0,0,0.5); color: white;
      border: none; font-size: 24px; cursor: pointer; z-index: 10;
      padding: 6px 12px; border-radius: 50%;
    }
    .header-prev { left: 10px; }
    .header-next { right: 10px; }

    .museum-list { margin-top: 20px; }
    .museum-card { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; cursor: pointer; }
    .museum-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 6px; margin-right: 10px; }
    .museum-row { display: flex; align-items: center; gap: 10px; }
    .small-muted { font-size: 0.85em; color: #666; }
  </style>
</head>
<body>
  <div class="container">
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
    <div id="map" ></div>
    <div id="routeInfo"></div>

    <!-- Danh sách bảo tàng -->
    <div id="museumList" class="museum-list"></div>

    <!-- Login -->
    <button class="login-btn">🔑 Đăng nhập</button>
  </div>

  <script>
    let currentIndex = 0;
    let headerImages = []; // [{id, mime_type}, ...]

    function updateHeaderImage() {
  const mediaBox = document.getElementById('headerMedia');
  mediaBox.innerHTML = ''; // clear
      const prevBtn = document.querySelector('.header-prev');
  const nextBtn = document.querySelector('.header-next');
  if (headerImages.length > 0) {
    const m = headerImages[currentIndex]; // {id, mime_type}
    const url = `showMedia.php?id=${m.id}`;
    let el;

    if (m.mime_type === "mp4" || m.mime_type === "webm" || m.mime_type === "ogg") {
      el = document.createElement('video');
      el.src = url;
      el.autoplay = true;
      el.loop = true;
      el.muted = true;
      el.playsInline = true;
      el.controls = true;
    } else if (m.mime_type === "jpg" || m.mime_type === "jpeg" || m.mime_type === "png" || m.mime_type === "gif") {
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
  // 🔹 Ẩn/hiện nút điều hướng
  if (headerImages.length <= 1) {
    prevBtn.style.display = "none";
    nextBtn.style.display = "none";
  } else {
    prevBtn.style.display = "block";
    nextBtn.style.display = "block";
  }
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
    function renderMuseumList(sorted) {
      const container = document.getElementById('museumList');
      container.innerHTML = '';

      sorted.forEach(m => {
        const card = document.createElement('div');
        card.className = 'museum-card';

        let mediaHTML = '';
        if (m.media && m.media.length > 0) {
          mediaHTML = `<img class="museum-thumb" src="showMedia.php?id=${m.media[0].id}" alt="${m.MuseumName}">`;
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
          showRoute(m);
          highlightMarker(m.marker, sorted);
          if (m.marker) map.panTo(m.marker.getPosition());
        });

        container.appendChild(card);
      });
    }

    // --- Google Map + vị trí ---
    let map, userMarker, directionsService, directionsRenderer, userLocation;

    function haversineDistance(lat1, lon1, lat2, lon2) {
      const R = 6371000;
      const toRad = v => v * Math.PI / 180;
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat/2)**2 +
        Math.cos(toRad(lat1))*Math.cos(toRad(lat2)) *
        Math.sin(dLon/2)**2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

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

    function showRoute(museum) {
      if (!userLocation) return;
      const request = {
        origin: userLocation,
        destination: { lat: museum.Latitude, lng: museum.Longitude },
        travelMode: 'DRIVING'
      };
      directionsService.route(request, (result, status) => {
        if (status === 'OK') {
          directionsRenderer.setDirections(result);
          const route = result.routes[0].legs[0];
          document.getElementById("routeInfo").textContent =
            `Khoảng cách: ${route.distance.text}, Thời gian: ${route.duration.text}`;
        }
      });
    }

    async function reverseGeocode(lat, lon) {
      try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}`;
        const res = await fetch(url);
        if (!res.ok) return null;
        const json = await res.json();
        return json.display_name || null;
      } catch { return null; }
    }

    async function locateAndUpdate(museums) {
      const addressEl = document.getElementById('userAddress');
      if (!navigator.geolocation) {
        addressEl.textContent = 'Trình duyệt không hỗ trợ vị trí.';
        renderMuseumList(museums);
        return;
      }

      navigator.geolocation.getCurrentPosition(async (pos) => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        userLocation = { lat, lng: lon };

        const addr = await reverseGeocode(lat, lon);
        if (addr && addressEl) {
          addressEl.textContent = 'Địa chỉ (gần): ' + addr;
        } else if (addressEl) {
          addressEl.textContent = `Vị trí: ${lat.toFixed(5)}, ${lon.toFixed(5)}`;
        }

        // --- Khởi tạo bản đồ ---
        map = new google.maps.Map(document.getElementById("map"), {
          center: userLocation,
          zoom: 14
        });

        // Khi click bất kỳ đâu trên bản đồ -> mở rộng
        map.addListener("click", () => {
          document.getElementById("map").classList.add("expanded");
        });

        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(map);

        // --- Marker user ---
        userMarker = new google.maps.Marker({
          position: userLocation,
          map: map,
          title: "Vị trí của bạn",
          icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        });

        const withDist = museums.map(m => {
          if (m.Latitude && m.Longitude) {
            const d = haversineDistance(lat, lon, m.Latitude, m.Longitude);
            const marker = new google.maps.Marker({
              position: { lat: m.Latitude, lng: m.Longitude },
              map: map,
              title: m.MuseumName,
              icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
            });

            m.marker = marker;

            marker.addListener("click", () => {
              setHeaderMuseum(m);
              highlightMarker(marker, museums);

            });
            window.addEventListener("scroll", () => {
              document.getElementById("map").classList.remove("expanded");
            });


            return {
              ...m,
              distance: d,
              distanceText: (d >= 1000) ? ((d / 1000).toFixed(2) + ' km') : (Math.round(d) + ' m'),
              marker: marker
            };
          }
          return m;
        });

        withDist.sort((a, b) => (a.distance || 9999999) - (b.distance || 9999999));
        setHeaderMuseum(withDist[0]);
        renderMuseumList(withDist);

      }, (err) => {
        console.warn('Geolocation error', err);
        addressEl.textContent = 'Không lấy được vị trí.';
        renderMuseumList(museums);
      });
    }

    async function init() {
      const res = await fetch('getMuseums.php');
      const museums = await res.json();
      renderMuseumList(museums);
      locateAndUpdate(museums);
    }
  </script>

  <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOVYRIgupAurZup5y1PRh8Ismb1A3lLao&callback=init"></script>
</body>
</html>
