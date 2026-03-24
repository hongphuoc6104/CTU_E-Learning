# Ke hoach cap nhat UX/UI

## Muc tieu

- Giu nguyen mau sac chu dao hien tai.
- Tang do de dung tren mobile va desktop.
- Giam bo cuc roi, table day, thao tac thua va CTA khong ro rang.
- Uu tien cac flow anh huong truc tiep den chuyen doi: tim khoa hoc, xem chi tiet, mua khoa hoc, hoc bai.

## Trang thai thuc thi

Trang thai hien tai cua repo:

- Ke hoach nay da duoc thuc thi xong o muc do functional completion cho current wave.
- Cac man uu tien cao tren public/student da duoc refresh theo huong mobile-first va CTA ro rang hon.
- Cac man trung binh cho homepage, My Courses, admin dashboard/payments, instructor courses/add course da duoc nang cap giao dien de dong bo hon voi flow moi.
- Ke hoach nay nen duoc xem la da hoan thanh cho dot cap nhat UX/UI chinh, khong con la mot feature lane doc lap.

Nhung gi da dat duoc trong runtime:

- Header public va student da co mobile menu, active state va dropdown theo click/tap.
- Login/signup da co show-hide password, validation/feedback va hierarchy ro rang hon.
- Catalog khoa hoc da co search/sort, card dong nhat hon va empty state.
- Course details da co CTA ro rang, lesson accordion/checklist va sticky CTA tren mobile.
- Cart/order details da duoc tach layout, lam ro summary, status, QR, upload proof va next step.
- Learning page va My Courses da uu tien progress, next action va mobile navigation.
- Admin/instructor da duoc lam gon layout, them navigation/drawer/quick actions o muc do du dung cho current flow.

Pham vi con lai chi nen xem la polish khong blocker:

- Vi du mot so action cu van con native `confirm()`/reload page toan bo.
- Mot so table/admin-instructor layout co the tiep tuc toi uu them neu can polish sau cung.
- Cac micro-interaction nho, animation va helper text bo sung co the bo sung sau, khong can reopen plan nay nhu mot workstream lon.

## Nguyen tac thuc hien

- Khong doi palette chinh; chi chuan hoa cach dung mau cho cac trang thai `default`, `hover`, `active`, `disabled`, `error`.
- Uu tien sua bo cuc, typography, spacing, hierarchy, form, empty state, responsive, sticky CTA.
- Sua theo user flow thay vi sua tung man hinh roi rac.
- Uu tien mobile-first cho cac man public, student va learning.

## Thu tu uu tien

### Uu tien cao

1. `ELearning/mainInclude/header.php`
2. `ELearning/Student/stuInclude/header.php`
3. `ELearning/courses.php`
4. `ELearning/coursedetails.php`
5. `ELearning/Student/myCart.php`
6. `ELearning/Student/orderDetails.php`
7. `ELearning/Student/watchcourse.php`
8. `ELearning/login.php`
9. `ELearning/signup.php`

### Uu tien trung binh

1. `ELearning/index.php`
2. `ELearning/Student/myCourse.php`
3. `ELearning/Admin/adminDashboard.php`
4. `ELearning/Admin/payments.php`
5. `ELearning/Instructor/courses.php`
6. `ELearning/Instructor/addCourse.php`

### Uu tien thap

1. Cac man admin/instructor con lai co tinh dong bo layout.
2. Cac polish nho ve animation, micro-interaction, helper text bo sung.

## Phase 0 - Audit va chot rule nen

### Viec can cap nhat

- Lap danh sach user flow chinh: trang chu -> danh sach khoa hoc -> chi tiet khoa hoc -> gio hang -> thanh toan -> vao hoc.
- Chup before/after cho cac man uu tien cao.
- Chot mini UI guideline de team sua dong nhat.
- Chot rule cho spacing, button, input, card, table, empty state, toast, modal, sticky action.

### Dau ra can co

- 1 tai lieu audit UX/UI.
- 1 checklist man hinh theo muc do uu tien.
- 1 mini design guideline de tranh sua lech tay.

