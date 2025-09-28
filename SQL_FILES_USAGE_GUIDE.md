# HƯỚNG DẪN SỬ DỤNG CÁC FILE SQL

## Tổng quan
Sau khi kiểm tra và gộp, hiện tại có các file SQL sau:

### 1. `exe201(data).sql` - ⚡ FILE GỐC, KHÔNG CHỈNH SỬA
- **Mục đích**: Database chính với dữ liệu có sẵn
- **Nội dung**: Các bảng chính (users, museum, artifact, etc.)
- **Trạng thái**: Được giữ nguyên theo yêu cầu

### 2. `checkin_database_setup.sql` - 🚀 FILE CHÍNH ĐỂ CHẠY
- **Mục đích**: Thiết lập hoàn chỉnh chức năng check-in
- **Được gộp từ**: `sample_museums.sql` + `update_database_after_merge.sql`
- **Nội dung**:
  - Tạo các bảng check-in (checkins, checkin_photos, museum_checkin_rules, daily_checkin_limits)
  - Thêm dữ liệu bảo tàng mẫu để test
  - Thiết lập quy tắc check-in mặc định

### 3. `backup_original_sql_files.sql` - 📋 FILE BACKUP
- **Mục đích**: Lưu trữ nội dung 2 file SQL gốc đã gộp
- **Trạng thái**: Chỉ để tham khảo, không chạy

### 4. `exe201.sql` - 📊 FILE CẤU TRÚC CƠ BẢN
- **Mục đích**: Cấu trúc database cơ bản (không có dữ liệu)

## Cách sử dụng

### Bước 1: Thiết lập database ban đầu
```sql
-- Import database chính
mysql -u root -p exe201 < exe201(data).sql
```

### Bước 2: Thêm chức năng check-in
```sql
-- Import chức năng check-in
mysql -u root -p exe201 < checkin_database_setup.sql
```

### Kết quả sau khi chạy:
- ✅ Database đã có đầy đủ chức năng check-in
- ✅ Có sẵn dữ liệu bảo tàng để test
- ✅ Quy tắc check-in đã được thiết lập

## Cấu trúc bảng mới được tạo:

### `checkins` - Bảng chính lưu thông tin check-in
- CheckinID, UserToken, MuseumID, Latitude, Longitude
- Status, CheckinTime, Points, Privacy

### `checkin_photos` - Bảng lưu ảnh check-in
- PhotoID, CheckinID, PhotoPath, Caption, UploadOrder

### `museum_checkin_rules` - Quy tắc check-in theo bảo tàng
- MuseumID, MaxCheckinPerDay, MinTimeBetweenCheckins, DaysBetweenRevisit

### `daily_checkin_limits` - Giới hạn check-in hàng ngày
- UserToken, CheckinDate, MuseumsVisitedCount, LastResetTime

## Dữ liệu test được thêm:
- 13 bảo tàng thực tế tại Việt Nam (Hà Nội, TP.HCM, Đà Nẵng, Huế, Hội An, Quảng Ninh)
- 3 bảo tàng test gần Hà Nội (để test tính năng check-in)

## Ghi chú:
- Tất cả bảo tàng mới sẽ có quy tắc mặc định: 2 lần check-in/ngày, cách nhau 30 phút, 3 ngày mới được check-in lại
- Có thể xóa dữ liệu test: `DELETE FROM museum WHERE MuseumName LIKE 'Bảo tàng Test%';`