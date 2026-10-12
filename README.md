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

## Tìm, lưu và so sánh quán

- **Bộ lọc quán:** giá món tối đa, phân khúc giá, đánh giá tối thiểu và có món chay.
  Sắp xếp theo độ phù hợp, đánh giá, giá món thấp nhất hoặc số lượt đánh giá.
  Ngân sách trống/0 nghĩa là không giới hạn. Khi bật món chay, cùng một món đang
  bán phải thỏa cả ngân sách và điều kiện chay.
- **Thực đơn & chi tiết:** xem món đang bán, giá, độ cay, món chay, giờ mở cửa và
  tiện ích. Quán mẫu vẫn được ghi rõ và không mở chỉ đường thực tế.
- **Quán đã lưu:** lưu/bỏ lưu tối đa 100 quán, xem lại trong cùng phiên trình duyệt.
  Dữ liệu lưu trong CSDL theo session; chưa đồng bộ tài khoản hoặc thiết bị khác.
  Xóa cookie/hết phiên sẽ mất quyền truy cập danh sách cũ.
- **So sánh:** chọn 2–3 quán, kể cả ở các trang kết quả khác nhau, để đối chiếu
  đánh giá, giá món thấp nhất, phân khúc và địa chỉ. Lựa chọn so sánh chỉ giữ khi
  trang đang mở. Giá hiển thị không phải chi phí trung bình mỗi người.
- **Hôm nay ăn đâu?:** chọn ngẫu nhiên một quán trong toàn bộ kết quả khớp từ khóa
  và bộ lọc hiện tại, rồi mở chi tiết để quyết định.

Giao diện chia thành 7 tab: **Tìm quán**, **Khám phá món**, **Quán đã lưu**,
**Khẩu vị**, **Nhóm**, **Đặt bàn** và **Xu hướng**. Mỗi lần chỉ hiện một phần;
bộ lọc, lựa chọn so sánh và nội dung biểu mẫu được giữ khi chuyển tab.
Có thể mở trực tiếp `#search`, `#discovery`, `#saved`, `#profile`, `#groups`,
`#booking`, `#insights`; nút Quay lại/Tiến tới cũng chuyển tab. Khi tập trung
thanh tab, dùng phím trái/phải, Home/End để di chuyển. Trên điện thoại, vuốt ngang
thanh tab để xem các mục còn lại.

Font Noto Sans (Latin và tiếng Việt, dạng WOFF2 variable) được lưu tại
`public/fonts/`, kèm giấy phép SIL Open Font License. Trang tự tải font từ máy
chủ của ứng dụng, không gọi Google Fonts khi người dùng mở web. Cỡ chữ, khoảng
cách dòng và font trong biểu mẫu dùng chung quy tắc ở `assets/typography.css`.

## API bổ sung

`GET api/restaurants.php` nhận thêm `maxPrice` (0–100000000), `priceLevel` (0–4;
0 là tất cả), `minRating` (0–5, tối đa một chữ số thập phân), `vegetarian` (0/1),
`sort` (`relevance`, `rating`, `price_asc`, `reviews`, `random`). Mỗi quán có thêm
`minPriceVnd`, là giá thấp nhất trong thực đơn đang bán hoặc `null` nếu chưa có.
Kết quả sắp giá đặt quán chưa có giá ở cuối; thứ tự ngẫu nhiên thay đổi mỗi lần gọi.

`GET api/restaurant.php?id=11` trả `{success, data, error}` với chi tiết, `menu`,
`hours` và `amenities`; quán không có/không hoạt động trả HTTP 404. `hours.weekday`
dùng 0 = Chủ nhật, 1 = Thứ hai; giờ là số phút tính từ 00:00, 1440 = 24:00.

`GET api/saved.php` trả `{success, data, csrfToken, error}` cho phiên hiện tại.
`POST api/saved.php` nhận JSON `{restaurantId: 11, action: "save" | "remove",
csrfToken: "..."}` với cùng session cookie. Không nhận session của người khác từ
request. Bảng `nomnom_saved_restaurants` tự tạo khi truy cập API lần đầu trên
SQLite/MySQL; tài khoản CSDL cần quyền CREATE TABLE. Không cần import lại dump.

## Kiểm thử tính năng

```powershell
C:\xampp\php\php.exe tests/php/search_test.php
C:\xampp\php\php.exe tests/php/discovery_test.php
C:\xampp\php\php.exe tests/php/features_test.php
C:\xampp\php\php.exe tests/php/explore_test.php
C:\xampp\php\php.exe tests/php/explore_http_test.php
```

`explore_test.php` dùng SQLite trong bộ nhớ, không thay đổi dữ liệu ứng dụng.
`explore_http_test.php` cần Apache đang chạy, cURL PHP và dữ liệu demo đã cài;
có thể truyền URL localhost khác làm đối số. Bài kiểm thử HTTP dùng phiên mới,
kiểm tra CSRF/cách ly phiên và tự bỏ lưu dữ liệu vừa tạo khi kết thúc.