## Phase 1 - Foundation va quick wins

### 1. Header va navigation

#### File can cap nhat

- `ELearning/mainInclude/header.php`
- `ELearning/Student/stuInclude/header.php`

#### Bat buoc cap nhat

- Them mobile menu thay cho nav dang bi an tren man nho.
- Them active state cho menu dang duoc chon.
- Chuyen account dropdown tu hover sang click/tap.
- Bo sung page title/phu de/breadcrumb cho cac man quan trong.
- Chuan hoa kich thuoc, khoang cach va alignment cua nav action.

### 2. Form va feedback state

#### File can cap nhat

- `ELearning/login.php`
- `ELearning/signup.php`
- Cac form lien quan o student/admin/instructor khi can dong bo.

#### Bat buoc cap nhat

- Them show/hide password.
- Them inline validation va hien thi loi sat input.
- Chuan hoa label, placeholder, helper text, message loi.
- Lam ro nut chinh, nut phu, trang thai loading, disabled, success.

### 3. State va thong bao

#### File can cap nhat

- `ELearning/Student/myCart.php`
- Cac man co xoa/cap nhat du lieu bang `alert()` hoac `confirm()`.

#### Bat buoc cap nhat

- Bo `alert()`/`confirm()` kieu cu.
- Thay bang inline feedback, toast, confirm modal nhe.
- Them empty state co CTA ro rang.

## Phase 2 - Toi uu flow tim va chon khoa hoc

### 1. Trang chu

#### File can cap nhat

- `ELearning/index.php`

#### Bat buoc cap nhat

- Lam ro 1 CTA chinh trong hero.
- Sap xep lai section theo thu tu thuyet phuc: gia tri -> khoa hoc noi bat -> loi ich -> feedback.
- Giam mat do thong tin o cac block ngang nhau.
- Chuan hoa section spacing de trang de doc hon.

### 2. Danh sach khoa hoc

#### File can cap nhat

- `ELearning/courses.php`

#### Bat buoc cap nhat

- Them search, filter, sort.
- Chuan hoa card khoa hoc: anh, tieu de, meta, gia, CTA.
- Dua CTA ve vi tri dong nhat giua cac card.
- Toi uu grid tren mobile/tablet.
- Bo sung empty state cho truong hop khong co ket qua.

### 3. Chi tiet khoa hoc

#### File can cap nhat

- `ELearning/coursedetails.php`

#### Bat buoc cap nhat

- Giu CTA mua/dang ky o vi tri de thay va de bam.
- Chuyen danh sach bai hoc tu table sang accordion/checklist.
- Lam ro gia tri khoa hoc, doi tuong phu hop, nhung gi nguoi hoc nhan duoc.
- Tach khoi thong tin phuc vu quyet dinh mua voi thong tin tham khao.
- Them sticky CTA tren mobile hoac sticky summary tren desktop.

## Phase 3 - Toi uu cart va payment

### 1. Gio hang

#### File can cap nhat

- `ELearning/Student/myCart.php`

#### Bat buoc cap nhat

- Chia layout 2 cot tren desktop: danh sach trai, tong ket phai.
- Lam summary sticky khi cuon.
- Them sticky CTA tren mobile.
- Xoa san pham ma khong reload cung toan trang.
- Lam ro tong tien, hanh dong tiep theo va CTA thanh toan.

### 2. Thanh toan va chi tiet don

#### File can cap nhat

- `ELearning/Student/orderDetails.php`

#### Bat buoc cap nhat

- Chia flow thanh cac buoc ro rang: tao don -> chuyen khoan -> tai minh chung -> cho duyet.
- Tach rieng block thong tin ngan hang, QR, upload proof, trang thai don.
- Hien thi trang thai tien trinh de nguoi dung biet dang o buoc nao.
- Giam text day, tang huong dan hanh dong tiep theo.

## Phase 4 - Toi uu trai nghiem hoc tap

### 1. Trang hoc bai

#### File can cap nhat

- `ELearning/Student/watchcourse.php`

