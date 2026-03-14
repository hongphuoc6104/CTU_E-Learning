# CTU E-Learning — Hệ thống Quản lý Học trực tuyến

Hệ thống E-Learning dành cho Trường Đại học Cần Thơ (CTU). Ứng dụng web cho phép **quản trị viên (Admin)** quản lý khoá học, bài học, học viên, doanh thu; và **học viên (Student)** đăng ký, mua khoá học, xem video bài giảng, quản lý hồ sơ cá nhân.

Dự án này được thiết kế theo mô hình client-server truyền thống, sử dụng **PHP thuần** cho xử lý backend và **MySQL** để lưu trữ dữ liệu hoàn toàn không sử dụng ngôn ngữ backend hay cơ sở dữ liệu nào khác. Giao diện hoàn toàn được nâng cấp lên **Tailwind CSS**.

---

## 🚀 Tính năng & Luồng hoạt động (Workflow)

Hệ thống được chia làm 3 luồng chính, tương ứng với 3 đối tượng sử dụng:

### 1. Khách viếng thăm (Người chưa có tài khoản)
- **Xem trang chủ:** Xem thông tin giới thiệu, các khoá học nổi bật, và đánh giá (feedback) kết hợp yếu tố hài hước từ học viên cũ.
- **Xem danh sách khoá học:** Xem tất cả khoá học hiện có, xem chi tiết mô tả, giảng viên, giá tiền, thời lượng.
- **Đăng ký / Đăng nhập:** Tạo tài khoản học viên mới để bắt đầu mua khoá học (sử dụng AJAX để hiển thị thông báo lỗi/thành công mà không cần tải lại trang). Sau khi đăng ký, hệ thống tự động gán hình đại diện (avatar AI) cho học viên.

### 2. Học viên (Student - Cần đăng nhập)
*Luồng học viên: Đăng ký → Mua khoá học → Học bài → Đánh giá*
- **Quản lý Giỏ hàng:** Thêm các khoá học muốn học vào giỏ, hệ thống **chặn việc thêm khóa học đã thanh toán** vào giỏ hàng giúp tránh mua nối trùng.
- **Khoá học của tôi:** Nơi hiển thị các khoá học **đã thanh toán thành công**. Nút "Thêm vào giỏ" ở trang chủ cũng được chuyển thành trạng thái **Học ngay/Đã sở hữu** cực kỳ tiện lợi.
- **Học bài (Watch Course):** Giao diện thiết kế theo phong cách rạp phim (dark cinema-style) với **Playlist bài học** bên trái và **Video Player** bên phải. Nhấn vào bài nào, video bài đó sẽ phát. Hỗ trợ hiển thị video tải lên hệ thống hoặc tự động nhúng Player của **YouTube link**.
- **Hồ sơ cá nhân:** 
  - Cập nhật thông tin (Tên, Nghề nghiệp) và **Ảnh đại diện (Avatar)**.
  - Xem danh sách khoá đã mua, Đổi mật khẩu.
  - Viết Đánh giá (Feedback) để hiển thị lên trang chủ.

### 3. Quản trị viên (Admin - Kiểm soát toàn hệ thống)
*Luồng Admin: Đăng nhập → Quản lý thông tin (Thêm/Sửa/Xoá) → Xem báo cáo doanh thu*
- **Bảng điều khiển (Dashboard):** Xem tổng quan số liệu bằng 4 thẻ thống kê: Tổng khoá học, Tổng học viên, Tổng số giao dịch, Tổng doanh thu. Đi kèm là bảng 10 giao dịch mua khoá học mới nhất.
- **Quản lý Khoá học:** Thêm/Sửa/Xoá khoá học.
- **Quản lý Bài học:** Tính năng thêm bài học hỗ trợ mạnh mẽ cả 2 phương án: **Tải video MP4 lên hệ thống** hoặc **dán đường link YouTube** cực kỳ linh hoạt và thẩm mỹ.
- **Quản lý Học viên:** Xem thông tin, ảnh đại diện, số lượng khoá đã mua của từng học viên. Admin có quyền Xoá học viên khỏi hệ thống.
- **Hộp thư Liên hệ:** Nhận và đọc các thư góp ý/hỗ trợ từ khách truy cập.
- **Báo cáo doanh thu:** Lọc giao dịch mua khoá học theo **Từ ngày - Đến ngày**. Tính tổng doanh thu trong khoảng thời gian đó và hỗ trợ **In báo cáo**.
- **Thùng rác (Thao tác Xóa Mềm):** Tất cả các lệnh xóa trên hệ thống (khoá học, bài học, học viên, đánh giá, giao dịch, liên hệ) đều là "xóa mềm" (chỉ cấp cờ `is_deleted = 1`). Mọi dữ liệu bị xóa sẽ nằm ở Thùng rác để Admin có thể **Khôi phục** lại hoặc **Xóa vĩnh viễn** tùy ý, đảm bảo an toàn dữ liệu 100%.

