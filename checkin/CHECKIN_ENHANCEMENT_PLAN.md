# KẾ HOẠCH CẢI TIẾN CHỨC NĂNG CHECK-IN

> **Tài liệu tổng hợp:** Các thay đổi cần thiết cho hệ thống check-in  
> **Ngày tạo:** 30/09/2025  
> **Trạng thái:** Planning Phase  

---

## 📋 **TỔNG QUAN CÁC THAY ĐỔI**

### **A. Database Structure Changes**
### **B. Business Logic Updates** 
### **C. Code Implementation**
### **D. Validation & Security**

---

## 🗄️ **PHẦN A: DATABASE STRUCTURE CHANGES**

### **1. Bảng `checkins` - Thay đổi cấu trúc chính**

#### **1.1 Thay đổi field Status → Caption + ApprovalStatus**
```sql
-- Bước 1: Thêm các field mới
ALTER TABLE checkins 
ADD COLUMN ApprovalStatus ENUM('none', 'approved', 'denied') DEFAULT 'none' AFTER Status,
ADD COLUMN DeniedReason TEXT NULL AFTER ApprovalStatus,
ADD COLUMN PendingPoints INT DEFAULT 0 AFTER DeniedReason,
ADD COLUMN ActualPoints INT DEFAULT 0 AFTER PendingPoints,
ADD COLUMN ProcessedAt TIMESTAMP NULL AFTER ActualPoints,
ADD COLUMN ProcessedBy VARCHAR(64) NULL AFTER ProcessedAt;

-- Bước 2: Migration dữ liệu cũ
UPDATE checkins SET Caption = Status WHERE Status IS NOT NULL;

-- Bước 3: Đổi tên field Status → Caption
ALTER TABLE checkins CHANGE COLUMN Status Caption TEXT;

-- Bước 4: Cập nhật Points logic
UPDATE checkins SET 
    PendingPoints = Points,
    ActualPoints = Points,
    ApprovalStatus = 'approved'
WHERE Points > 0;

-- Bước 5: Thêm indexes
ALTER TABLE checkins
ADD INDEX idx_approval_status (ApprovalStatus),
ADD INDEX idx_user_status (UserToken, ApprovalStatus),
ADD INDEX idx_process_time (ProcessedAt);
```

#### **1.2 Kết quả cấu trúc mới**
```sql
CREATE TABLE checkins (
    CheckinID INT AUTO_INCREMENT PRIMARY KEY,
    UserToken VARCHAR(64) NOT NULL,
    MuseumID INT NOT NULL,
    Latitude DECIMAL(10,7) NOT NULL,
    Longitude DECIMAL(10,7) NOT NULL,
    Caption TEXT,                    -- ← Đổi từ Status (user comment)
    ApprovalStatus ENUM('none', 'approved', 'denied') DEFAULT 'none',  -- ← MỚI
    DeniedReason TEXT NULL,          -- ← MỚI (lý do admin từ chối)
    PendingPoints INT DEFAULT 0,     -- ← MỚI (điểm chờ duyệt)
    ActualPoints INT DEFAULT 0,      -- ← MỚI (điểm thực tế)
    ProcessedAt TIMESTAMP NULL,      -- ← MỚI (thời gian admin xử lý)
    ProcessedBy VARCHAR(64) NULL,    -- ← MỚI (admin token)
    CheckinTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Points INT DEFAULT 0,            -- ← GIỮ NGUYÊN (backward compatibility)
    
    -- Indexes
    INDEX idx_user (UserToken),
    INDEX idx_museum (MuseumID),
    INDEX idx_approval_status (ApprovalStatus),
    INDEX idx_user_status (UserToken, ApprovalStatus),
    INDEX idx_process_time (ProcessedAt)
);
```

### **2. Bảng `checkin_photos` - Thêm UserToken**

