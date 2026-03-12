# CTU E-Learning — Hệ thống Quản lý Học trực tuyến

Hệ thống E-Learning dành cho Trường Đại học Cần Thơ (CTU). Ứng dụng web cho phép **quản trị viên (Admin)** quản lý khoá học, bài học, học viên, doanh thu; và **học viên (Student)** đăng ký, mua khoá học, xem video bài giảng, quản lý hồ sơ cá nhân.

Dự án này được thiết kế theo mô hình client-server truyền thống, sử dụng PHP cho xử lý backend và MySQL để lưu trữ dữ liệu.

---

## 🚀 Tính năng & Luồng hoạt động (Workflow)

Hệ thống được chia làm 3 luồng chính, tương ứng với 3 đối tượng sử dụng:

### 1. Khách viếng thăm (Người chưa có tài khoản)
- **Xem trang chủ:** Xem thông tin giới thiệu, các khoá học nổi bật, và đánh giá (feedback) từ học viên cũ.
- **Xem danh sách khoá học:** Xem tất cả khoá học hiện có, xem chi tiết mô tả, giảng viên, giá tiền, thời lượng.
- **Đăng ký / Đăng nhập:** Tạo tài khoản học viên mới để bắt đầu mua khoá học (sử dụng AJAX để hiển thị thông báo lỗi/thành công mà không cần tải lại trang).

### 2. Học viên (Student - Cần đăng nhập)
*Luồng học viên: Đăng ký → Mua khoá học → Học bài → Đánh giá*
- **Quản lý Giỏ hàng:** Thêm các khoá học muốn học vào giỏ, sau đó tiến hành thanh toán (hiện tại hỗ trợ checkout mô phỏng).
- **Khoá học của tôi:** Nơi hiển thị các khoá học **đã thanh toán thành công**.
- **Học bài (Watch Course):** Giao diện thiết kế theo phong cách rạp phim (dark cinema-style) với **Playlist bài học** bên trái và **Video Player** bên phải. Nhấn vào bài nào, video bài đó sẽ phát.
- **Hồ sơ cá nhân:** 
  - Cập nhật thông tin (Tên, Nghề nghiệp) và **Ảnh đại diện (Avatar)**.
  - Xem danh sách khoá đã mua, Đổi mật khẩu.
  - Viết Đánh giá (Feedback) để hiển thị lên trang chủ.

### 3. Quản trị viên (Admin - Kiểm soát toàn hệ thống)
*Luồng Admin: Đăng nhập → Quản lý thông tin (Thêm/Sửa/Xoá) → Xem báo cáo doanh thu*
- **Bảng điều khiển (Dashboard):** Xem tổng quan số liệu bằng 4 thẻ thống kê: Tổng khoá học, Tổng học viên, Tổng số giao dịch, Tổng doanh thu. Đi kèm là bảng 10 giao dịch mua khoá học mới nhất.
- **Quản lý Khoá học:** Thêm/Sửa/Xoá khoá học. Mỗi khoá học có tải lên 1 **Ảnh Thumbnail**. *(Lưu ý: Không thể xoá khoá học nếu đã có học viên mua)*
- **Quản lý Bài học:** Thêm các bài học video vào từng Khoá học cụ thể (hỗ trợ nhập link YouTube hoặc file MP4).
- **Quản lý Học viên:** Xem thông tin, ảnh đại diện, số lượng khoá đã mua của từng học viên. Admin có quyền Xoá học viên khỏi hệ thống.
- **Báo cáo doanh thu:** Lọc giao dịch mua khoá học theo **Từ ngày - Đến ngày**. Tính tổng doanh thu trong khoảng thời gian đó và hỗ trợ **In báo cáo**.

---

## 🛠 Công nghệ sử dụng

