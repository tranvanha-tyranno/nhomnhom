"""Generate the Vietnamese database reference using python-docx from the artifact runtime."""
import json
import sqlite3
from pathlib import Path
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from PIL import Image, ImageDraw, ImageFont
from doc_content import TABLES, FIELDS, GROUPS
from doc_diagram import draw_relationships

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs"
TMP = ROOT / "tmp" / "doc-qa"
OUT.mkdir(exist_ok=True)
TMP.mkdir(parents=True, exist_ok=True)
db = sqlite3.connect((ROOT / "data/nhomnhom.sqlite").as_uri() + "?mode=ro", uri=True)
db.row_factory = sqlite3.Row
counts = {name: db.execute(f'SELECT count(*) FROM "{name}"').fetchone()[0] for name in TABLES}
doc = Document()
section = doc.sections[0]
section.page_width, section.page_height = Inches(8.27), Inches(11.69)
section.top_margin = section.bottom_margin = Inches(.68)
section.left_margin = section.right_margin = Inches(.7)
for style in ["Normal", "Title", "Subtitle", "Heading 1", "Heading 2", "Heading 3"]:
    s = doc.styles[style]
    s.font.name = "Arial"
    s.font.color.rgb = RGBColor(0, 0, 0)
    s.font.bold = style.startswith("Heading")
    s.font.underline = False
for border in list(doc.styles.element.iter(qn("w:pBdr"))):
    border.getparent().remove(border)
doc.styles["Normal"].font.size = Pt(10)
doc.styles["Normal"].paragraph_format.space_after = Pt(7)
doc.styles["Normal"].paragraph_format.line_spacing = 1.12
doc.styles["Title"].font.size = Pt(29)
doc.styles["Heading 1"].font.size = Pt(19)
doc.styles["Heading 2"].font.size = Pt(12)
for name in ["Heading 1", "Heading 2"]:
    doc.styles[name].paragraph_format.space_before = Pt(10)
    doc.styles[name].paragraph_format.space_after = Pt(6)
footer = section.footer.paragraphs[0]
footer.alignment = WD_ALIGN_PARAGRAPH.RIGHT
footer.add_run("NhomNhom  |  ").font.size = Pt(8)
field = OxmlElement("w:fldSimple")
field.set(qn("w:instr"), "PAGE")
footer._p.append(field)
doc.core_properties.title = "Thiết kế và vận hành cơ sở dữ liệu NhomNhom"
doc.core_properties.subject = "Từ điển dữ liệu và hướng dẫn vận hành SQLite"
doc.core_properties.author = "NhomNhom"

def p(text):
    para = doc.add_paragraph(text)
    for run in para.runs:
        run.font.name = "Arial"
        run.font.bold = False
        run.font.cs_bold = False
        run.font.size = Pt(10)
    return para

def h(text, level=1):
    return doc.add_heading(text, level)

def page(title):
    doc.add_page_break()
    h(title)

def table(headers, rows, widths=None):
    t = doc.add_table(rows=1, cols=len(headers))
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    t.autofit = False
    widths = widths or [6.87 / len(headers)] * len(headers)
    for c, width in zip(t.columns, widths):
        c.width = Inches(width)
    for cell, text in zip(t.rows[0].cells, headers):
        cell.text = str(text)
    for row in rows:
        for cell, text in zip(t.add_row().cells, row):
            cell.text = str(text)
    for i, row in enumerate(t.rows):
        trpr = row._tr.get_or_add_trPr()
        trpr.append(OxmlElement("w:cantSplit"))
        if i == 0:
            trpr.append(OxmlElement("w:tblHeader"))
        for j, cell in enumerate(row.cells):
            cell.width = Inches(widths[j])
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            props = cell._tc.get_or_add_tcPr()
            shading = OxmlElement("w:shd")
            shading.set(qn("w:fill"), "17324D" if i == 0 else ("F0F4F7" if i % 2 == 0 else "FFFFFF"))
            props.append(shading)
            margins = OxmlElement("w:tcMar")
            for side in ["top", "left", "bottom", "right"]:
                edge = OxmlElement(f"w:{side}"); edge.set(qn("w:w"), "75"); edge.set(qn("w:type"), "dxa"); margins.append(edge)
            props.append(margins)
            borders = OxmlElement("w:tcBorders")
            for side in ["top", "left", "bottom", "right"]:
                edge = OxmlElement(f"w:{side}"); edge.set(qn("w:val"), "single"); edge.set(qn("w:sz"), "4"); edge.set(qn("w:color"), "D9D9D9"); borders.append(edge)
            props.append(borders)
            for para in cell.paragraphs:
                para.paragraph_format.space_after = Pt(0)
                para.paragraph_format.line_spacing = 1.05
                for run in para.runs:
                    run.font.size = Pt(8.5)
                    run.font.bold = i == 0
                    run.font.color.rgb = RGBColor.from_string("FFFFFF" if i == 0 else "000000")
    doc.add_paragraph().paragraph_format.space_after = Pt(1)
    return t

