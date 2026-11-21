# NhomNhom PHP chạy bằng XAMPP

Đây là ứng dụng PHP thuần chạy trên Apache, dùng PDO MySQL/MariaDB hoặc SQLite
và JavaScript trình duyệt. Không cần Node.js, npm hay framework frontend.

## Cài đặt

1. Cài XAMPP có PHP 8.1 trở lên và bật **Apache**.
2. Chép toàn bộ thư mục dự án vào `C:\xampp\htdocs\nhomnhom`, giữ nguyên thư mục `data`.
3. Trong `C:\xampp\php\php.ini`, bật `pdo_mysql` (hoặc `pdo_sqlite` nếu dùng SQLite), rồi:

```ini
extension=pdo_mysql
; extension=pdo_sqlite
; extension=sqlite3
```

4. Khởi động lại Apache và mở `http://localhost/nhomnhom/`.

## Cấu hình MySQL

File import đầy đủ nằm ở `database/mysql/nhomnhom.sql`. Import file này bằng
phpMyAdmin sau khi bật MySQL trong XAMPP. Chi tiết cấu hình PDO nằm trong
`database/mysql/README.md`.

## Cấu hình SQLite dự phòng

Mặc định ứng dụng đọc `data/nhomnhom.sqlite`. Nếu muốn đặt CSDL ở vị trí khác,
thêm biến môi trường Apache trong `C:\xampp\apache\conf\httpd.conf` hoặc VirtualHost:

```apache
SetEnv NHOMNHOM_DB_PATH "C:/du-lieu/nhomnhom.sqlite"
```

Thư mục `data` và `src` bị chặn truy cập trực tiếp bởi `.htaccess`. CSDL
mẫu không chứa thông tin đăng nhập thật.

## API và kiểm thử

API tìm kiếm là `GET /nhomnhom/api/restaurants.php?q=bun%20bo&page=1&pageSize=12`.
API trả JSON dạng `{ success, data, pagination, error }`, dùng prepared statements
và giới hạn từ khóa 80 ký tự, trang 1–10000, page size 1–100.

API khám phá món là `GET /nhomnhom/api/discover.php?limit=8&offset=0`. Giao diện
MVP có nút **Bỏ qua** và **Thích món này**; lựa chọn được lưu ở trình duyệt để
chuẩn bị cho hồ sơ khẩu vị cá nhân ở giai đoạn tiếp theo.

Các endpoint PHP đã có:

- `GET/POST /api/profile.php`: hồ sơ khẩu vị và CSRF token.
- `POST /api/taste.php`: ghi thích/bỏ qua và cập nhật điểm Elo món.
- `GET/POST /api/groups.php`: tạo, tham gia và bình chọn nhóm.
- `GET/POST /api/bookings.php`: tạo và xem yêu cầu đặt bàn.
- `GET /api/insights.php`: bảng xu hướng lượt thích và đặt bàn.

Chạy smoke test bằng PHP của XAMPP:

```powershell
C:\xampp\php\php.exe tests/php/search_test.php
```

Nút **Chỉ đường** mở Google Maps với phương tiện đang chọn. Địa điểm mẫu
(`is_demo`) được gắn nhãn chưa xác minh và không tạo đường dẫn thực tế.
