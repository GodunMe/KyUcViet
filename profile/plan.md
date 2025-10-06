# Kế hoạch phân biệt vai trò người dùng trong profile

## Tổng quan

Chúng ta sẽ triển khai hệ thống phân biệt vai trò người dùng sử dụng **Mẫu 3: Viền và màu sắc** kết hợp với **Phương án màu B (Material)**. Mỗi vai trò sẽ được thể hiện qua màu sắc khác nhau cả ở tên người dùng và viền avatar.

## Phương án màu sắc

| Vai trò | Màu sắc | Mã màu | Ứng dụng |
|---------|---------|--------|----------|
| Admin | Đỏ material | #F44336 | Tên người dùng + Viền avatar |
| CustomerPre | Vàng amber | #FFC107 | Tên người dùng + Viền avatar |
| Customer | Xanh material | #2196F3 | Tên người dùng + Viền avatar |

## Các bước triển khai

### 1. Cập nhật API Backend (getUserInfo.php)
- Thêm thông tin `role` vào dữ liệu người dùng trả về
- Đảm bảo API truy vấn và trả về đúng vai trò từ cơ sở dữ liệu

### 2. Cập nhật CSS
- Thêm classes cho từng vai trò người dùng
- Định nghĩa màu sắc cho tên người dùng theo vai trò
- Định nghĩa viền avatar theo vai trò
- Đảm bảo độ dày viền phù hợp (2-3px)

### 3. Sửa đổi JavaScript
- Cập nhật hàm `showUserLoggedInState()` để thêm class tương ứng với vai trò
- Áp dụng class cho cả tên người dùng và avatar
- Đảm bảo xử lý trường hợp không có thông tin vai trò (mặc định là Customer)

### 4. Kiểm tra khả năng hiển thị
- Đảm bảo độ tương phản đủ cao giữa text và background
- Kiểm tra trên các kích thước màn hình khác nhau
- Kiểm tra trên dark mode và light mode

## Mã CSS dự kiến

```css
/* Định dạng màu tên người dùng theo vai trò */
.user-name.admin {
    color: #F44336; /* Đỏ material */
    font-weight: bold;
}

.user-name.customerpre {
    color: #FFC107; /* Vàng amber */
    font-weight: bold;
}

.user-name.customer {
    color: #2196F3; /* Xanh material */
}

/* Định dạng viền avatar theo vai trò */
.profile-avatar.admin img, 
.user-icon.admin {
    border: 3px solid #F44336;
    box-shadow: 0 0 8px rgba(244, 67, 54, 0.5);
}

.profile-avatar.customerpre img,
.user-icon.customerpre {
    border: 3px solid #FFC107;
    box-shadow: 0 0 8px rgba(255, 193, 7, 0.5);
}

.profile-avatar.customer img,
.user-icon.customer {
    border: 2px solid #2196F3;
    box-shadow: 0 0 8px rgba(33, 150, 243, 0.3);
}
```

## Mã JavaScript dự kiến

```javascript
function showUserLoggedInState(userData) {
    // Code hiện tại...
    
    // Xử lý vai trò
    const nameElement = document.getElementById('userName');
    const avatarElement = document.getElementById('userAvatar');
    const profileAvatar = document.querySelector('.profile-avatar');
    
    // Xóa tất cả class vai trò cũ
    nameElement.classList.remove('admin', 'customerpre', 'customer');
    avatarElement.classList.remove('admin', 'customerpre', 'customer');
    if (profileAvatar) {
        profileAvatar.classList.remove('admin', 'customerpre', 'customer');
    }
    
    // Thêm class mới dựa trên vai trò (mặc định là customer)
    const role = (userData.role || 'customer').toLowerCase();
    nameElement.classList.add(role);
    avatarElement.classList.add(role);
    if (profileAvatar) {
        profileAvatar.classList.add(role);
    }
    
    // Code tiếp theo...
}
```

## Các API cần cập nhật

1. **getUserInfo.php**
   - Thêm trường `role` vào kết quả JSON

```php
// Ví dụ đơn giản:
$userData = array(
    'loggedIn' => true,
    'userToken' => $row['UserToken'],
    'username' => $row['Username'],
    'score' => $row['Score'],
    'avatarRelative' => $row['avatar'],
    'role' => $row['Role'] // Thêm thông tin vai trò
);
```

## Kiểm thử

1. Đăng nhập với tài khoản Admin (Tester2)
   - Kiểm tra màu đỏ tên người dùng
   - Kiểm tra viền đỏ avatar

2. Đăng nhập với tài khoản CustomerPre (Pham Xuân Dương)
   - Kiểm tra màu vàng amber tên người dùng
   - Kiểm tra viền vàng amber avatar

3. Đăng nhập với tài khoản Customer (Tester1)
   - Kiểm tra màu xanh tên người dùng
   - Kiểm tra viền xanh avatar

## Kế hoạch triển khai

1. Thêm CSS vào style.css
2. Cập nhật getUserInfo.php để trả về thông tin vai trò
3. Sửa đổi profile.html để áp dụng class dựa trên vai trò
4. Kiểm thử trên các thiết bị và vai trò khác nhau