def code(text):
    para = p(text)
    para.paragraph_format.line_spacing = 1.05
    for run in para.runs:
        run.font.name = "Consolas"
        run.font.size = Pt(9)

doc.add_paragraph("Thiết kế và vận hành\ncơ sở dữ liệu NhomNhom", "Title")
p("Tài liệu kỹ thuật cho người phát triển và người quản lý dữ liệu")
p("Phiên bản 1   •   Ngày 05 tháng 10 năm 2026")
h("Cơ sở dữ liệu đã triển khai", 2)
p("NhomNhom đã có cơ sở dữ liệu SQLite kết nối trực tiếp với chức năng tìm kiếm quán ăn trên web. Bộ dữ liệu khởi tạo gồm 31 bảng quan hệ và 615.604 bản ghi, đủ để kiểm thử tìm kiếm, phân trang, liên kết thực đơn và các ràng buộc dữ liệu.")
p("Tài liệu giải thích cấu trúc đã tạo trong mã nguồn, mô tả từng trường dữ liệu và hướng dẫn khởi tạo, kiểm tra, sao lưu, khôi phục. Người đọc có thể dùng tài liệu để bảo trì hệ thống hoặc phát triển các chức năng tài khoản và tương tác.")
table(["Chỉ tiêu", "Giá trị"], [["Bảng nghiệp vụ và bảng vận hành", "29 + 2 = 31"], ["Quán ăn và mục thực đơn", "5.000 quán · 60.000 mục"], ["Người dùng mẫu", "10.000"], ["Đánh giá và lượt yêu thích", "100.000 mỗi loại"], ["Dung lượng file chính tại thời điểm lập tài liệu", f"{(ROOT / 'data/nhomnhom.sqlite').stat().st_size / 1024**2:.2f} MiB"]], [4.8, 2.07])
p("Dữ liệu quán, địa chỉ, tọa độ, tài khoản và đánh giá là giả lập. Ảnh được dùng để minh họa. Không sử dụng bộ mẫu để xác nhận quán đang hoạt động, xác định món an toàn với dị ứng hoặc dẫn đường đến một quán thật.")
h("Nội dung tài liệu", 2)
p("Phạm vi và kiến trúc • Quan hệ dữ liệu • Quy mô bộ mẫu • Khởi tạo và khôi phục • Từ điển 31 bảng • Chỉ mục và tính toàn vẹn • API và bảo mật • Kiểm thử và hướng mở rộng")

