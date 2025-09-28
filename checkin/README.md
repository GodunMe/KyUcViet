# Chức năng Check-in

Folder này chứa tất cả file liên quan đến chức năng check-in bảo tàng.

## Cấu trúc file:

### Frontend
- `checkin.html` - Giao diện check-in chính với quy trình 4 bước:
  1. Chọn bảo tàng (sử dụng `../getMuseums.php`)
  2. Xác nhận lựa chọn  
  3. Kiểm tra vị trí (JavaScript, phải trong vòng 200m)
  4. Upload ảnh (bắt buộc, xử lý trong `basicCheckin.php`)

### Backend APIs
- `basicCheckin.php` - API xử lý check-in với upload ảnh tích hợp
- `getRecentCheckins.php` - API lấy lịch sử check-in gần đây

### APIs được sử dụng từ thư mục gốc:
- `../getMuseums.php` - Lấy danh sách tất cả bảo tàng
- `../getUserInfo.php` - Lấy thông tin người dùng

## Quy trình Check-in:

1. **Chọn bảo tàng**: Hiển thị danh sách bảo tàng gần nhất dựa trên GPS
2. **Xác nhận**: Người dùng xác nhận chọn bảo tàng cụ thể
3. **Kiểm tra vị trí**: Phải ở trong vòng 200m từ bảo tàng mới được check-in
4. **Upload ảnh**: Bắt buộc ít nhất 1 ảnh để hoàn thành check-in
5. **Hoàn thành**: Lưu check-in + ảnh + cộng 10 điểm

## Tính năng:
- ✅ Xác nhận chọn bảo tàng rõ ràng
- ✅ Kiểm tra khoảng cách thực tế GPS
- ✅ Upload và lưu nhiều ảnh
- ✅ Step indicator hiển thị tiến trình
- ✅ Xử lý lỗi và validation đầy đủ
- ✅ Responsive design cho mobile

## Truy cập:
- URL: `http://localhost:8000/checkin/checkin.html`
- Từ trang chủ: Nhấn nút "Check-in" trên bottom navigation