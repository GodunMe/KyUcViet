# Hướng dẫn gitignore

File `.gitignore` được cấu hình để loại bỏ các file và thư mục không cần thiết khi push code lên remote repository. Dưới đây là một số quy tắc quan trọng:

## Thư mục uploads

Thư mục `uploads/` chứa các file người dùng tải lên (ảnh của bảo tàng, hiện vật và check-ins). Cấu trúc thư mục được giữ lại trong repository nhờ các file `.gitkeep`, nhưng nội dung thực tế (ảnh tải lên) sẽ không được theo dõi.

```
uploads/
  ├── artifacts/
  │   └── .gitkeep
  ├── museums/
  │   └── .gitkeep
  ├── checkins/
  │   └── .gitkeep
  └── .gitkeep
```

## Các file được loại trừ

1. **File cấu hình cá nhân**: `.local.php`, `local-config.php`
2. **File tạm thời**: `.tmp`, `.temp`, `.bak`, `.swp`
3. **Log files**: `.log` và thư mục `logs/`
4. **IDE & editor**: `.vscode/`, `.idea/`
5. **File hệ thống**: `.DS_Store`, `Thumbs.db`
6. **Dependencies**: `node_modules/`, `vendor/` (nếu sử dụng)

## Thêm quy tắc mới

Nếu bạn cần thêm quy tắc mới vào `.gitignore`:

1. Mở file `.gitignore`
2. Thêm quy tắc mới (mỗi quy tắc một dòng)
3. Lưu file và commit

## Lưu ý quan trọng

- **Không thêm file `db.php`** vào `.gitignore` vì file này đã được theo dõi trong repository và chứa cấu trúc kết nối cơ sở dữ liệu chung cho dự án.
- Nếu file đã được theo dõi trong Git, thêm vào `.gitignore` không loại bỏ nó. Bạn cần chạy `git rm --cached <file>` để ngừng theo dõi file đó.
- Sử dụng file cấu hình riêng (ví dụ: `db.local.php`) cho các cài đặt cụ thể của môi trường cá nhân.