page("Phạm vi và kiến trúc")
p("SQLite được chọn để chạy ngay trên một máy mà không cần cài máy chủ CSDL riêng. PHP 8.2 truy cập SQLite bằng module node:sqlite; PHP thực thi truy vấn ở phía máy chủ. Trình duyệt chỉ nhận một trang kết quả JSON, không tải toàn bộ dữ liệu.")
table(["Lớp", "Vai trò đã triển khai"], [["Giao diện PHP/JavaScript", "Nhập từ khóa, chờ 250 ms, gọi API, xử lý tải và lỗi, hiển thị 12 kết quả mỗi trang."], ["GET /api/restaurants", "Kiểm tra tham số, gọi repository, trả envelope thành công hoặc lỗi."], ["RestaurantRepository", "Tìm kiếm FTS5, lọc quán active, xếp hạng và đọc thông tin liên quan."], ["SQLite", "Lưu dữ liệu quan hệ, thực thi khóa ngoại, CHECK, UNIQUE, trigger và transaction."], ["PHP CLI", "Migration, seed, thống kê, xác minh, reindex và backup."]], [1.8, 5.07])
h("Những chức năng có dữ liệu nhưng chưa có giao diện", 2)
p("Các bảng người dùng, sở thích, yêu thích, bộ sưu tập, đánh giá và nhật ký đã có dữ liệu mẫu cùng ràng buộc. Phiên bản này chưa cung cấp đăng nhập, API ghi dữ liệu hoặc màn hình quản trị. Lịch sử mẫu không chứng minh ứng dụng đang theo dõi thao tác thật của người dùng.")
h("Quy ước dữ liệu", 2)
p("ID là INTEGER. Tiền dùng số nguyên đồng VND để tránh sai số số thực. Thời gian do CURRENT_TIMESTAMP tạo được lưu dưới dạng TEXT UTC; khi hiển thị cần chuyển sang múi giờ phù hợp. Cờ đúng sai dùng INTEGER 0 hoặc 1. Mọi bảng quan hệ dùng chế độ STRICT.")
p("Các bảng nối dùng khóa chính kép. Bảng danh mục dùng khóa tự nhiên bổ sung khi cần, ví dụ email không phân biệt chữ hoa thường hoặc cặp tên thành phố và quốc gia. is_demo phân biệt quán giả lập; UI không tạo liên kết chỉ đường cho quán có cờ này.")
h("Giới hạn vận hành", 2)
p("WAL cho phép đọc đồng thời trong khi ghi, nhưng SQLite vẫn chỉ có một luồng ghi tại một thời điểm. DatabaseSync thực thi đồng bộ và có thể giữ event loop khi truy vấn dài. Thiết kế phù hợp bản demo lớn hoặc triển khai một máy; chưa phải kiến trúc nhiều máy có tải ghi cao.")

page("Sơ đồ quan hệ cốt lõi")
p("Sơ đồ trình bày các bảng trung tâm và bảng nối chính. Ký hiệu 1 và N chỉ một và nhiều bản ghi ở mỗi đầu liên kết. Danh sách quan hệ đầy đủ hơn nằm ở trang tiếp theo và trong phần từ điển dữ liệu.")
diagram = TMP / "relationships.png"
draw_relationships(diagram)
doc.add_picture(str(diagram), width=Inches(6.87))
p("Hình 1  Quan hệ giữa tài khoản, quán ăn, thực đơn và nguyên liệu")
p("Khóa ngoại luôn nằm ở phía N. Ví dụ menu_items.restaurant_id tham chiếu restaurants.id, còn menu_items.dish_id tham chiếu dishes.id. Cặp restaurant_id và dish_id là duy nhất để tránh lặp món trong thực đơn một quán.")
p("users và restaurants không có khóa ngoại trực tiếp sang nhau. favorites và reviews biểu diễn từng loại tương tác độc lập, giúp lưu thời điểm và ràng buộc riêng cho mỗi nghiệp vụ.")
page("Quan hệ giữa các nhóm dữ liệu")
p("Quán ăn là trung tâm liên kết địa lý, thực đơn và tương tác. Món ăn được tách khỏi quán để cùng một món có giá khác nhau ở từng quán. Người dùng liên kết với quán qua các bảng yêu thích, đánh giá và bộ sưu tập.")
table(["Thực thể gốc", "Quan hệ", "Thực thể đích"], [
    ["countries", "1 đến nhiều", "cities"], ["cities", "1 đến nhiều", "areas"], ["areas", "1 đến nhiều", "restaurants"],
    ["restaurants", "nhiều đến nhiều qua menu_items", "dishes"], ["dishes", "nhiều đến nhiều qua dish_ingredients", "ingredients"],
    ["dishes", "nhiều đến nhiều qua dish_allergens", "allergens"], ["restaurants", "nhiều đến nhiều qua bảng nối", "cuisines, amenities, tags"],
    ["users", "0 hoặc 1 cấu hình", "user_preferences"], ["users", "nhiều đến nhiều qua favorites", "restaurants"],
    ["users", "1 đến nhiều", "collections"], ["collections", "nhiều đến nhiều qua collection_items", "restaurants"],
    ["users và restaurants", "mỗi cặp tối đa 1 đánh giá", "reviews"], ["reviews", "nhiều đến nhiều qua review_votes", "users"]], [1.65, 3.2, 2.02])