#### Bat buoc cap nhat

- Tren mobile, uu tien noi dung bai hoc truoc.
- Chuyen syllabus thanh panel thu gon/collapse.
- Lam noi progress va nut bai tiep theo.
- Giam thao tac danh dau hoan thanh thu cong neu khong can thiet.
- Toi uu hierarchy giua video, tai lieu, bai viet va progress.

### 2. My Courses

#### File can cap nhat

- `ELearning/Student/myCourse.php`

#### Bat buoc cap nhat

- Phan tach ro khoa dang hoc, chua bat dau, da hoan thanh.
- Them progress bar, bai hoc gan nhat, CTA `Tiep tuc hoc`.
- Uu tien hien khoa hoc dang hoc tren cung.

## Phase 5 - Toi uu admin va instructor

### 1. Admin

#### File can cap nhat

- `ELearning/Admin/adminInclude/header.php`
- `ELearning/Admin/adminDashboard.php`
- `ELearning/Admin/payments.php`

#### Bat buoc cap nhat

- Them sidebar drawer cho mobile/tablet.
- Giam phu thuoc vao table day dac.
- Chuyen action quan trong sang detail panel/modal thay vi nhieu nut trong cung mot row.
- Lam ro thong tin uu tien tren dashboard theo workflow.

### 2. Instructor

#### File can cap nhat

- `ELearning/Instructor/instructorInclude/header.php`
- `ELearning/Instructor/courses.php`
- `ELearning/Instructor/addCourse.php`

#### Bat buoc cap nhat

- Giam scroll ngang o man danh sach khoa hoc.
- Chuyen table sang card/list responsive tren man nho.
- Chia form tao khoa hoc thanh cac nhom ro rang: thong tin co ban, pricing, delivery, image.
- Bo sung helper text, preview, checklist truoc khi submit.

## Checklist thuc thi de doi dev/design bam vao

### Foundation

- [ ] Chot spacing scale, card pattern, button pattern, input pattern.
- [ ] Chot rule cho active, hover, disabled, error, success.
- [ ] Chot pattern cho empty state, toast, modal, sticky CTA.

### Public va student

- [ ] Them mobile menu cho public header.
- [ ] Them mobile menu cho student header.
- [ ] Them active state cho navigation.
- [ ] Them breadcrumb/page title cho man quan trong.
- [ ] Chuan hoa login/signup form.
- [ ] Nang cap card khoa hoc tren homepage va catalog.
- [ ] Them search/filter/sort tren catalog.
- [ ] Toi uu trang chi tiet khoa hoc.
- [ ] Toi uu gio hang.
- [ ] Toi uu payment flow.
- [ ] Toi uu learning page mobile-first.
- [ ] Toi uu My Courses.

### Admin va instructor

- [ ] Responsive hoa sidebar/drawer.
- [ ] Giam table day va action roi.
- [ ] Toi uu dashboard theo workflow.
- [ ] Toi uu danh sach khoa hoc cua instructor.
- [ ] Toi uu form tao khoa hoc.

## Moc thoi gian de xuat

- Tuan 1: Audit + mini UI guideline + navigation + form + state.
- Tuan 2: Homepage + course list + course details.
- Tuan 3: Cart + payment flow.
- Tuan 4: Learning page + My Courses.
- Tuan 5: Admin + Instructor + polish responsive.

## Tieu chi nghiem thu

- Moi man chinh co 1 CTA chinh ro rang.
- Mobile co menu su dung duoc va khong mat dieu huong.
- Form co validation, helper text va state day du.
- Card khoa hoc khong vo layout tren man nho.
- Checkout co flow tung buoc, de hieu.
- Trang hoc uu tien noi dung hoc truoc, dieu huong bai hoc sau ro rang.
- Admin/instructor dung duoc tren tablet/mobile ma khong phai scroll ngang qua nhieu.

## Ghi chu

- Khong can doi mau chu dao de nang cap UX/UI.
- Neu can doi visual, uu tien doi hierarchy, spacing, pattern va interaction truoc khi nghi den doi palette.
