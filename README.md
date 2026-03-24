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
| **Front-end UI** | HTML, CSS, [Tailwind CSS](https://tailwindcss.com/) (build ra static CSS asset) | `ELearning/css/`, `tailwind.config.js`, `package.json` |
| **Logic Frontend**| Vanilla JavaScript (`fetch`, DOM APIs) | `ELearning/js/ajaxrequest.js`, `ELearning/js/adminajaxrequest.js` |
| **Back-end** | PHP 8.3+ (server-rendered, prepared statements, CSRF/session hardening) | Toàn bộ các file `.php` |
| **Cơ sở dữ liệu** | MySQL / MariaDB | File `SQL/lms_db.sql` |
| **Web Server** | Apache (XAMPP) | |

---

## 🏁 Hướng dẫn cài đặt chi tiết cho người mới

Để chạy được website này trên máy tính của bạn (Localhost), hãy làm theo từng bước sau. Đảm bảo bạn làm đúng thứ tự.

### Bước 1: Chuẩn bị môi trường (Cài XAMPP)
1. Tải phần mềm **XAMPP** từ [apachefriends.org](https://www.apachefriends.org/) (khuyến nghị dùng nhánh PHP 8.3+ để khớp target dự án).
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
7. Cuộn xuống dưới cùng, nhấn **Go** (Thực hiện).
8. File `SQL/lms_db.sql` đã bao gồm toàn bộ bảng bắt buộc (`cart`, `contact_message`, các cột `is_deleted`...), nên **không cần import thêm file SQL thứ hai**.

### Bước 3.1 (tuỳ chọn): kiểm tra nhanh seed-data cho lane Plan 06
Nếu bạn đang chạy lane chuẩn bị migration/testing/rollout (Plan 06), có thể chạy thêm bộ query kiểm tra trong file `SQL/plan06_seed_validation_checks.sql`:

1. Mở tab **SQL** trong phpMyAdmin sau khi đã import xong `SQL/lms_db.sql`.
2. Copy nội dung từ `SQL/plan06_seed_validation_checks.sql` và chạy.
3. Xem các dòng `PASS`/`FAIL` để xác nhận độ phủ seed-data cho regression matrix.

Lưu ý: bộ check này phục vụ chuẩn bị rollout và **không thay thế** kiểm thử chức năng end-to-end.

### Bước 4: Kiểm tra File cấu hình kết nối
Hệ thống cần biết mật khẩu MySQL của máy bạn để kết nối. File quy định điều này là `ELearning/dbConnection.php`.
- Mặc định XAMPP **không có mật khẩu** MySQL. File `dbConnection.php` đã được set sẵn `$db_password = "";`, nên thường bạn **không cần sửa gì**.
- *Nếu máy bạn có cài pass MySQL riêng, hãy mở file đó lên và sửa.*

### Bước 4.1: Kiểm tra quyền ghi cho các thư mục upload
Để website chạy ổn trên **Windows + XAMPP** khi chuyển qua máy khác, bạn cần đảm bảo Apache có thể ghi file mới vào các thư mục upload công khai sau:

- `ELearning/image/courseimg/`  → ảnh khoá học
- `ELearning/image/stu/` → avatar học viên
- `ELearning/image/paymentproof/` → minh chứng thanh toán
- `ELearning/lessonvid/` → video bài học upload trực tiếp

Ghi chú quan trọng:

- Dự án đã có cơ chế **tự tạo thư mục nếu thiếu** và **kiểm tra quyền ghi trước khi upload**.
- Trên **Windows + XAMPP**, nếu bạn giải nén/copy project trong thư mục `htdocs` của user đang chạy XAMPP thì thường không cần chỉnh thêm quyền thủ công.
- Nếu upload vẫn lỗi, hãy kiểm tra:
  - thư mục có thật sự tồn tại không
  - file có đang bị Windows chặn quyền ghi không
  - Apache/XAMPP có đang chạy bằng user có quyền ghi vào `htdocs` hay không

Mục tiêu cuối cùng là khi chuyển dự án sang máy Windows khác, các luồng sau vẫn phải hoạt động bình thường:

- upload ảnh khoá học
- upload avatar học viên
- upload minh chứng thanh toán
- upload video bài học
- tạo dữ liệu mới và ghi thành công vào MySQL

### Bước 5: Chạy dự án!
Bây giờ mọi thứ đã sẵn sàng:
- **Trang chủ học viên**: Truy cập vào `http://localhost/ELearning/`
- **Trang quản trị (Admin)**: Truy cập vào `http://localhost/ELearning/Admin/`

### Bước 6: Build Tailwind CSS (bắt buộc cho trạng thái chuẩn)
Sau khi clone hoặc pull code mới, build lại file CSS tĩnh của Tailwind:

1. Mở terminal tại thư mục gốc dự án (nơi có `package.json`).
2. Cài dependency build (chỉ lần đầu):
   ```bash
   npm install
   ```
3. Build file CSS:
   ```bash
   npm run tailwind:build
   ```
4. Nếu đang phát triển giao diện và muốn tự build khi sửa file:
   ```bash
   npm run tailwind:watch
   ```

File output chính là: `ELearning/css/tailwind.css`.

## 🐳 Chạy bằng Docker (tuỳ chọn)

- `Dockerfile` hiện được cấu hình theo `php:7.4-apache`, không khớp target `PHP 8.3+` của dự án.
- Để đúng chuẩn stack mục tiêu, ưu tiên chạy bằng môi trường local PHP 8.3+ hoặc cập nhật image Docker lên nhánh PHP 8.3 trước khi dùng cho môi trường chính.

---

## 🔑 Tài khoản đăng nhập mẫu

Thay vì phải tạo lại từ đầu, bạn có thể dùng các tài khoản đã có sẵn trong cơ sở dữ liệu để test. Seed mặc định trong `SQL/lms_db.sql` hiện bao gồm các nhóm dữ liệu cho published course, blended/live course, draft, pending review, cart chưa thanh toán, order chờ xác minh, order failed/cancelled và học viên đang học dở/đã hoàn thành:

| Vai trò | Email đăng nhập | Mật khẩu | Chức năng |
|---|---|---|---|
| **Quản trị viên** | `admin@gmail.com` | `admin` | Tài khoản admin chính để kiểm tra dashboard, quản lý khóa học và dữ liệu mẫu commerce. |
| **Quản trị viên** | `operations.admin@example.com` | `admin` | Tài khoản admin phụ, phù hợp để test luồng xác minh thanh toán thủ công ở các plan sau. |
| **Instructor seed** | `chau.instructor@example.com` | `instructor123` | Giảng viên sở hữu khóa Figma đã published và một phần nội dung chờ review. |
| **Instructor seed** | `long.live@example.com` | `instructor123` | Giảng viên phụ trách khóa blended/live có live session và replay. |
| **Instructor seed** | `ngoc.creator@example.com` | `instructor123` | Giảng viên có khóa draft và các khóa self-paced phục vụ demo seed data. |
| **Học viên mẫu** | `cap@example.com` | `123456` | Đã thanh toán khóa Figma, đang học dở và có tiến độ từng learning item. |
| **Học viên mẫu** | `hoan.thanh@example.com` | `123456` | Đã hoàn thành khóa blended/live, có quiz attempt pass và replay history. |
| **Học viên mẫu** | `lan.cart@example.com` | `123456` | Có cart chưa thanh toán và pending order để test case unpaid. |
| **Học viên mẫu** | `xacminh.pay@example.com` | `123456` | Có order ở trạng thái `awaiting_verification` và payment `submitted`. |
| **Học viên mẫu** | `rejected.order@example.com` | `123456` | Có order failed/rejected và một order cancelled để test vòng đời commerce. |
| **Học viên mẫu** | `thao.live@example.com` | `123456` | Đã mua khóa live bootcamp, có enrollment và attendance dữ liệu mẫu. |
| **Học viên mẫu** | `thanh.multi@example.com` | `123456` | Đã mua nhiều khóa trong một order để test multi-course enrollment. |

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
├── css/ & js/                # Tài nguyên giao diện Tailwind + script Vanilla JS (không dùng Bootstrap/jQuery/Owl trong trạng thái hoàn tất)
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
4. Với các luồng upload mới hơn, hệ thống sẽ kiểm tra sẵn khả năng ghi thư mục trước khi gọi `move_uploaded_file()` để giảm lỗi khi deploy qua máy Windows/XAMPP khác.

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

- **Mật khẩu seed** trong `SQL/lms_db.sql` đã được hash bằng `password_hash()` (bcrypt). Bạn vẫn đăng nhập bằng mật khẩu gốc trong bảng "Tài khoản đăng nhập mẫu".
- Dự án dùng **Vanilla JavaScript + Fetch API** (trong `js/ajaxrequest.js`, `js/adminajaxrequest.js`) để xử lý đăng ký/đăng nhập/cart/admin-login mà không cần jQuery.
- Checkout hiện tại là **mô phỏng thanh toán nội bộ** (mock checkout) với dữ liệu đơn hàng xác nhận phía server. Các endpoint Paytm legacy trong `PaytmKit/` đã được vô hiệu hoá để tránh nhầm lẫn với flow hiện tại.
- Các luồng thay đổi dữ liệu chính (giỏ hàng, liên hệ, phản hồi, thao tác xoá/khôi phục trong admin, đổi mật khẩu) đã thêm **CSRF token** để giảm nguy cơ request giả mạo.
- Session được khởi tạo qua `ELearning/session_bootstrap.php` với cookie flag `HttpOnly`, `SameSite=Lax` (và `Secure` khi chạy HTTPS).
- Hệ thống thiết kế các file "Include" (`header.php`, `footer.php`) giúp không phải copy-paste cục mã HTML menu lặp lại ở cả trăm trang. Code tái sử dụng cao.

## 🔒 Quy tắc logic đã harden theo plan

- Quy tắc sở hữu khoá học được chuẩn hoá: chỉ xem là đã sở hữu khi `courseorder.status = 'TXN_SUCCESS'` **và** `courseorder.is_deleted = 0`.
- `My Course`, `Watch Course`, public purchase-state UI và các báo cáo doanh thu admin đều đồng bộ theo quy tắc trên.
- Giỏ hàng và badge đếm giỏ hàng chỉ tính các dòng `cart` chưa xoá và liên kết tới `course` còn active (`course.is_deleted = 0`).
- `studentChangePass.php` đã được chuyển thành route redirect về `studentProfile.php#tab-password` để tránh duy trì 2 flow đổi mật khẩu song song.
- Ở trang quản trị, chỉnh sửa email học viên sẽ đồng bộ transactional sang `courseorder.stu_email` và `cart.stu_email` để tránh orphan dữ liệu.