h("Chính sách xóa", 2)
p("Xóa người dùng làm xóa sở thích, yêu thích, bộ sưu tập, đánh giá và bình chọn liên quan. Các dòng search_history, direction_events và audit_events giữ lại nhưng đặt khóa người dùng về NULL. Xóa quán làm xóa các bản ghi con phụ thuộc quán. Danh mục dùng mặc định NO ACTION để ngăn xóa dữ liệu còn được tham chiếu.")
p("CASCADE và SET NULL là quy tắc toàn vẹn, chưa thay thế chính sách quyền riêng tư. Nếu thu thập dữ liệu thật, cần quyết định thêm thời hạn lưu và quy trình xóa nội dung có thể chứa thông tin cá nhân.")

page("Quy mô và cách tạo dữ liệu mẫu")
table(["Bảng", "Bản ghi", "Bảng", "Bản ghi"], [[a, f"{counts[a]:,}", b, f"{counts[b]:,}"] for a,b in zip(list(TABLES)[::2], list(TABLES)[1::2])] + [["seed_runs", "1", "Tổng", f"{sum(counts.values()):,}"]], [2.5,.8,2.77,.8])
p("Tổng số không bao gồm bảng ảo restaurant_search và các bảng phụ nội bộ của FTS5. Hai bảng vận hành schema_migrations và seed_runs được tính trong 31 bảng. Tên thành phố là mốc địa lý minh họa; 40 khu mẫu không đại diện cho danh mục hành chính Việt Nam.")
h("Tính lặp lại của bộ seed", 2)
p("Seed tạo dữ liệu theo công thức xác định, không dùng nguồn đánh giá hoặc tài khoản thật. Mỗi quán có 12 mục thực đơn, 7 dòng giờ mở cửa, 3 tiện ích và 3 thẻ. Mỗi người dùng có 10 đánh giá, 10 yêu thích, 1 bộ sưu tập với 3 quán, 5 sự kiện tìm kiếm và 5 sự kiện chỉ đường khi dùng cấu hình mặc định.")
p("Tất cả dữ liệu seed được ghi trong một transaction. Nếu lỗi xảy ra, phần seed được rollback. Bản ghi demo-v1 trong seed_runs ngăn chạy lại tạo trùng. Khi thay đổi kích thước seed, chương trình yêu cầu dùng file CSDL mới thay vì xóa dữ liệu cũ.")

