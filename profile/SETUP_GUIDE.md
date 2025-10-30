# 🚀 Hướng Dẫn Setup Hệ Thống Ranking Vietnamese Memories

## 📋 Checklist Triển Khai

### Phase 1: Database Setup ✅

1. **Chạy SQL setup file**
   ```bash
   # Import vào MySQL/MariaDB
   mysql -u your_username -p exe201 < profile/ranking_system_setup.sql
   ```
   
   Hoặc từ phpMyAdmin:
   - Mở phpMyAdmin
   - Chọn database `exe201`
   - Vào tab SQL
   - Copy nội dung file `ranking_system_setup.sql`
   - Chạy script

2. **Kiểm tra kết quả**
   - Bảng `user_ranks`: 8 ranks từ Sắt đến Kim Cương
   - Bảng `achievements`: 16 achievements mặc định
   - Bảng `user_achievements`: Theo dõi achievements của user
   - Bảng `user_activities`: Log hoạt động
   - Cột mới trong `users`: CurrentRank, TotalCheckins, etc.

### Phase 2: API Integration ✅

1. **APIs đã tạo:**
   - `profile/getUserProfileComplete.php`: Lấy profile đầy đủ với ranking
   - `profile/updateUserScore.php`: Cập nhật điểm + kiểm tra rank up
   - `profile/getUserAchievements.php`: Lấy danh sách achievements

2. **Profile page đã update:**
   - Sử dụng API mới thay vì placeholder data
   - Hiển thị rank badge với màu sắc
   - Real-time stats cards
   - Dynamic achievements và activities

### Phase 3: Tích Hợp Vào Hệ Thống Hiện Tại

#### 🎯 Checkin Integration

**File cần sửa:** `checkin/basicCheckin.php`

Thêm sau khi checkin thành công:
```php
// After successful checkin, award points
$updateScoreData = [
    'actionType' => 'checkin',
    'basePoints' => 25, // Base points for checkin
    'museumId' => $museumId,
    'description' => 'Check-in tại ' . $museumName
];

// Call ranking API (you can do this via cURL or include the logic directly)
$rankingResponse = callUpdateScoreAPI($userToken, $updateScoreData);
```

#### 🧠 Quiz Integration

**File cần sửa:** `doquiz.php`

Thêm sau khi hoàn thành quiz:
```php
// After quiz completion, award points based on score
$quizPoints = 0;
if ($correctAnswers >= 5) $quizPoints = 50;
elseif ($correctAnswers >= 4) $quizPoints = 30;
elseif ($correctAnswers >= 3) $quizPoints = 15;

if ($quizPoints > 0) {
    $updateScoreData = [
        'actionType' => 'quiz',
        'basePoints' => $quizPoints,
        'quizScore' => $correctAnswers,
        'museumId' => $museumId,
        'description' => "Hoàn thành quiz ({$correctAnswers}/5 đúng)"
    ];
    
    $rankingResponse = callUpdateScoreAPI($userToken, $updateScoreData);
}
```

#### 🪙 Lucky Coin Integration

**File cần sửa:** `lucky_coin/pickupCoin.php`

Thêm sau khi pickup coin thành công:
```php
// Award points for coin pickup
$updateScoreData = [
    'actionType' => 'coin_pickup',
    'basePoints' => 5, // Small points for coin pickup
    'description' => 'Nhặt được Lucky Coin'
];

$rankingResponse = callUpdateScoreAPI($userToken, $updateScoreData);
```

### Phase 4: Helper Functions

Tạo file `profile/rankingHelper.php`:
```php
<?php
function callUpdateScoreAPI($userToken, $scoreData) {
    $_SESSION['UserToken'] = $userToken; // Ensure session is set
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, '/profile/updateUserScore.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($scoreData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function getUserRankInfo($userToken) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, '/profile/getUserProfileComplete.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['success'] ? $data['profile']['rank'] : null;
}
?>
```

### Phase 5: Leaderboard Enhancement

**File cần sửa:** `leaderboard/leaderboard.html`

Sử dụng view `leaderboard_view` thay vì query thủ công:
```php
// In leaderboard PHP file
$sql = "SELECT * FROM leaderboard_view LIMIT 50";
```

### Phase 6: Navigation Updates

**Thêm rank indicator vào navigation:**
```css
.nav-rank-indicator {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--rank-color);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
```

## 🎮 Game Mechanics

### Điểm Số & Rank System
- **Sắt (0-99)**: 1.0x multiplier
- **Đồng (100-299)**: 1.05x multiplier
- **Bạc (300-699)**: 1.10x multiplier
- **Vàng (700-1499)**: 1.15x multiplier
- **Bạch Kim (1500-2999)**: 1.20x multiplier
- **Lục Bảo (3000-5999)**: 1.25x multiplier
- **Hồng Ngọc (6000-11999)**: 1.30x multiplier
- **Kim Cương (12000+)**: 1.35x multiplier

### Achievement Categories
1. **Check-in Achievements**: 1, 5, 15, 30, 50 check-ins
2. **Quiz Achievements**: Perfect scores, quiz completion
3. **Exploration**: Different museums visited
4. **Streak**: Consecutive days
5. **Rank**: Achieving different ranks

## 🔧 Testing & Debugging

### Test Scenarios
1. **New User**: Phải ở rank Sắt, 0 điểm
2. **Checkin**: +25 điểm base, bonus theo rank
3. **Quiz Perfect**: +50 điểm base, bonus theo rank
4. **Rank Up**: Tự động khi đủ điểm
5. **Achievements**: Unlock khi đạt requirements

### Debug Commands
```sql
-- Check user rank status
SELECT u.Username, u.Score, ur.RankNameVi, ur.BonusMultiplier 
FROM users u 
LEFT JOIN user_ranks ur ON u.CurrentRank = ur.RankID;

-- Check achievements earned
SELECT u.Username, a.NameVi, ua.EarnedDate
FROM user_achievements ua
JOIN users u ON ua.UserToken = u.UserToken
JOIN achievements a ON ua.AchievementID = a.AchievementID
ORDER BY ua.EarnedDate DESC;

-- Check recent activities
SELECT u.Username, act.ActivityType, act.Title, act.Points, act.CreatedAt
FROM user_activities act
JOIN users u ON act.UserToken = u.UserToken
ORDER BY act.CreatedAt DESC
LIMIT 20;
```

## 🚨 Lưu Ý Quan Trọng

1. **Backup Database**: Luôn backup trước khi chạy migration
2. **Test trên Dev**: Test kỹ trước khi deploy production
3. **Performance**: Các view và index đã được tối ưu
4. **Compatibility**: API mới tương thích với code cũ
5. **Migration**: User hiện tại sẽ được assign rank dựa trên điểm hiện có

## 🎯 Next Steps

1. Chạy database setup
2. Test profile page với ranking system
3. Tích hợp vào checkin & quiz
4. Enhance leaderboard
5. Add rank notifications & badges
6. Mobile optimization

## 📞 Support

Nếu gặp vấn đề:
1. Check console log
2. Verify database tables created
3. Test APIs individually  
4. Check session management
5. Verify file permissions

**Hệ thống ranking sẽ transform Vietnamese Memories thành một gamified experience hoàn toàn mới! 🎮🏆**