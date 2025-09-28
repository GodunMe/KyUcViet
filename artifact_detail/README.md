# Hướng dẫn tạo trang chi tiết hiện vật

## 🆕 KHUYÊN DÙNG: Sử dụng Text Converter Tool
**Sử dụng tool `/text_converter.html` để tạo file HTML tự động thay vì tạo thủ công!**

## 1. Cấu trúc thư mục
```
/artifact_detail/
├── template.html (file mẫu - không còn cần thiết)
├── 1.html (artifact ID = 1)
├── 2.html (artifact ID = 2)
└── ...
```

## 2. Cách tạo file HTML cho hiện vật

### 🚀 Phương pháp KHUYÊN DÙNG: Text Converter
1. Mở `http://localhost/text_converter.html`
2. Nhập thông tin hiện vật (tên, ID, museum ID)
3. Nhập nội dung với format đặc biệt:
   - `**text**` → Text in đậm
   - `IMG:/path/image.jpg` → Ảnh
   - `CAPTION:chú thích` → Chú thích ảnh
4. Click "Chuyển đổi" và tải file HTML

### 📝 Phương pháp thủ công (cũ):
1. Copy file `template.html` và đổi tên thành `{ArtifactID}.html`
2. **Đổi title**: `<title>Tên Hiện Vật - Ký Ức Việt</title>`
3. **Đổi tên hiện vật**: `<h1 class="artifact-title">Tên Hiện Vật</h1>`
4. **Thay MUSEUM_ID**: Trong script `goBack()`, thay `MUSEUM_ID` bằng ID thực của bảo tàng

### Bước 3: Format nội dung

#### Text in đậm:
```html
<div class="bold-text">Tiêu đề in đậm</div>
```

#### Đoạn văn thường:
```html
<p>Nội dung đoạn văn...</p>
```

#### Hình ảnh với chú thích:
```html
<div class="artifact-image">
    <img src="/uploads/artifacts/image.jpg" alt="Mô tả ảnh">
    <div class="image-caption">Chú thích ảnh - in nghiêng, nhỏ hơn</div>
</div>
```

## 3. Cập nhật database

Thêm vào cột `artifact_detail` trong bảng `artifact`:
```sql
UPDATE artifact 
SET artifact_detail = '/artifact_detail/1.html' 
WHERE ArtifactID = 1;
```

## 4. Ví dụ hoàn chỉnh

File: `/artifact_detail/1.html`
```html
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bình Gốm Cổ - Ký Ức Việt</title>
    <link rel="stylesheet" href="../style.css">
    <!-- CSS styles giữ nguyên từ template -->
</head>
<body>
    <button class="back-button" onclick="goBack()">← Quay lại bảo tàng</button>
    
    <div class="artifact-detail-content">
        <h1 class="artifact-title">Bình Gốm Cổ</h1>
        
        <div class="artifact-content">
            <div class="bold-text">Giới thiệu</div>
            <p>Bình gốm cổ này được tìm thấy tại di chỉ khảo cổ học...</p>
            
            <div class="artifact-image">
                <img src="/uploads/artifacts/binh-gom-co.jpg" alt="Bình gốm cổ">
                <div class="image-caption">Bình gốm cổ từ thế kỷ 15</div>
            </div>
            
            <div class="bold-text">Đặc điểm kỹ thuật</div>
            <p>Chiều cao: 30cm, đường kính: 15cm...</p>
            
            <div class="bold-text">Giá trị lịch sử</div>
            <p>Hiện vật này thể hiện trình độ nghề gốm...</p>
        </div>
    </div>
    
    <script>
        function goBack() {
            // Simply go back to museum page
            // The museum position was already saved when entering artifact detail
            window.location.href = '/museum.html?id=3';
        }
    </script>
</body>
</html>
```

## 5. ✨ Tính năng mới: Nhớ vị trí scroll
- **Nút "Quay lại bảo tàng"** sẽ nhớ vị trí scroll trước đó  
- Người dùng không cần lướt lại đến cuối trang
- JavaScript tự động lưu và khôi phục scroll position
- **Text Converter** đã tích hợp sẵn chức năng này

## 6. Cập nhật Database
Sau khi tạo file HTML, cần cập nhật database:
```sql
UPDATE artifacts 
SET artifact_detail = '/artifact_detail/1.html' 
WHERE ArtifactID = 1;
```

## 7. ⚠️ Lưu ý quan trọng  
- File `artifact_detail.html` (fallback cũ) **đã bị xóa**
- Tất cả hiện vật bây giờ **phải có file HTML riêng**
- Nếu hiện vật chưa có HTML, sẽ hiện thông báo yêu cầu tạo file
- File HTML phải đặt trong thư mục `/artifact_detail/`
- Tên file phải trùng với ArtifactID (ví dụ: `1.html` cho artifact ID = 1)
- CSS classes đã được định nghĩa sẵn trong template  
- Hình ảnh nên đặt trong `/uploads/artifacts/`