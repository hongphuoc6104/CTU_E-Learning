# Project Expansion Master Execution Prompt

Copy the prompt below into another AI if you want that AI to execute the full expansion roadmap in order.

```text
Hãy đọc và thực thi đúng theo toàn bộ bộ kế hoạch mở rộng trong thư mục `docs/expansion/` của dự án.

Thứ tự đọc và thực thi bắt buộc:
1. `docs/expansion/PROJECT_EXPANSION_PLAN_INDEX.md`
2. `docs/expansion/completed/PROJECT_EXPANSION_PLAN_01_DOMAIN_AND_SCHEMA.md`
3. `docs/expansion/completed/PROJECT_EXPANSION_PLAN_02_STOREFRONT_CART_PAYMENT.md`
4. `docs/expansion/completed/PROJECT_EXPANSION_PLAN_03_LEARNING_EXPERIENCE.md`
5. `docs/expansion/completed/PROJECT_EXPANSION_PLAN_04_INSTRUCTOR_AND_LIVE_CLASS.md`
6. `docs/expansion/completed/PROJECT_EXPANSION_PLAN_05_ADMIN_OPERATIONS.md`
7. `docs/expansion/release/PROJECT_EXPANSION_PLAN_06_MIGRATION_TESTING_AND_ROLLOUT.md`

Đây là expansion wave cho sản phẩm.
Sản phẩm vẫn là web bán khóa học.
Bên trong khóa học sẽ có nhiều loại nội dung học thay vì video cố định.

Mục tiêu cuối cùng:
- giữ dự án là course-commerce website
- thêm multi-content learning
- thêm Instructor
- thêm live class + replay URL
- thêm order/payment lifecycle có trạng thái
- thêm admin approval và payment verification

Stack bắt buộc phải giữ nguyên:
- PHP + MySQL
- HTML + CSS + Tailwind CSS + Vanilla JavaScript
- server-rendered PHP

Ràng buộc bắt buộc:
1. Không reintroduce Bootstrap, jQuery, Popper, Owl Carousel
2. Không chuyển sang framework/backend khác
3. Không làm real payment gateway
4. Không làm internal livestream engine
5. Live class phải dùng external meeting link
6. Replay sau buổi học phải dùng recording URL
7. Không phá các flow hiện đang chạy ổn
8. Không rewrite big-bang toàn bộ dự án
9. Phải làm theo plan order, không nhảy lung tung
10. Nếu gặp blocker lớn, ghi rõ blocker rồi dừng mở rộng scope

Quy tắc seed data bắt buộc:
1. Phải tạo tối thiểu 10 bộ dữ liệu giả hoàn chỉnh mặc định trong SQL
2. Tất cả seed data phải nằm trong SQL bootstrap/import mặc định
3. Seed data phải chỉnh chu, có nghĩa, dùng được để test/demo ngay
4. Không tạo seed dữ liệu hời hợt chỉ để đủ số lượng
5. Seed data phải phủ các case:
   - published course
   - blended/live course
   - draft course
   - pending review course
   - student chưa mua gì
   - student có cart chưa thanh toán
   - student đã thanh toán và đang học dở
   - student đã hoàn thành course và pass quiz
   - order chờ xác minh thanh toán
   - order failed/rejected/cancelled

Nguyên tắc thực thi:
- Thực hiện từng plan một, theo đúng thứ tự
- Không cố làm tất cả trong một patch khổng lồ nếu không cần
- Sau mỗi plan:
  - báo cáo plan hiện tại
  - file đã sửa
  - test đã chạy
  - blocker nếu có
  - còn thiếu gì để chuyển sang plan tiếp theo
- Chỉ sang plan tiếp theo khi plan hiện tại đã đủ ổn định

Ưu tiên nghiệp vụ phải giữ đúng:
- Đây vẫn là website bán khóa học
- Student chỉ học course khi đã được cấp quyền hợp lệ
- Instructor tạo và quản lý course/content/live session
- Admin review/publish course và verify payment
- Live session là một phần của khóa học, không phải sản phẩm riêng
- Replay xuất hiện sau buổi học kết thúc khi có recording URL

Khi bắt đầu, hãy làm đúng theo format sau:
- Current plan file
- Objective
- Files expected to change
- Dependencies checked
- Seed-data impact

Khi kết thúc mỗi plan, báo theo format:
- Completed plan
- Business rules implemented
- Files changed
- SQL changes
- Seed data added/updated
- Tests run
- Remaining risks
- Ready for next plan: yes/no

Quan trọng:
- Không bỏ qua phần seed data
- Không coi seed data là việc phụ
- SQL mặc định sau cùng phải đủ để import và demo/test hầu hết các tính năng mới ngay lập tức
```