```sql
-- Thêm UserToken để dễ quản lý
ALTER TABLE checkin_photos 
ADD COLUMN UserToken VARCHAR(64) NOT NULL AFTER CheckinID,
ADD CONSTRAINT fk_photos_user FOREIGN KEY (UserToken) REFERENCES users(UserToken) ON DELETE CASCADE;

-- Migration dữ liệu
UPDATE checkin_photos cp 
JOIN checkins c ON cp.CheckinID = c.CheckinID 
SET cp.UserToken = c.UserToken;

-- Thêm index
ALTER TABLE checkin_photos
ADD INDEX idx_user_photos (UserToken);
```

### **3. Bảng `checkin_status_history` - Audit Trail**

```sql
-- Bảng mới để lưu lịch sử thay đổi trạng thái
CREATE TABLE checkin_status_history (
    HistoryID INT AUTO_INCREMENT PRIMARY KEY,
    CheckinID INT NOT NULL,
    OldStatus ENUM('none', 'approved', 'denied'),
    NewStatus ENUM('none', 'approved', 'denied'),
    OldPoints INT DEFAULT 0,
    NewPoints INT DEFAULT 0,
    AdminToken VARCHAR(64),
    Reason TEXT,
    ChangedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_checkin (CheckinID),
    INDEX idx_admin (AdminToken),
    INDEX idx_time (ChangedAt),
    FOREIGN KEY (CheckinID) REFERENCES checkins(CheckinID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **4. Cập nhật Check-in Rules**

```sql
-- Cập nhật quy tắc theo yêu cầu mới
UPDATE museum_checkin_rules SET 
    MaxCheckinPerDay = 1,            -- 1 lần/ngày tại 1 bảo tàng
    DaysBetweenRevisit = 7,          -- 1 tuần (7 ngày) mới được check-in lại
    MinTimeBetweenCheckins = 0       -- Không cần thời gian chờ
WHERE MuseumID IN (SELECT MuseumID FROM museum);
```

---

## 🏗️ **PHẦN B: BUSINESS LOGIC UPDATES**

### **1. Check-in Validation Rules**

#### **1.1 Giới hạn hiện tại cần thay đổi:**
- ❌ **Hiện tại:** 10 lần/ngày tại 1 bảo tàng  
- ✅ **Mới:** 1 lần/ngày tại 1 bảo tàng

- ❌ **Hiện tại:** 2 ngày chờ giữa các lần check-in  
- ✅ **Mới:** 7 ngày (1 tuần) chờ giữa các lần check-in

- ❌ **Hiện tại:** Không giới hạn số bảo tàng/ngày  
- ✅ **Mới:** Tối đa 2 bảo tàng/ngày

#### **1.2 Validation Logic Matrix:**
| Điều kiện | Hiện tại | Yêu cầu mới | Status |
|-----------|----------|-------------|---------|
| Check-in/ngày tại 1 bảo tàng | 10 lần | 1 lần | ❌ Cần sửa |
| Thời gian chờ giữa các lần | 2 ngày | 7 ngày | ❌ Cần sửa |
| Số bảo tàng/ngày | Không giới hạn | 2 bảo tàng | ❌ Cần sửa |
| Check giới hạn bảo tàng/ngày | Không có | Cần thêm | ❌ Cần thêm |

### **2. Points System Logic**

#### **2.1 Points Flow:**
```
Check-in → PendingPoints (hiển thị "chờ duyệt")
    ↓
Admin Review
    ↓
