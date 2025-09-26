# Yêu cầu và Thiết kế: Cải tiến Quy trình Check-in

## Mục lục
1. [Tổng quan](#tổng-quan)
2. [Quy trình check-in hiện tại](#quy-trình-check-in-hiện-tại)
3. [Yêu cầu cải tiến](#yêu-cầu-cải-tiến)
4. [Kiểm tra vị trí và tọa độ](#kiểm-tra-vị-trí-và-tọa-độ)
5. [Tải lên ảnh và trạng thái](#tải-lên-ảnh-và-trạng-thái)
6. [Giới hạn thời gian check-in](#giới-hạn-thời-gian-check-in)
7. [Tính năng mạng xã hội](#tính-năng-mạng-xã-hội)
8. [Cấu trúc cơ sở dữ liệu](#cấu-trúc-cơ-sở-dữ-liệu)
9. [API cần phát triển](#api-cần-phát-triển)
10. [Giao diện người dùng](#giao-diện-người-dùng)
11. [Kế hoạch triển khai](#kế-hoạch-triển-khai)

## Tổng quan

Tài liệu này tổng hợp các yêu cầu và thiết kế cho việc cải tiến quy trình check-in của ứng dụng KyUcViet. Mục tiêu là tạo ra một quy trình check-in toàn diện, có tính xã hội cao, khuyến khích người dùng khám phá nhiều bảo tàng và chia sẻ trải nghiệm của họ.

## Quy trình check-in hiện tại

Hiện tại, quy trình check-in rất đơn giản và có những hạn chế sau:
- Chỉ hỗ trợ nhập mã check-in thủ công hoặc quét QR code
- Không xác thực vị trí của người dùng
- Không có khả năng tải lên ảnh hoặc thêm trạng thái
- Không có giới hạn về tần suất check-in
- Không có kiểm tra tọa độ với vị trí của bảo tàng

## Yêu cầu cải tiến

Các yêu cầu cải tiến cho quy trình check-in bao gồm:

1. **Kiểm tra vị trí và tọa độ**:
   - Kiểm tra tọa độ của người dùng khi check-in
   - Xác minh người dùng đang ở trong phạm vi của bảo tàng (bán kính 50m)

2. **Tải lên ảnh và trạng thái**:
   - Cho phép người dùng chụp và tải lên 5-10 ảnh cho mỗi lần check-in
   - Cho phép thêm trạng thái (tùy chọn hoặc mẫu sẵn cho từng bảo tàng)

3. **Giới hạn thời gian check-in**:
   - Người dùng có thể check-in tối đa 2 lần/ngày tại một bảo tàng
   - Giữa 2 lần check-in tại cùng bảo tàng phải cách nhau ít nhất 30 phút
   - Người dùng được phép check-in tại tối đa 2 bảo tàng khác nhau mỗi ngày
   - Sau 3 ngày, người dùng mới có thể check-in lại tại bảo tàng đã check-in đủ số lần

4. **Tính năng mạng xã hội**:
   - Lưu lịch sử check-in như bài đăng mạng xã hội
   - Cho phép bạn bè và người khác xem bài đăng check-in
   - Hỗ trợ like và comment trên bài đăng check-in

## Kiểm tra vị trí và tọa độ

### Yêu cầu kỹ thuật:
- Sử dụng Geolocation API để lấy tọa độ hiện tại của người dùng
- So sánh tọa độ người dùng với tọa độ bảo tàng (lưu trong bảng `museum`)
- Tính toán khoảng cách giữa hai điểm và kiểm tra nếu người dùng trong phạm vi 50m
- Hiển thị khoảng cách và bản đồ nhỏ cho người dùng

### Công thức tính khoảng cách:
```javascript
// Hàm tính khoảng cách giữa hai điểm theo tọa độ GPS (theo công thức Haversine)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Bán kính trái đất tính bằng mét
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const d = R * c;
    
    return d; // khoảng cách tính bằng mét
}
```

## Tải lên ảnh và trạng thái

### Tải lên ảnh:
- Cho phép tải lên từ 1-10 ảnh cho mỗi lần check-in
- Hỗ trợ chụp ảnh trực tiếp từ camera hoặc chọn từ thư viện
- Hiển thị xem trước ảnh và cho phép xóa/sắp xếp lại trước khi đăng
- Tối ưu hóa và nén ảnh để giảm kích thước

### Trạng thái check-in:
- Cho phép nhập trạng thái tùy chọn
- Cung cấp các mẫu trạng thái phù hợp với từng bảo tàng
- Ví dụ mẫu trạng thái:
  * "Đang khám phá lịch sử tại [tên bảo tàng]"
  * "Ấn tượng với các hiện vật tại [tên bảo tàng]"
  * "Học hỏi được nhiều điều thú vị tại [tên bảo tàng]"

## Giới hạn thời gian check-in

### Quy tắc chi tiết:
1. **Giới hạn cho mỗi bảo tàng**:
   - Người dùng có thể check-in tối đa 2 lần/ngày tại một bảo tàng
   - Giữa 2 lần check-in tại cùng một bảo tàng phải cách nhau ít nhất 30 phút
   - Sau 3 ngày kể từ ngày check-in đầu tiên, người dùng mới có thể check-in lại tại bảo tàng đó (khi đã check-in đủ số lần)

2. **Giới hạn tổng thể**:
   - Người dùng được phép check-in tại tối đa 2 bảo tàng khác nhau mỗi ngày
   - Không có thời gian chờ giữa các lần check-in ở các bảo tàng khác nhau

### Hiển thị trạng thái giới hạn:
- Hiển thị số lần check-in còn lại trong ngày tại mỗi bảo tàng
- Hiển thị thời gian còn lại phải chờ (nếu có)
- Hiển thị số bảo tàng còn có thể check-in trong ngày

### Thông báo mẫu:
1. "Bạn đã check-in đủ 2 lần tại Bảo tàng Lịch sử hôm nay. Bạn có thể check-in lại sau 3 ngày (vào ngày 29/09/2025)."
2. "Bạn đã check-in tại Bảo tàng Nghệ thuật cách đây 15 phút. Bạn cần đợi thêm 15 phút nữa để có thể check-in lại."
3. "Bạn đã check-in tại 2 bảo tàng hôm nay. Bạn có thể check-in tại các bảo tàng khác vào ngày mai."

## Tính năng mạng xã hội

### Bài đăng check-in:
- Mỗi lần check-in được lưu như một bài đăng với ảnh, trạng thái, vị trí, thời gian
- Cho phép thiết lập quyền riêng tư (công khai, chỉ bạn bè, riêng tư)
- Hỗ trợ tương tác (like, comment) trên bài đăng check-in

### Bảng tin và hồ sơ:
- Thêm tab "Bảng tin" hiển thị các check-in gần đây từ bạn bè
- Cập nhật trang hồ sơ để hiển thị lịch sử check-in như dòng thời gian

### Quản lý bạn bè:
- Hệ thống gửi lời mời kết bạn và quản lý danh sách bạn bè
- Kiểm soát quyền riêng tư dựa trên mối quan hệ

## Cấu trúc cơ sở dữ liệu

### Bảng `checkins`:
```sql
CREATE TABLE checkins (
    CheckinID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(255) NOT NULL,
    MuseumID INT NOT NULL,
    Latitude DECIMAL(10,7) NOT NULL,
    Longitude DECIMAL(10,7) NOT NULL,
    Status TEXT,
    CheckinTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Points INT DEFAULT 0,
    Privacy ENUM('public', 'friends', 'private') DEFAULT 'public',
    FOREIGN KEY (UserToken) REFERENCES users(UserToken),
    FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID)
);
```

### Bảng `checkin_photos`:
```sql
CREATE TABLE checkin_photos (
    PhotoID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    PhotoPath VARCHAR(255) NOT NULL,
    Caption TEXT,
    UploadOrder INT NOT NULL,
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE
);
```

### Bảng `museum_checkin_rules`:
```sql
CREATE TABLE museum_checkin_rules (
    MuseumID INT PRIMARY KEY,
    MaxCheckinPerDay INT NOT NULL DEFAULT 2, -- Số lần check-in tối đa mỗi ngày tại một bảo tàng
    MinTimeBetweenCheckins INT NOT NULL DEFAULT 1800, -- Thời gian tối thiểu giữa các lần check-in (30 phút = 1800 giây)
    DaysBetweenRevisit INT NOT NULL DEFAULT 3, -- Số ngày chờ trước khi có thể check-in lại
    FOREIGN KEY (MuseumID) REFERENCES museum(MuseumID)
);
```

### Bảng `daily_checkin_limits`:
```sql
CREATE TABLE daily_checkin_limits (
    UserToken VARCHAR(255) NOT NULL,
    CheckinDate DATE NOT NULL,
    MuseumsVisitedCount INT NOT NULL DEFAULT 0, -- Số bảo tàng khác nhau đã check-in trong ngày
    LastResetTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Thời điểm cuối cùng đặt lại bộ đếm
    PRIMARY KEY (UserToken, CheckinDate),
    FOREIGN KEY (UserToken) REFERENCES users(UserToken)
);
```

### Bảng tương tác xã hội:
```sql
CREATE TABLE checkin_likes (
    LikeID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(255) NOT NULL,
    LikeTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (CheckinID, UserToken),
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    FOREIGN KEY (UserToken) REFERENCES users(UserToken)
);

CREATE TABLE checkin_comments (
    CommentID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    UserToken VARCHAR(255) NOT NULL,
    Comment TEXT NOT NULL,
    CommentTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE,
    FOREIGN KEY (UserToken) REFERENCES users(UserToken)
);

CREATE TABLE user_friends (
    FriendshipID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken1 VARCHAR(255) NOT NULL,
    UserToken2 VARCHAR(255) NOT NULL,
    Status ENUM('pending', 'accepted', 'rejected', 'blocked') NOT NULL,
    RequestTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (UserToken1, UserToken2),
    FOREIGN KEY (UserToken1) REFERENCES users(UserToken),
    FOREIGN KEY (UserToken2) REFERENCES users(UserToken)
);
```

## API cần phát triển

### API kiểm tra và check-in:
1. `checkCheckinEligibility.php`: Kiểm tra xem người dùng có thể check-in tại bảo tàng không
2. `verifyLocation.php`: Kiểm tra vị trí người dùng so với bảo tàng
3. `uploadMultiplePhotos.php`: Xử lý tải lên nhiều ảnh
4. `createCheckinPost.php`: Tạo bài đăng check-in mới
5. `getRecentCheckins.php`: Lấy lịch sử check-in gần đây của người dùng

### API mạng xã hội:
1. `getSocialFeed.php`: Lấy bảng tin check-in từ bạn bè
2. `likeComment.php`: Xử lý tương tác like, comment
3. `getUserCheckins.php`: Lấy lịch sử check-in của một người dùng

## Giao diện người dùng

### Quy trình check-in theo bước:
1. **Bước 1: Xác định bảo tàng**
   - Quét QR hoặc nhập mã check-in
   - Hiển thị thông tin bảo tàng và kiểm tra tư cách check-in

2. **Bước 2: Xác minh vị trí**
   - Kiểm tra vị trí người dùng có trong phạm vi bảo tàng không
   - Hiển thị khoảng cách và bản đồ nhỏ

3. **Bước 3: Chụp/Tải ảnh**
   - Cho phép chụp hoặc chọn nhiều ảnh (tối đa 10)
   - Hiển thị thumbnails và cho phép sắp xếp lại

4. **Bước 4: Thêm trạng thái**
   - Nhập trạng thái hoặc chọn từ các mẫu gợi ý
   - Thiết lập quyền riêng tư

5. **Bước 5: Xác nhận check-in**
   - Kiểm tra lại tất cả thông tin
   - Hiển thị điểm thưởng và xác nhận

### Giao diện bài đăng check-in:
- Hiển thị ảnh trong gallery có thể trượt
- Hiển thị tên bảo tàng, thời gian check-in, trạng thái
- Hiển thị bản đồ nhỏ với vị trí check-in
- Khu vực like và comment

### Trang bảng tin:
- Dòng thời gian với các bài đăng check-in từ bạn bè
- Bộ lọc theo thời gian, bảo tàng, người dùng

## Kế hoạch triển khai

### Giai đoạn 1: Chức năng check-in cơ bản
- Xác minh vị trí và tọa độ
- Tải lên một ảnh và trạng thái
- Giới hạn check-in cơ bản

### Giai đoạn 2: Quản lý ảnh và giới hạn
- Nâng cấp để hỗ trợ nhiều ảnh
- Triển khai đầy đủ các quy tắc giới hạn check-in
- Cải thiện giao diện người dùng

### Giai đoạn 3: Tính năng mạng xã hội
- Bài đăng và tương tác (like, comment)
- Bảng tin và trang hồ sơ
- Quản lý quyền riêng tư và bạn bè

### Giai đoạn 4: Tối ưu hóa và mở rộng
- Cải thiện hiệu suất
- Thêm thành tích và phần thưởng
- Tích hợp với các nền tảng xã hội khác