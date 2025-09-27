## Hướng dẫn cập nhật cấu trúc cơ sở dữ liệu

Dự án này sử dụng hệ thống migration để quản lý các thay đổi cấu trúc cơ sở dữ liệu. Điều này đảm bảo tất cả các thành viên trong nhóm và các môi trường (phát triển, kiểm thử, sản phẩm) đều có cùng một cấu trúc cơ sở dữ liệu.

### Chạy Migrations

1. Mở terminal hoặc command prompt
2. Di chuyển đến thư mục gốc của dự án (htdocs)
3. Chạy lệnh sau:

```
php migrations/migrate.php
```

### Tạo Migration mới

Khi bạn cần thay đổi cấu trúc cơ sở dữ liệu (thêm bảng, sửa bảng, thêm cột, vv), hãy tạo một migration mới:

1. Tạo file SQL mới trong thư mục `migrations` với định dạng tên: `00X_tên_migration.sql` (X là số thứ tự tiếp theo)
2. Viết các câu lệnh SQL cần thiết trong file
3. Commit file migration vào git repository để chia sẻ với các thành viên khác

### Quy tắc quan trọng

- **KHÔNG bao giờ** sửa các migration đã được commit và đẩy lên repository. Thay vào đó, hãy tạo migration mới để sửa lỗi hoặc điều chỉnh.
- Luôn sử dụng `IF NOT EXISTS` khi tạo bảng để tránh lỗi.
- Sử dụng lệnh `php migrations/migrate.php` sau khi pull code mới từ repository về.
- Khi merge code, nhớ thực hiện migration để đồng bộ cấu trúc cơ sở dữ liệu.

### Kiểm tra trạng thái migration

Bạn có thể kiểm tra xem migration nào đã được thực thi bằng cách truy vấn bảng `migrations` trong cơ sở dữ liệu:

```sql
SELECT * FROM migrations ORDER BY executed_at;
```

### Tạo migration rollback (khôi phục)

Nếu cần, bạn có thể tạo migration để khôi phục các thay đổi không mong muốn:

```sql
-- Ví dụ về migration khôi phục
DROP TABLE IF EXISTS checkin_photos;
DROP TABLE IF EXISTS checkin_likes;
DROP TABLE IF EXISTS checkin_comments;
DROP TABLE IF EXISTS checkins;
```

### Tạo thư mục uploads

Migration tự động sẽ tạo thư mục `uploads/checkins` để lưu trữ ảnh check-in. Nếu gặp lỗi, bạn có thể tạo thủ công:

```
mkdir -p uploads/checkins
chmod 777 uploads/checkins
```