Approved → ActualPoints + User.Score ++ 
Denied → ActualPoints = 0, User.Score không đổi
```

#### **2.2 Display Logic:**
- **none/pending:** `"+10 điểm (chờ duyệt)"`
- **approved:** `"+10 điểm"`  
- **denied:** `"Không được cộng điểm" + DeniedReason`

### **3. Status Transition Rules**

```
none → approved ✅ (cộng điểm)
none → denied ✅ (không cộng điểm)  
approved → denied ✅ (trừ điểm đã cộng)
denied → approved ✅ (cộng điểm)
```

**Lưu ý:** Một khi admin đã xử lý (approved/denied), không thể quay về trạng thái `none` (chờ duyệt) vì đó là trạng thái chờ xử lý ban đầu.

---

## 💻 **PHẦN C: CODE IMPLEMENTATION**

### **1. Files cần thay đổi:**

#### **1.1 Backend PHP Files:**
- `checkCheckinEligibility.php` - Thêm validation 2 bảo tàng/ngày
- `basicCheckin.php` - Cập nhật PendingPoints logic  
- `getRecentCheckins.php` - Hiển thị ApprovalStatus
- `getCheckinDetail.php` - Hiển thị status detail + DeniedReason

#### **1.2 Frontend Files:**
- `checkin.html` - Cập nhật hiển thị status
- `checkinDetail.html` - Hiển thị ApprovalStatus + DeniedReason

#### **1.3 Database Files:**
- `checkin_database_setup.sql` - Update với cấu trúc mới

### **2. Key Implementation Points:**

#### **2.1 checkCheckinEligibility.php - Thêm validation:**
```php
// THÊM: Kiểm tra tổng số bảo tàng đã check-in hôm nay
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT MuseumID) as museums_today 
    FROM checkins 
    WHERE UserToken = ? AND DATE(CheckinTime) = ?
");
$stmt->bind_param("ss", $userToken, $today);
$stmt->execute();
$result = $stmt->get_result();
$museumsToday = $result->fetch_assoc()['museums_today'];

// Kiểm tra giới hạn 2 bảo tàng/ngày
if ($museumsToday >= 2) {
    $canCheckin = false;
    $message = 'Đã đạt giới hạn 2 bảo tàng/ngày';
}
```

#### **2.2 basicCheckin.php - PendingPoints logic:**
```php
// Tính PendingPoints thay vì cộng điểm ngay
$pendingPoints = 10; // Base points
if ($isFirstCheckinToday) {
    $pendingPoints += 5; // Bonus cho lần đầu trong ngày
}