| Thành phần | Công nghệ | File/Thư mục liên quan |
|---|---|---|
| **Front-end UI** | HTML, [Tailwind CSS](https://tailwindcss.com/) (CDN), [Bootstrap 4](https://getbootstrap.com/) | `css/`, `js/` |
| **Logic Frontend**| JavaScript, jQuery (Dùng cho AJAX request) | `js/ajaxrequest.js`, `js/adminajaxrequest.js` |
| **Back-end** | PHP 7+ (Sử dụng Prepared Statements chống SQL Injection) | Toàn bộ các file `.php` |
| **Cơ sở dữ liệu** | MySQL / MariaDB | File `SQL/lms_db.sql` |
| **Web Server** | Apache (XAMPP) | |

---

## 🏁 Hướng dẫn cài đặt chi tiết cho người mới

Để chạy được website này, bạn có thể chọn **Cách 1 (Sử dụng Docker - Khuyên dùng)** hoặc **Cách 2 (Sử dụng XAMPP)**.

### Cách 1: Chạy bằng Docker (Khuyên dùng)
Hệ thống đã được tích hợp sẵn Docker giúp bạn khởi chạy ngay lập tức mà không cần cấu hình môi trường hay import Database thủ công.
1. Cài đặt phần mềm **[Docker Desktop](https://www.docker.com/products/docker-desktop/)**.
2. Mở Terminal (Command Prompt / PowerShell) tại thư mục gốc dự án (Nơi chứa file `docker-compose.yml`).
3. Chạy lệnh sau để khởi động toàn bộ hệ thống:
   ```bash
   docker-compose up -d
   ```
4. Đợi một lát cho các dịch vụ khởi động xong. Bây giờ mọi thứ đã sẵn sàng:
   - **Trang chủ học viên**: Truy cập vào `http://localhost:8080/`
   - **Trang quản trị (Admin)**: Truy cập vào `http://localhost:8080/Admin/`
   - **Trình quản lý CSDL (phpMyAdmin)**: `http://localhost:8081/`

*(Lưu ý: Nếu muốn dừng server, chạy lệnh `docker-compose down`)*

---

### Cách 2: Sử dụng phần mềm XAMPP
Nếu bạn không cài Docker, bạn có thể dùng XAMPP để chạy PHP.

#### Bước 1: Chuẩn bị môi trường (Cài XAMPP)
1. Tải phần mềm **XAMPP** từ [apachefriends.org](https://www.apachefriends.org/) (Khuyên dùng bản PHP 7.4 hoặc 8.x).
2. Cài đặt vào máy và mở **XAMPP Control Panel**, nhấn **Start** cho `Apache` và `MySQL`.

#### Bước 2: Đặt mã nguồn đúng chỗ
XAMPP chỉ chạy code PHP khi đặt trong thư mục `htdocs`.
1. Copy thư mục `ELearning` của dự án này.
2. Dán thư mục `ELearning` vào mục `htdocs` của XAMPP:
   - Trên **Windows**: `C:\xampp\htdocs\ELearning\`
   - Trên **Mac**: `/Applications/XAMPP/xamppfiles/htdocs/ELearning/`

#### Bước 3: Thiết lập Cơ sở dữ liệu (Database)
Bạn cần tạo Database và import dữ liệu mẫu để web có thể chạy:
1. Mở trình duyệt web, gõ địa chỉ: `http://localhost/phpmyadmin`
2. Nhấn vào nút **New** (hoặc Mới). Tạo cơ sở dữ liệu mới với tên: **`lms_db`** (Bảng mã `utf8mb4_general_ci`).
3. Chọn database `lms_db` vừa tạo, bấm tab **Import** (Nhập).
4. Nhấn **Choose File**, tìm đến thư mục `SQL/` của dự án và chọn file `lms_db.sql`. Nhấn **Go**.
5. Làm tương tự bước Import với file `cart_table.sql` để có bảng giao dịch.

#### Bước 4: Kiểm tra File cấu hình
- File kết nối CSDL hiện tại mặc định là `$db_password = "";` cho XAMPP.
- Nếu XAMPP của bạn có đặt mật khẩu MySQL, hãy vào file `ELearning/dbConnection.php` để bổ sung mật khẩu.

#### Bước 5: Chạy dự án!
Mọi thứ đã sẵn sàng:
- **Trang chủ học viên**: Truy cập vào `http://localhost/ELearning/`
- **Trang quản trị (Admin)**: Truy cập vào `http://localhost/ELearning/Admin/`

---

## 🔑 Tài khoản đăng nhập mẫu

Thay vì phải tạo lại từ đầu, bạn có thể dùng các tài khoản đã có sẵn trong cơ sở dữ liệu để test:

| Vai trò | Email đăng nhập | Mật khẩu | Chức năng |
|---|---|---|---|
| **Quản trị viên** | `admin@gmail.com` | `admin` | Toàn quyền thêm, sửa, xoá khoá học, xem doanh thu... |
| **Học viên mẫu** | `cap@example.com` | `123456` | Có sẵn các khoá học đã mua, dùng để test xem video. |

---

## 📂 Giải thích Cấu trúc Thư mục

Việc chia nhỏ thư mục giúp code dễ quản lý và bảo mật hơn (Tách biệt quyền Admin và Học viên).

```text
ELearning/                    ← THƯ MỤC GỐC (Chứa các trang Public AI CŨNG XEM ĐƯỢC)
├── index.php                 # Trang chủ hiển thị banner, khoá học nổi bật
├── courses.php               # Danh sách tất cả khoá học 
├── coursedetails.php         # Chi tiết 1 khoá học (Giá, giảng viên, lộ trình)
├── login.php, signup.php     # Form đăng nhập, đăng ký
├── checkout.php              # Trang thanh toán
├── dbConnection.php          # File duy nhất chứa cấu hình kết nối MySQL -> Rất quan trọng!
│
├── Admin/                    ← KHU VỰC QUẢN TRỊ (Phải có session Admin mới vào được)
│   ├── adminDashboard.php    # Bảng số liệu tổng quan
│   ├── courses.php, editcourse.php # Quản lý khoá học (Thêm/Sửa/Xoá)
│   ├── lessons.php           # Quản lý file/link video bài giảng
│   ├── students.php          # Quản lý danh sách học viên
│   ├── sellReport.php        # Lọc giao dịch theo ngày, tính doanh thu
│   ├── adminInclude/         # File tái sử dụng (Header/Footer riêng cho Admin có giao diện Sidebar)
│   └── ...
│
├── Student/                  ← KHU VỰC HỌC VIÊN (Phải có session Học viên mới vào được)
│   ├── myCourse.php          # Liệt kê khoá học đã mua thành công
│   ├── watchcourse.php       # TRANG QUAN TRỌNG: Trình chiếu video bài giảng (Theater Mode)
│   ├── studentProfile.php    # Quản lý thông tin, upload Avatar
│   ├── myCart.php            # Giỏ hàng chưa thanh toán
│   ├── stuInclude/           # Header/Footer riêng cho Học viên
│   └── ...
│
├── mainInclude/              # Header (Thanh điều hướng) và Footer dùng chung cho trang Public
├── css/ & js/                # File phong cách (Bootstrap, Tailwind style) và script (AJAX, Carousel)
├── image/                    # Thư mục lưu ẢNH UPLOAD. Chứa 2 thư mục con: `courseimg/` và `stu/`
├── lessonvid/                # Cất giữ các file MP4 bài giảng mẫu.
└── SQL/                      # (File ngoài mã nguồn) Chứa file cấu trúc Database để export/import.
```

---

## 📸 Xử lý ảnh tải lên (Upload Paths)

Một trong những phần dễ lỗi nhất của dự án là đường dẫn ảnh. Dự án áp dụng quy tắc sau:

1. Khi Admin thêm hình Khoá học, file gốc sẽ được copy bằng PHP (`move_uploaded_file`) vào thư mục vật lý hệ thống: `ELearning/image/courseimg/tên_file_mới.jpg`
2. Tương tự, Học viên tải Avatar sẽ vào: `ELearning/image/stu/tên_file_mới.jpg`
3. Đường dẫn lưu vào Database là **đường dẫn tương đối từ gốc ELearning**: `image/courseimg/tên_file.jpg`. Điều này giúp hệ thống không bị lỗi file khi bưng code từ máy này sang máy khác.

---

## ⚠️ Database Schema (Cấu trúc DB `lms_db` chi tiết)

Tìm hiểu ý nghĩa các bảng để biết dữ liệu chảy như thế nào.

| Tên Bảng | Đặc điểm & Vai trò |
|---|---|
| `admin` | Lưu TK Quản trị (`admin_email`, `admin_pass`). |
| `student` | Lưu TK Học viên (`stu_email`, `stu_pass`, `stu_img` lưu avatar). |
| `course` | Thông tin 1 khoá học (`course_name`, `course_price` là giá bán thực tế, `course_img`). |
| `lesson` | Bài giảng con. Bắt buộc có `course_id` để biết nó thuộc Khoá học nào. `lesson_link` chứa URL video. |
| `cart` | Giỏ hàng tạm thời, lưu `stu_email` gắn với `course_id`. Sẽ bị xoá khi thanh toán thành công. |
| `courseorder`| Giao dịch THÀNH CÔNG. Gắn `stu_email` mua `course_id`. Bảng này dùng để tính Doanh thu bên Admin và hiện "Khoá học của tôi" bên Student. |
| `feedback` | Nhận xét của học viên. Link với `student` thông qua `stu_id` để hiện Tên người gửi lên trang chủ. |

---

## 📌 Ghi chú Bảo mật & Kỹ thuật

- **Mật khẩu** hiện tại đang lưu dưới dạng text thô (plain text) nhằm mục đích giáo dục dễ quan sát database. Ở môi trường thực tế (production), dev cần phải hash mật khẩu (VD: dùng `password_hash()` của PHP).
- Dự án có sử dụng **AJAX** (trong `js/ajaxrequest.js`) để kiểm tra email trùng lặp lúc đăng ký và thực hiện đăng nhập mà không làm chớp tải lại trang.
- File `Admin/adminPaymentStatus.php` và thư mục `PaytmKit/` là code tích hợp cổng thanh toán Paytm của Ấn Độ (chưa dùng được cho thẻ ngân hàng Việt Nam, dùng để tham khảo).
- Hệ thống thiết kế các file "Include" (`header.php`, `footer.php`) giúp không phải copy-paste cục mã HTML menu lặp lại ở cả trăm trang. Code tái sử dụng cao.
