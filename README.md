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

Để chạy được website này trên máy tính của bạn (Localhost), hãy làm theo từng bước sau. Đảm bảo bạn làm đúng thứ tự.

### Bước 1: Chuẩn bị môi trường (Cài XAMPP)
1. Tải phần mềm **XAMPP** từ [apachefriends.org](https://www.apachefriends.org/) (Khuyên dùng bản PHP 7.4 hoặc 8.x).
2. Cài đặt vào máy (thường ở `C:\xampp` trên Windows hoặc `/opt/lampp` trên Linux).
3. Mở **XAMPP Control Panel**, nhấn **Start** cho 2 module là `Apache` (Web server chạy PHP) và `MySQL` (Máy chủ cơ sở dữ liệu). Khi cả 2 chuyển màu xanh lá là thành công.

### Bước 2: Đặt mã nguồn đúng chỗ
XAMPP chỉ chạy được code PHP nếu code nằm trong thư mục `htdocs`.
1. Copy thư mục `ELearning` của dự án này.
2. Dán thư mục `ELearning` vào bên trong thư mục `htdocs` của XAMPP:
   - Trên **Windows**: Dán vào `C:\xampp\htdocs\ELearning\`
   - Trên **Mac**: Dán vào `/Applications/XAMPP/xamppfiles/htdocs/ELearning/`
   - Trên **Linux**: Dán vào `/opt/lampp/htdocs/ELearning/`

### Bước 3: Thiết lập Cơ sở dữ liệu (Database)
Code PHP cần có dữ liệu để hiển thị. Bạn phải "nhập" (import) CSDL mẫu vào máy bạn:
1. Mở trình duyệt web (Chrome/Edge), gõ địa chỉ: `http://localhost/phpmyadmin`
2. Nhấn vào nút **New** (hoặc Mới) ở cột bên trái để tạo Database mới.
3. Ở ô *Tên cơ sở dữ liệu* (Database name), điền chính xác: **`lms_db`**
4. Ở ô bên cạnh (Bảng mã / Collation), chọn `utf8mb4_general_ci` (để hỗ trợ tiếng Việt có dấu). Nhấn **Create** (Tạo).
5. Nhấn vào database `lms_db` vừa tạo ở cột trái. Nhìn lên menu trên cùng, chọn tab **Import** (Nhập).
6. Nhấn nút **Choose File** (Chọn tệp), tìm đến file `lms_db.sql` (nằm trong thư mục `SQL/` của code bạn tải về).
7. Cuộn xuống dưới cùng, nhấn **Go** (Thực hiện). *Làm tương tự bước 5-6-7 với file `cart_table.sql` để import thêm bảng giỏ hàng.*

### Bước 4: Kiểm tra File cấu hình kết nối
Hệ thống cần biết mật khẩu MySQL của máy bạn để kết nối. File quy định điều này là `ELearning/dbConnection.php`.
- Mặc định XAMPP **không có mật khẩu** MySQL. File `dbConnection.php` đã được set sẵn `$db_password = "";`, nên thường bạn **không cần sửa gì**.
- *Nếu máy bạn có cài pass MySQL riêng, hãy mở file đó lên và sửa.*

### Bước 5: Chạy dự án!
Bây giờ mọi thứ đã sẵn sàng:
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