// Lưu vào database với status = 'none'
$stmt = $conn->prepare("
    UPDATE checkins 
    SET PendingPoints = ?, ApprovalStatus = 'none' 
    WHERE CheckinID = ?
");
$stmt->bind_param("ii", $pendingPoints, $checkinId);
$stmt->execute();

// KHÔNG cộng vào User.Score ngay (chờ approve)
```

#### **2.3 Frontend display logic:**
```javascript
function getStatusDisplay(checkin) {
    switch(checkin.ApprovalStatus) {
        case 'none': 
            return { 
                text: 'Đang chờ duyệt', 
                color: 'orange',
                points: `+${checkin.PendingPoints} điểm (chờ duyệt)`
            };
        case 'approved': 
            return { 
                text: 'Đã duyệt', 
                color: 'green',
                points: `+${checkin.ActualPoints} điểm`
            };
        case 'denied': 
            return { 
                text: 'Bị từ chối', 
                color: 'red',
                reason: checkin.DeniedReason,
                points: 'Không được cộng điểm'
            };
    }
}
```

---

## ✅ **PHẦN D: VALIDATION & SECURITY**

### **1. Data Integrity Checks:**

```sql
-- Function kiểm tra tính nhất quán points
DELIMITER $$
CREATE FUNCTION ValidateUserPoints(user_token VARCHAR(64)) 
RETURNS BOOLEAN
READS SQL DATA
BEGIN
    DECLARE total_actual_points INT;
    DECLARE user_score INT;
    
    SELECT COALESCE(SUM(ActualPoints), 0) INTO total_actual_points
    FROM checkins 
    WHERE UserToken = user_token AND ApprovalStatus = 'approved';
    
    SELECT score INTO user_score 
    FROM users 
    WHERE UserToken = user_token;
    
    RETURN (total_actual_points = user_score);
END$$
DELIMITER ;
```

### **2. Transaction Safety:**

```php
// Đảm bảo atomic operations khi admin approve/deny
BEGIN;
try {
    // Validate status transition
    $validTransitions = [
        'none' => ['approved', 'denied'],
        'approved' => ['denied'],
        'denied' => ['approved']
    ];
    
    if (!in_array($newStatus, $validTransitions[$oldStatus])) {
        throw new Exception("Invalid status transition from {$oldStatus} to {$newStatus}");
    }
    
    // Update checkin status
    $stmt = $conn->prepare("
        UPDATE checkins 
        SET ApprovalStatus = ?, ActualPoints = ?, ProcessedAt = NOW(), ProcessedBy = ?
        WHERE CheckinID = ?
    ");
    $stmt->bind_param("sisi", $newStatus, $actualPoints, $adminToken, $checkinId);
    $stmt->execute();
    
    // Update user score based on transition
    if ($newStatus === 'approved') {
        // Cộng điểm (từ none hoặc denied → approved)
        $stmt = $conn->prepare("UPDATE users SET score = score + ? WHERE UserToken = ?");
        $stmt->bind_param("is", $actualPoints, $userToken);
        $stmt->execute();
    } elseif ($oldStatus === 'approved' && $newStatus === 'denied') {
        // Trừ điểm (từ approved → denied)
        $stmt = $conn->prepare("UPDATE users SET score = score - ? WHERE UserToken = ?");
        $stmt->bind_param("is", $actualPoints, $userToken);
        $stmt->execute();
    }
    
    // Insert history
    $stmt = $conn->prepare("
        INSERT INTO checkin_status_history 
        (CheckinID, OldStatus, NewStatus, AdminToken, Reason) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $checkinId, $oldStatus, $newStatus, $adminToken, $reason);
    $stmt->execute();
    
    COMMIT;
} catch (Exception $e) {
    ROLLBACK;
    throw $e;
}
```

---

## 📅 **IMPLEMENTATION TIMELINE**

### **Phase 1: Database Changes** ⏱️ 1-2 days
- [ ] Update `checkin_database_setup.sql`
- [ ] Create migration scripts
- [ ] Add new tables and fields
- [ ] Update existing data

### **Phase 2: Backend API Updates** ⏱️ 2-3 days  
- [ ] Update `checkCheckinEligibility.php`
- [ ] Modify `basicCheckin.php`  
- [ ] Update `getRecentCheckins.php`
- [ ] Update `getCheckinDetail.php`

### **Phase 3: Frontend Updates** ⏱️ 1-2 days
- [ ] Update `checkin.html` status display
- [ ] Update `checkinDetail.html` 
- [ ] Test UI/UX flow

### **Phase 4: Testing & Validation** ⏱️ 1-2 days
- [ ] Test all validation rules
- [ ] Test points system
- [ ] Test status transitions
- [ ] Performance testing

---

## ⚠️ **IMPORTANT NOTES**

### **1. Backwards Compatibility:**
- Giữ nguyên field `Points` cũ để không break existing code
- Migration data cẩn thận từ `Status` → `Caption`

### **2. Admin Features:**
- Team khác đang làm admin approval workflow
- Chỉ chuẩn bị database structure + API endpoints

### **3. Future Features (Skip for now):**
- ✅ **Point adjustment:** Có structure nhưng chưa implement
- ✅ **Notification:** Có thể gửi email nhưng chưa làm  
- ✅ **Time limit:** Có structure nhưng chưa enforce

### **4. Testing Data:**
- Sử dụng các bảo tàng test ở Hà Nội trong `checkin_database_setup.sql`
- Test với rules mới: 1 lần/ngày, 7 ngày chờ, 2 bảo tàng/ngày

---

## 🔗 **RELATED FILES**

- `checkin_database_setup.sql` - Database structure
- `checkCheckinEligibility.php` - Validation logic  
- `basicCheckin.php` - Check-in processing
- `checkin.html` - Main UI
- `README.md` - Current documentation

---

**📝 End of Document**  
*Last updated: 30/09/2025*