page("Khởi tạo và sao lưu")
p("Chạy lệnh từ thư mục gốc của dự án với PHP 8.2. Trước lần chạy web đầu tiên, khởi tạo CSDL bằng lệnh dưới đây.")
code("C:\\xampp\\php\\php.exe tests/php/search_test.php\nApache: http://localhost/nhomnhom/")
table(["Lệnh", "Tác dụng"], [["PHP migration SQL", "Chỉ áp dụng migration."], ["PHP/SQLite query", "Đếm bản ghi từng bảng."], ["PHP/SQLite integrity check", "Kiểm tra toàn vẹn, khóa ngoại, điểm tổng hợp và cấu trúc FTS."], ["SQLite FTS5 reindex", "Dựng lại nội dung tìm kiếm trong transaction."], ["SQLite backup", "Tạo bản sao nhất quán, mở lại và xác minh."]], [2.5,4.37])
h("Cấu hình bằng biến môi trường", 2)
code("$env:NHOMNHOM_DB_PATH = 'data/nhomnhom-small.sqlite'\n$env:SEED_RESTAURANTS = '100'\n$env:SEED_USERS = '200'\nDùng file data/nhomnhom.sqlite đã seed")
p("CLI không tự đọc .env; dùng biến môi trường của shell hoặc Apache SetEnv NHOMNHOM_DB_PATH. Kích thước seed phải là số nguyên từ 2 đến 100000 cho mỗi biến. Cấu hình mặc định là 5000 quán và 10000 người dùng.")
h("Khôi phục không ghi đè dữ liệu đang chạy", 2)
p("Dừng tiến trình sử dụng CSDL. Sao chép bản backup đã kiểm tra sang một đường dẫn mới, đặt DATABASE_PATH tới file mới, chạy db:verify rồi khởi động lại web. Giữ nguyên bản gốc cho đến khi xác nhận dữ liệu phục hồi đúng. Không sao chép riêng file .sqlite đang hoạt động trong chế độ WAL để làm backup.")
h("Triển khai Docker", 2)
code("Đặt dự án vào C:\\xampp\\htdocs và bật Apache")
p("Compose dùng volume riêng cho /app/data và /app/backups. Cổng host mặc định là 3000; cần đổi nếu đang bận. File CSDL, bản sao lưu và tài liệu được loại khỏi nội dung build image. Lệnh Docker cần được kiểm tra trong môi trường triển khai trước khi sử dụng chính thức.")

def describe_table(name):
    title, description = TABLES[name]
    h(title, 2)
    p(f"{name}  |  {counts[name]:,} bản ghi. {description}")
    columns = db.execute(f'PRAGMA table_info("{name}")').fetchall()
    fks = {row["from"]: row for row in db.execute(f'PRAGMA foreign_key_list("{name}")')}
    rows = []
    for col in columns:
        field = col["name"]
        rules = ["PK" if col["pk"] else ("Bắt buộc" if col["notnull"] else "Cho phép NULL")]
        if field in fks:
            rules.append("FK " + fks[field]["table"])
        if col["dflt_value"] is not None:
            rules.append("Mặc định " + str(col["dflt_value"]))
        description = FIELDS.get(field, "Tham chiếu " + fks[field]["table"] if field in fks else "Giá trị quản lý nội bộ")
        if field == "status":
            description = {"restaurants":"active, closed hoặc pending", "users":"demo, active hoặc disabled", "reviews":"published, pending hoặc hidden"}[name]
        rows.append([field, col["type"], "; ".join(rules), description])
    table(["Trường", "Kiểu", "Khóa và mặc định", "Ý nghĩa"], rows, [1.37,.85,1.97,2.68])
    uniques = []
    for idx in db.execute(f'PRAGMA index_list("{name}")'):
        if idx["unique"]:
            cols = ", ".join(r["name"] for r in db.execute(f'PRAGMA index_info("{idx["name"]}")'))
            uniques.append(cols + (" khi is_cover = 1" if idx["partial"] else ""))
    if uniques:
        p("Duy nhất theo: " + "; ".join(uniques) + ".")

for i, group in enumerate(GROUPS, 1):
    page(f"Từ điển dữ liệu phần {i}")
    if i == 1:
        p("PK là khóa chính; FK là khóa ngoại. Các bảng dùng STRICT. Ràng buộc UNIQUE có thể bao gồm nhiều trường; với trường cho phép NULL, giá trị NULL không bị UNIQUE xem là bản sao trùng.")
    for name in group:
        describe_table(name)

