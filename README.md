# NhomNhom PHP

NhomNhom là ứng dụng PHP thuần để tìm món ăn và mở Google Maps. Ứng dụng chạy
trực tiếp trên Apache/XAMPP, dùng PDO MySQL/MariaDB hoặc SQLite và JavaScript trình duyệt; không cần
Node.js, npm hoặc framework frontend.

## Chạy nhanh trên XAMPP

1. Chép thư mục này vào `C:\xampp\htdocs\nhomnhom`, giữ nguyên `data/nhomnhom.sqlite`.
2. Trong `C:\xampp\php\php.ini`, bật `extension=pdo_sqlite` và `extension=sqlite3`.
3. Bật Apache trong XAMPP.
4. Mở `http://localhost/nhomnhom/`.

Hướng dẫn chi tiết và cấu hình đường dẫn CSDL ở [README-XAMPP.md](README-XAMPP.md).
Nếu dùng MySQL, import [database/mysql/nhomnhom.sql](database/mysql/nhomnhom.sql)
và xem [database/mysql/README.md](database/mysql/README.md).

API tìm kiếm: `GET /nhomnhom/api/restaurants.php?q=bun%20bo&page=1&pageSize=12`.
Khám phá món cấp món: `GET /nhomnhom/api/discover.php?limit=8&offset=0`; giao diện
cho phép bỏ qua/thích món và lưu tối đa 50 lựa chọn trên trình duyệt.
Các API PHP bổ sung gồm `profile.php`, `taste.php`, `groups.php`, `bookings.php`
và `insights.php` cho hồ sơ khẩu vị, Elo món, nhóm chọn món, đặt bàn và bảng xu
hướng. Các thao tác ghi dùng session cookie, CSRF token và prepared statements.
Kiểm thử PHP: `C:\xampp\php\php.exe tests/php/search_test.php`.
