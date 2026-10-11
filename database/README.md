# Cơ sở dữ liệu NhomNhom

Ứng dụng chạy bằng PHP 8.1+, Apache/XAMPP và PDO MySQL/MariaDB. SQLite vẫn được
giữ làm chế độ dự phòng; file MySQL đầy đủ nằm ở `database/mysql/nhomnhom.sql`.

## Chạy với XAMPP

1. Chép toàn bộ dự án vào `C:\xampp\htdocs\nhomnhom`.
2. Bật extension `pdo_mysql` trong `C:\xampp\php\php.ini`.
3. Bật Apache và mở `http://localhost/nhomnhom/`.

Nếu dùng CSDL ở vị trí khác, đặt biến môi trường Apache `NHOMNHOM_DB_PATH` trỏ
tới file SQLite. PHP mở CSDL bằng prepared statements, bật khóa ngoại và timeout
ghi để tránh lỗi khi có nhiều yêu cầu đồng thời.

## Cấu hình driver

Để dùng MySQL, đặt `NHOMNHOM_DB_DRIVER=mysql` cùng host, port, database, user và
password như trong `database/mysql/README.md`. Nếu không đặt driver, ứng dụng
dùng SQLite tại `data/nhomnhom.sqlite`.

## API

`GET /api/restaurants.php?q=bun%20bo&page=1&pageSize=12` trả envelope:

```json
{"success":true,"data":[],"pagination":{"page":1,"pageSize":12,"total":0,"totalPages":0},"error":null}
```

Từ khóa tối đa 80 ký tự; `page` từ 1 đến 10000; `pageSize` từ 1 đến 100.
Tham số lạ, lặp hoặc không hợp lệ trả HTTP 400. Lỗi máy chủ chỉ trả thông báo
thân thiện và ghi chi tiết vào log Apache.

Tìm kiếm dùng chỉ mục FTS5 có sẵn trong CSDL và chuẩn hóa dấu tiếng Việt ở PHP.
Kết quả được xếp theo BM25, điểm đánh giá và ID để phân trang ổn định.

## Cấu trúc dữ liệu

Các file trong `database/migrations/` là SQL SQLite cho danh mục, cộng đồng,
đánh giá và chỉ mục tìm kiếm. File CSDL mẫu hiện có chứa 31 bảng quan hệ và dữ
liệu demo. Ảnh, địa chỉ và đánh giá mẫu chỉ dùng để kiểm thử; địa điểm có
`is_demo=1` không tạo liên kết chỉ đường thực tế.

Thư mục `src/` chứa lớp kết nối, kiểm tra tham số và repository tìm quán.
Thư mục `api/` có API danh mục và API khám phá món cấp món; giao diện nằm ở
`index.php`, `assets/app.js` và `assets/app.css`.

Khi mở CSDL lần đầu sau phiên bản PHP mới, `src/database.php` tự tạo các bảng
`nomnom_*` cho hồ sơ, lượt thích, nhóm, đặt bàn, coupon và Elo. Các bảng này tách
khỏi dữ liệu mẫu cũ để có thể mở rộng mà không phá vỡ migration ban đầu.