page("Tìm kiếm chỉ mục và tính toàn vẹn")
h("Chỉ mục toàn văn", 2)
p("restaurant_search là bảng ảo FTS5 với hai cột name và content. rowid khớp với restaurants.id. View catalog_search_documents ghép tên, mô tả, địa chỉ, khu vực, thành phố, ẩm thực, món còn phục vụ và thẻ. Chữ tiếng Việt được chuẩn hóa, bao gồm chuyển đ thành d.")
p("Mỗi từ trong câu tìm kiếm được chuyển thành một token tiền tố có dấu nháy bảo vệ, rồi nối bằng AND. Tham số SQL vẫn được bind riêng. Tên quán có trọng số BM25 lớn hơn nội dung; thứ tự phụ là điểm trung bình giảm dần và ID. Truy vấn rỗng sắp theo điểm và ID.")
p("Sau khi sửa danh mục bằng SQL hoặc nhập dữ liệu mới, phải chạy db:reindex. Việc cập nhật nội dung FTS chưa được tự động hóa cho mọi bảng. db:verify kiểm tra cấu trúc FTS và sự khớp ID, không phát hiện mọi trường hợp nội dung chỉ mục cũ.")
h("Điểm đánh giá và trigger", 2)
p("Ba trigger reviews_insert, reviews_update và reviews_delete duy trì rating_sum và review_count. Khi đổi quán hoặc chuyển trạng thái đánh giá, trigger trừ đóng góp cũ rồi cộng đóng góp mới. Chỉ đánh giá published được tính. View restaurant_rating_summary trả điểm trung bình làm tròn một chữ số thập phân, bằng 0 nếu chưa có đánh giá.")
h("Chỉ mục B tree", 2)
p("Khóa chính và UNIQUE hỗ trợ nhận diện và ngăn trùng. Các chỉ mục phụ bao phủ chiều ngược của bảng nối, quán theo khu vực/trạng thái, món theo danh mục/ẩm thực, đánh giá theo quán/trạng thái/thời gian và lịch sử theo người dùng/thời gian. idx_one_cover chỉ áp dụng cho ảnh có is_cover = 1.")
h("Migration và transaction", 2)
p("Mỗi migration chạy trong BEGIN IMMEDIATE và được ghi nhận cùng checksum khi hoàn tất. Dòng xuống hàng được chuẩn hóa trước khi băm để tránh khác biệt CRLF và LF. Không sửa migration đã áp dụng; thêm file mới có số thứ tự lớn hơn. Seed và reindex cũng chạy trong transaction để tránh dữ liệu dở dang.")
p("Mỗi connection bật foreign_keys, busy_timeout 5000 ms, journal_mode WAL và synchronous NORMAL. NORMAL tối ưu tốc độ nhưng các giao dịch mới nhất có thể mất khi mất điện; nếu yêu cầu độ bền cao hơn cần đánh giá synchronous FULL và kiểm thử trên hạ tầng thực tế.")

page("Giao tiếp API và bảo mật")
code("GET /api/restaurants?q=bun%20bo&page=1&pageSize=12")
table(["Tham số", "Mặc định", "Ràng buộc"], [["q", "Chuỗi rỗng", "Tối đa 80 ký tự, không chứa ký tự điều khiển."], ["page", "1", "Số nguyên từ 1 đến 10000."], ["pageSize", "12", "Số nguyên từ 1 đến 100."]], [1.3,1.5,4.07])
code('{ "success": true, "data": [ ... ], "error": null,\n  "pagination": { "page": 1, "pageSize": 12,\n                  "total": 5000, "totalPages": 417 } }')
p("HTTP 400 dành cho tham số không hợp lệ, bị lặp hoặc không hỗ trợ. Lỗi máy chủ trả HTTP 500 với thông báo chung; chi tiết được ghi ở console phía máy chủ. Phản hồi sử dụng Cache-Control no-store. API chỉ trả dữ liệu quán, không trả email hoặc nhật ký người dùng.")
h("Biện pháp đã thực hiện", 2)
p("Giá trị truy vấn được truyền qua prepared statements. Dữ liệu được escape bằng JavaScript trước khi chèn vào giao diện. Tài khoản mẫu không chứa mật khẩu hoặc token và email dùng miền example.invalid. CSDL cùng backup nằm ngoài public và bị loại khỏi Git. Tất cả kiểm tra đầu vào phía API được thực hiện trước khi mở CSDL.")
h("Biện pháp cần có trước khi mở API ghi", 2)
p("Bổ sung xác thực, phân quyền theo chủ sở hữu, rate limit và kiểm tra quyền ở từng thao tác. Nếu xác thực bằng cookie, triển khai chống CSRF cho thao tác thay đổi dữ liệu. Xác minh URL ảnh trước khi cho phép nguồn ngoài và giới hạn dung lượng/tần suất tải lên. Các chức năng này chưa được triển khai trong phiên bản hiện tại.")
p("File SQLite chưa mã hóa và không có hệ thống tài khoản CSDL riêng như máy chủ PostgreSQL. Quyền đọc file và thư mục backup phải được giới hạn ở hệ điều hành. Không đưa mật khẩu, token, tọa độ cá nhân hoặc nội dung nhạy cảm vào audit_events.metadata_json.")