---

## 🛠 Công nghệ sử dụng

| Thành phần | Công nghệ | File/Thư mục liên quan |
|---|---|---|
| **Front-end UI** | HTML, **[Tailwind CSS](https://tailwindcss.com/)** | `css/`, `js/`, Header/Footer các phần |
| **Logic Frontend**| JavaScript, jQuery (Dùng cho AJAX request, Video Player) | `js/ajaxrequest.js`, `js/adminajaxrequest.js` |
| **Back-end** | **PHP 7+** nguyên bản (Chống SQL Injection) | Toàn bộ các file `.php` |
| **Cơ sở dữ liệu** | **MySQL / MariaDB** | File `SQL/lms_db.sql` |
| **Web Server** | Apache (XAMPP) | |

---

## 🏁 Hướng dẫn cài đặt chi tiết cho người mới

### Sử dụng phần mềm XAMPP

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
4. Nhấn **Choose File**, tìm đến thư mục `SQL/` của dự án và chọn file `lms_db.sql`. Nhấn **Go**. Mọi dữ liệu (bao gồm cả bảng giỏ hàng) đã được gộp chung.

#### Bước 4: Kiểm tra File cấu hình
- File kết nối CSDL hiện tại mặc định username là `root` và mật khẩu để trống `""` cho XAMPP.
- Nếu XAMPP của bạn có đặt mật khẩu MySQL, hãy vào file `ELearning/dbConnection.php` để bổ sung mật khẩu.

#### Bước 5: Chạy dự án!
Mọi thứ đã sẵn sàng:
- **Trang chủ học viên**: Truy cập vào `http://localhost/ELearning/`
- **Trang quản trị (Admin)**: Truy cập vào `http://localhost/ELearning/Admin/`

---

## 🔑 Tài khoản đăng nhập mẫu (Trải nghiệm dự án)

Bạn có thể sử dụng các tài khoản được cung cấp sẵn mặc định dưới đây để trực tiếp trải nghiệm mọi quyền và tính năng của hệ thống (bảng `admin` và bảng `student`):

| Vai trò | Tên người dùng | Email đăng nhập | Mật khẩu | Chức năng (Đã có sẵn data) |
|---|---|---|---|---|
| **Quản trị viên (Admin)** | Admin Kumar | `admin@gmail.com` | `admin` | Toàn quyền thao tác trên Dashboard, thêm/xóa bài học youtube, khoá học, xem doanh thu... |
| **Học viên mẫu (Đã mua khóa học)** | Captain Marvel | `cap@example.com` | `123456` | Đã mua sẵn khóa học trong tài khoản, có thể test ngay giao diện "Khoá học của tôi" và màn hình xem video |
| **Học viên mẫu 2** | Ant Man | `ant@example.com` | `123456` | Trải nghiệm luồng bắt đầu mua hàng của Học viện |
| **Học viên mẫu 3** | Dr Strange | `doc@example.com` | `123456` | Trải nghiệm cập nhật lại feedback |

---

## 📖 Hướng dẫn Sử dụng Website (Toàn tập)

***Dành cho Học viên (Student)***  
1. **Khám phá và Thêm giỏ hàng:** Từ Trang chủ, hãy kéo xuống "Khóa học nổi bật" hoặc nhấp vào nút "Khóa học" trên thanh điều hướng để truy cập danh sách đầy đủ. Tại đây, bạn có thể bấm "Chi tiết" để xem mô tả. Để mua hàng, nhấp vào biểu tượng xe đẩy (Giỏ hàng) trực tiếp trên góc ảnh bìa thẻ khóa học. Hệ thống sẽ cấm việc bạn bỏ những khóa đã thanh toán rồi vào giỏ hàng giúp chống lỗi mua nhầm!
2. **Thanh toán:** Để xem đồ, nhấn nút giỏ trên thanh Navbar và ấn **Thanh toán**. Hệ thống đã được tích hợp mô phỏng thanh toán thành công (checkout success bypass), thao tác trên Web sẽ cập nhật trạng thái ngay sau đó.
3. **Tiến hành học tập:** Khi đã có khóa học, nút mua hàng sẽ bị ẩn đi thay vào đó là nút `Học ngay`. Truy cập phần `Hồ sơ -> Khóa học của tôi` -> Chọn khóa và ấn `Học ngay`. Màn hình trình chiếu (Cinema Mode) sẽ được kích hoạt cùng dãy **Playlist tự động chuyển bài** ngay khi xem hết đoạn phim, trải nghiệm vô cùng tinh tế.
4. **Bình luận & Góp ý:** Hãy vào phần `Hồ sơ cá nhân -> Đánh giá của tôi` để viết một nhận xét dí dỏm hoặc nghiêm túc. Phản hồi mới nhất sẽ cập nhật liên tục tại bảng vàng Trang chủ.

***Dành cho Quản trị viên (Admin)***
1. **Nhập mới Khóa học:** Tại trang Admin (Sidebar), chọn mục "Khóa học" > Bấm nút `+ Thêm khóa học mới`. Bạn điền đầy đủ tiêu đề, mô tả, ấn định giá và tải thumbnail lên, website sẽ tự quản lý phần dọn đường dẫn tĩnh. 
2. **Thêm Video Bài giảng (Hỗ trợ Youtube và Truyền thống MP4):** Tại mục "Bài học", bạn sẽ tìm kiếm ID khóa học đã tạo từ trước. Tại đây, nhấn "Thêm bài mới". Nền tảng đem đến 2 giao diện song song để linh hoạt tài nguyên: bạn hoàn toàn có thể chọn **Tab Upload Video local MP4** nếu file sẵn trên máy tính, hoặc nhúng nhanh bằng **Link dẫn Youtube** nhằm mở tối đa hiệu suất máy chủ.
3. **In Doanh thu Thực tế:** Tìm đến Tab "Doanh thu" trên bảng điều khiển. Admin chỉ việc quét chọn lịch `Từ ngày` - `Đến ngày`. Nhấn lọc để hiện chi tiết từng giao dịch thành công. Tiếp theo ấn lệnh `In báo cáo`, toàn bộ UI thừa sẽ tạm mờ và in ra bản in (Print mode) sạch sẽ nhất dạng bảng tài chính truyền thống. 

---

## 📂 Giải thích Cấu trúc Thư mục

Việc chia nhỏ thư mục giúp code dễ quản lý và bảo mật hơn (Tách biệt quyền Admin và Học viên).

```text
ELearning/                    ← THƯ MỤC GỐC (Trang Public)
├── index.php                 # Trang chủ hiển thị banner, khoá học nổi bật
├── coursedetails.php         # Chi tiết 1 khoá học (Giá, giảng viên, hiển thị trạng thái đã mua)
├── login.php, signup.php     # Form đăng nhập, đăng ký
├── checkout.php              # Trang thanh toán
├── dbConnection.php          # File duy nhất chứa cấu hình kết nối MySQL -> Rất quan trọng!
│
├── Admin/                    ← KHU VỰC QUẢN TRỊ (Kiểm tra session Admin)
│   ├── adminDashboard.php    # Bảng số liệu tổng quan
│   ├── courses.php, editcourse.php # Quản lý khoá học (Thêm/Sửa/Xoá)
│   ├── addLesson.php         # Quản lý file/link video bài giảng (chia Tab UI rất hiện đại)
│   ├── students.php          # Quản lý danh sách học viên
│   ├── sellReport.php        # Lọc giao dịch theo ngày, tính doanh thu
│   ├── adminInclude/         # Header/Footer của Admin
│   └── ...
│
├── Student/                  ← KHU VỰC HỌC VIÊN (Kiểm tra session Học viên)
│   ├── myCourse.php          # Liệt kê khoá học đã thanh toán
│   ├── watchcourse.php       # TRANG QUAN TRỌNG: Trình chiếu MP4/YouTube Video Player
│   ├── studentProfile.php    # Quản lý thông tin Avatar
│   ├── myCart.php            # Giỏ hàng
│   ├── stuInclude/           # Header/Footer riêng cho Học viên
│   └── ...
│
├── mainInclude/              # Header và Footer dùng chung với Tailwind UI
├── css/ & js/                # CSS tuỳ biến và logic js AJAX, Fontawesome Icons
├── image/                    # Thư mục lưu ẢNH UPLOAD. (`courseimg/`, `stu/`)
├── lessonvid/                # Cất giữ các file MP4 bài giảng mẫu nội bộ.
└── SQL/                      # (File ngoài) Chứa file cấu trúc Database để export/import.
```

---

## 📸 Xử lý ảnh tải lên (Upload Paths)

Một trong những phần dễ lỗi nhất của dự án là đường dẫn ảnh. Dự án áp dụng quy tắc sau:

1. Khi Admin thêm hình Khoá học, file gốc sẽ được copy bằng PHP (`move_uploaded_file`) vào thư mục vật lý hệ thống: `ELearning/image/courseimg/tên_file_mới.jpg`
2. Tương tự, Học viên tải Avatar sẽ vào: `ELearning/image/stu/tên_file_mới.jpg`
3. Lúc đăng ký hệ thống gán mặc định AI avatar cho user, vào thư mục tương đối `image/stu/`
4. Đường dẫn lưu vào Database là **đường dẫn gốc tương đối**: `../image/...` hoặc `image/...`. Điều này giúp hệ thống tương thích sâu nhất.

---

## ⚠️ Cấu trúc Database (`lms_db`)

| Tên Bảng | Đặc điểm & Vai trò |
|---|---|
| `admin` | Lưu TK Quản trị (`admin_email`, `admin_pass`). |
| `student` | Lưu TK Học viên (`stu_email`, `stu_pass`, `stu_img` lưu avatar). |
| `course` | Thông tin 1 khoá học (`course_name`, `course_price` là giá bán thực tế, `course_img`). |
| `lesson` | Bài giảng con. Bắt buộc có `course_id` để biết nó thuộc Khoá học nào. `lesson_link` chứa URL video nội bộ hoặc cả URL YouTube. |
| `cart` | Giỏ hàng tạm thời, lưu `stu_email` gắn với `course_id`. Logic đã chặn việc lỡ tay nhúng lại khoá học đã mua vào cart. |
| `courseorder`| Giao dịch THÀNH CÔNG. Dùng để tính Doanh thu bên Admin và khóa các action "Thêm giỏ hàng", chuyển ngay sang "Học Ngay" phía Student. |
| `feedback` | Nhận xét của học viên. Link với `student` thông qua `stu_id` để hiện Tên người gửi lên trang chủ. Các feedback nay được viết theo lối hài hước đặc trưng. |
| `contact_message` | Thư liên hệ/hỗ trợ từ khách ghé thăm website gửi lên. Admin có thể xem được trong phần "Hộp thư liên hệ". |

*Lưu ý: Tất cả các bảng trên (trừ `admin`) đều có trường `is_deleted` để xử lý tính năng Xóa mềm (Gửi vào Thùng rác).*

---

## 📌 Ghi chú Bảo mật & Kỹ thuật

- **Mật khẩu** hiện tại đang lưu dưới dạng text thô nhằm mục đích thực hành cho người mới dễ quan sát database. Ở môi trường thực tế cần áp dụng hashing (VD: `password_hash()`).
- Tách biệt CSS bằng Framework **Tailwind CSS** hỗ trợ giao diện bóng bẩy, responsive, hover chuẩn và mượt mà hơn rất nhiều mà không bị xung đột.
- Ngôn ngữ cốt lõi sử dụng tuyệt đối 100% là **PHP**, không cài chéo với backend phụ nào khác, kết hợp Ajax để làm mượt các action (Không tải lại website).
