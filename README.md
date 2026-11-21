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

## Tài khoản đặt trước (Demo khi deploy)

CSDL `data/nhomnhom.sqlite` và bản dump `database/mysql/nhomnhom.sql` đã tạo sẵn 5 tài khoản mẫu
kèm hồ sơ khẩu vị, món yêu thích, điểm Elo, coupon (`NHOM10`, `CHAY20`, `BANHMI15`), lịch đặt bàn
sắp tới và 2 nhóm bình chọn (`CAFE2026`, `BEEF1234`):

| Email | Tên hiển thị | Khẩu vị mặc định | Lịch đặt bàn |
|---|---|---|---|
| `test@nhomnhom.local` | Người dùng Test | Ngân sách 120.000đ · Ô tô / xe máy | 2 sắp tới, 1 đã hủy |
| `minh.anh@nhomnhom.vn` | Minh Anh | Ngân sách 85.000đ · Đi bộ | 2 sắp tới |
| `hoang.nam@nhomnhom.vn` | Hoàng Nam | Ngân sách 150.000đ · Ô tô / xe máy | 2 sắp tới |
| `thu.ha@nhomnhom.vn` | Thu Hà | Chỉ món chay · 70.000đ · Xe đạp | 2 sắp tới |
| `quoc.bao@nhomnhom.vn` | Quốc Bảo | Ngân sách 200.000đ · Công cộng | 2 sắp tới |

Mật khẩu chung cho cả 5 tài khoản demo: `NhomNhom@2026`. Nếu cần nạp lại hoặc đổi mật khẩu
hàng loạt trước khi deploy, chạy:

```powershell
C:\xampp\php\php.exe scripts/seed_demo_accounts.php
python scripts/export_mysql.py
```

API tìm kiếm: `GET /nhomnhom/api/restaurants.php?q=bun%20bo&page=1&pageSize=12`.
Khám phá món: `GET /nhomnhom/api/discover.php?limit=8&offset=0&maxPrice=50000&maxSpice=1&sort=price`.
Hỗ trợ tìm tiếng Việt không dấu, lọc món chay (`vegetarian=1`), ngân sách (`maxPrice=0` = không giới hạn),
độ cay tối đa 0–3 và sắp xếp `rating` hoặc `price`. Bộ lọc và thứ tự được áp dụng trước phân trang.
Các API PHP bổ sung gồm `profile.php`, `taste.php`, `groups.php`, `bookings.php`
và `insights.php` cho hồ sơ khẩu vị, Elo món, nhóm chọn món, đặt bàn và bảng xu
hướng. Các thao tác ghi dùng session cookie, CSRF token và prepared statements.
Món yêu thích ở `favorites.php` lấy từ CSDL qua `GET api/taste.php`, cho phép tìm, sắp xếp,
bỏ lưu bằng POST `{action: "remove", dishId, csrfToken}` và chọn quán đang có giá thấp nhất.
Lựa chọn của tài khoản được lưu theo định danh ổn định, còn khách dùng dữ liệu theo phiên.
Dữ liệu phiên cũ không tự động chuyển sang tài khoản.

Từ món hoặc nhà hàng, chọn **Đặt bàn** để chuyển quán sang `bookings.php?restaurant=ID`.
Trang đặt bàn cũng có tìm quán bằng tên, tự điền thông tin tài khoản, lọc lịch sắp tới/đã hủy
và hủy lịch tương lai bằng POST `api/bookings.php` với `{action: "cancel", bookingId, csrfToken}`.
Máy chủ kiểm tra chủ sở hữu và trạng thái lịch; thời gian đặt bàn dùng múi giờ Việt Nam.
Đây là yêu cầu chờ xác nhận, chưa tích hợp hệ thống xác nhận thực tế của nhà hàng.
Địa điểm mẫu được ghi rõ và không có nút chỉ đường; Google Maps chỉ mở với dữ liệu đã xác minh.

Giao diện dùng CSS/JavaScript cục bộ, màu cam–xanh lá, điều hướng ngang trên desktop,
menu mobile và bố cục riêng cho đăng nhập/đăng ký. Tất cả 9 màn hình dùng chung hệ thiết kế.
Trang chủ có chọn nhóm món để tìm quán nhanh. Trang khám phá có **Chọn giúp tôi**:
`GET api/discover.php?sort=random&limit=1&offset=0` chọn một mục thực đơn trong bộ lọc
ngân sách, món chay và độ cay hiện tại. Không phân trang ngẫu nhiên; mỗi lần chọn là một lượt độc lập.
Trang nhóm có tìm món, bình chọn/đổi phiếu, sao chép mã mời và cập nhật kết quả thủ công.
Phiếu được máy chủ ràng buộc với thành viên nhóm, tách khỏi bộ sưu tập yêu thích cá nhân.
Danh mục nhóm `GET api/group-dishes.php?q=&limit=9&offset=0` phân trang theo món duy nhất,
hiển thị giá thực đơn thấp nhất trong các nhà hàng đang hoạt động phù hợp từ khóa.

Tham khảo thiết kế: [Frontend Design](https://github.com/anthropics/skills/tree/main/skills/frontend-design)
và [Responsive Design của web.dev](https://web.dev/learn/design/welcome).

### Rà soát phạm vi bản làm lại — 16/11/2026

Đây là bản triển khai và kiểm thử cục bộ, chưa phải xác nhận sẵn sàng phát hành thương mại.
Thay đổi mới dùng dữ liệu thực đơn hiện có; không thêm thanh toán, giải thưởng, quảng cáo,
AI sinh nội dung, định vị người dùng, nhà cung cấp xử lý dữ liệu hay tracking bên thứ ba.
Chọn ngẫu nhiên chỉ gợi ý món, không có phí tham gia hay phần thưởng.

| Phạm vi liên quan | Bằng chứng trong mã / việc còn lại | Người phụ trách |
|---|---|---|
| Tài khoản, khẩu vị, nhóm | Session, CSRF, truy vấn tham số hóa và kiểm tra thành viên; cần chủ vận hành xác định chính sách dữ liệu, lưu giữ, quyền người dùng và nhà cung cấp thực tế trước phát hành | Chủ vận hành |
| Thông tin nhà hàng, đặt bàn | Giữ nhãn dữ liệu mẫu và trạng thái chờ xác nhận; cần xác minh địa chỉ, giá, quyền hiển thị và quy trình xác nhận của quán | Chủ vận hành |
| Tài sản, dịch vụ ngoài | Tái sử dụng ảnh/SVG cục bộ; Google Maps chỉ mở bằng thao tác người dùng với địa điểm không đánh dấu mẫu; cần xác nhận quyền ảnh và nguồn dữ liệu | Chủ vận hành |
| Tư cách vận hành, TMĐT và nghĩa vụ khác | Chưa có bằng chứng về chủ thể, mô hình thu phí, hồ sơ hay địa bàn ngoài Việt Nam; phải xác định và đối chiếu nguồn chính thức hiện hành trước khi kết luận nghĩa vụ phát hành | Chủ vận hành |

Rà soát trên ghi nhận tác động kỹ thuật; không xác nhận thủ tục hoặc tuân thủ pháp luật đã hoàn tất.

Chạy toàn bộ kiểm thử trong PowerShell:

```powershell
Get-ChildItem tests/php/*.php | ForEach-Object {
    & C:\xampp\php\php.exe $_.FullName
    if ($LASTEXITCODE -ne 0) { throw "Test failed: $($_.Name)" }
}
```