page("Kiểm thử và hướng phát triển")
p("Tại thời điểm lập tài liệu, bộ kiểm thử tự động có 22 bài đạt, gồm logic tìm món và Google Maps có sẵn, mô hình CNN có sẵn, kiểm tra CSDL và API mới. Production build PHP hoàn tất. CSDL đã qua integrity_check, foreign_key_check, đối chiếu điểm đánh giá và kiểm tra FTS; bản sao lưu đã được mở lại và xác minh.")
table(["Nhóm kiểm thử", "Điều kiện đã kiểm tra"], [["Toàn vẹn", "Khóa ngoại, không trùng yêu thích, giá âm, tọa độ ngoài miền và điểm sai."], ["Đánh giá", "Insert, update, đổi trạng thái và delete cập nhật số lượng/tổng điểm."], ["Vận hành", "Migration/seed lặp lại, checksum bị sửa, kích thước seed khác và xóa người dùng."], ["Tìm kiếm", "Có dấu/không dấu, phân trang, cú pháp SQL/FTS, sửa món và reindex, ẩn quán closed."], ["API", "Tham số sai trả 400; lỗi máy chủ không lộ đường dẫn CSDL."]], [1.6,5.27])
h("Truy vấn kiểm tra thực tế", 2)
code("SELECT r.id, r.name, s.average_rating, s.review_count\nFROM restaurants r JOIN restaurant_rating_summary s\nON s.restaurant_id = r.id\nWHERE r.status = 'active'\nORDER BY s.average_rating DESC, r.id LIMIT 12;")
code("SELECT r.name, d.name, mi.price_vnd\nFROM menu_items mi\nJOIN restaurants r ON r.id = mi.restaurant_id\nJOIN dishes d ON d.id = mi.dish_id\nWHERE r.id = 1 AND mi.is_available = 1;")
h("Thứ tự mở rộng đề xuất", 2)
p("Trước hết nhập dữ liệu quán được xác minh và tách hoàn toàn bộ demo. Tiếp theo triển khai đăng nhập và lưu yêu thích với phân quyền, sau đó bổ sung quản trị thực đơn và quy trình duyệt đánh giá. Khi cần lọc bán kính địa lý, nhiều tiến trình ghi hoặc nhiều máy chủ, đánh giá chuyển repository sang PostgreSQL/PostGIS.")
p("Bộ seed kiểm tra dung lượng và quan hệ, không mô phỏng đầy đủ phân bố hành vi thực tế. Chưa có benchmark tải đồng thời, kiểm thử phục hồi sau mất điện, đồng bộ nhiều máy hoặc cam kết thông lượng. Các ràng buộc chi tiết có nguồn chuẩn tại database/migrations; hướng dẫn lệnh tại database/README.md.")
doc.save(OUT / "Mo-ta-CSDL-NhomNhom.docx")
(TMP / "counts.json").write_text(json.dumps(counts, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"Created {OUT / 'Mo-ta-CSDL-NhomNhom.docx'} with {len(TABLES)} table dictionaries")
db.close()
