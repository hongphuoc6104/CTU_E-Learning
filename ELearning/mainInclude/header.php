<?php
require_once(__DIR__ . '/../session_bootstrap.php');
secure_session_start();
require_once(__DIR__ . '/../csrf.php');
require_once(__DIR__ . '/../commerce_helpers.php');
$csrfToken = csrf_token();

// Detect current page for active nav state
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'nav-active' : '';
}
?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
    
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" type="text/css" href="css/all.min.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">

    <!-- Compiled Tailwind CSS -->
    <link rel="stylesheet" type="text/css" href="css/tailwind.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>

    <style>
        .glass-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .hero-pattern {
            background-color: #f5f7f8;
            background-image: radial-gradient(#00336610 1px, transparent 1px);
            background-size: 20px 20px;
        }
        a:hover { text-decoration: none; }

        /* Active nav state */
        .nav-active {
            color: #003366 !important;
            font-weight: 700 !important;
            position: relative;
        }
        .nav-active::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            right: 0;
            height: 2px;
            background: #003366;
            border-radius: 1px;
        }

        /* Mobile menu overlay */
        .mobile-menu-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 60;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .mobile-menu-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* Mobile menu panel */
        .mobile-menu-panel {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 280px;
            max-width: 85vw;
            background: #fff;
            z-index: 70;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: -4px 0 30px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        .mobile-menu-panel.active {
            transform: translateX(0);
        }

        /* Mobile nav active */
        .mobile-nav-active {
            background: rgba(0, 51, 102, 0.06) !important;
            color: #003366 !important;
            font-weight: 700 !important;
            border-left: 3px solid #003366;
        }

        /* User dropdown click-based */
        .user-dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            padding-top: 8px;
            width: 200px;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-4px);
            transition: all 0.2s ease;
            z-index: 50;
        }
        .user-dropdown-menu.open {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
    </style>

    <!-- Custom Style CSS -->
    <link rel="stylesheet" type="text/css" href="./css/style.css" />
    <title>CTU E-Learning - Truyền thông Đa phương tiện</title>
  </head>
  <body class="font-display bg-background-light text-slate-900">
     <!-- Start Navigation -->
     <header class="fixed top-0 w-full z-50 glass-header border-b border-primary/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between">
            <div class="flex items-center gap-6 lg:gap-10">
                <a href="index.php" class="flex items-center gap-2 sm:gap-3 hover:opacity-80 transition-opacity">
                    <div class="text-primary">
                        <i class="fas fa-graduation-cap text-2xl sm:text-4xl font-bold"></i>
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold tracking-tight text-primary m-0">CTU E-Learning</h1>
                </a>
                <nav class="hidden md:flex items-center gap-6 lg:gap-8">
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors relative <?php echo isActive('index.php'); ?>" href="index.php">Trang chủ</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors relative <?php echo isActive('courses.php'); ?>" href="courses.php">Khóa học</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors relative" href="Instructor/instructorLogin.php">Giảng viên</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors relative" href="index.php#Feedback">Góp ý</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors relative <?php echo isActive('contact.php'); ?>" href="index.php#Contact">Liên hệ</a>
                </nav>
            </div>
            <div class="flex items-center gap-4 sm:gap-6">
                <?php 
                    if (isset($_SESSION['is_login'])){
                        $stuEmail = $_SESSION['stuLogEmail'];
                        $stuImg = "";
                        $stuName = "Học viên";
                        $stmtAvatar = $conn->prepare('SELECT stu_img, stu_name FROM student WHERE stu_email = ? AND is_deleted = 0 LIMIT 1');
                        if ($stmtAvatar) {
                            $stmtAvatar->bind_param('s', $stuEmail);
                            $stmtAvatar->execute();
                            $resAvatar = $stmtAvatar->get_result();
                            if ($resAvatar && $resAvatar->num_rows > 0) {
                                $rowAvatar = $resAvatar->fetch_assoc();
                                $stuImg = $rowAvatar['stu_img'];
                                $stuName = $rowAvatar['stu_name'];
                            }
                            $stmtAvatar->close();
                        }
                        if(empty($stuImg)){
                            $stuImg = 'image/stu/default_avatar.png';
                        } else {
                            $stuImg = ltrim(str_replace('../', '', $stuImg), '/');
                        }

                        echo '<a href="Student/myCart.php" class="relative text-slate-700 hover:text-primary transition-colors pr-4"><i class="fas fa-shopping-cart text-lg sm:text-xl"></i><span class="absolute -top-1 -right-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" id="cartCount">0</span></a>';
                        
                        echo '<div class="relative" id="userDropdownContainer">';
                        echo '<button class="flex items-center gap-2 focus:outline-none cursor-pointer border-0 bg-transparent p-0 m-0" id="userDropdownBtn" aria-expanded="false" aria-haspopup="true">';
                        echo '<img src="'.$stuImg.'" onerror="this.onerror=null;this.src=\'image/stu/default_avatar.png\'" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full object-cover border-2 border-primary/30 shadow-sm" alt="Avatar">';
                        echo '<span class="text-sm font-semibold text-slate-700 hidden lg:inline max-w-[120px] truncate">'.$stuName.'</span>';
                        echo '<i class="fas fa-chevron-down text-xs text-slate-400 transition-transform duration-200" id="userDropdownArrow"></i>';
                        echo '</button>';
                        echo '<div class="user-dropdown-menu" id="userDropdownMenu">';
                        echo '<div class="bg-white rounded-xl shadow-xl border border-slate-100 py-1">';
                        echo '<a href="Student/studentProfile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors"><i class="fas fa-user w-4 text-center"></i> Hồ sơ của tôi</a>';
                        echo '<a href="Student/myCourse.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors"><i class="fas fa-book-reader w-4 text-center"></i> Khóa học của tôi</a>';
                        echo '<a href="Student/myOrders.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors"><i class="fas fa-receipt w-4 text-center"></i> Đơn hàng của tôi</a>';
                        echo '<hr class="my-1 border-slate-100">';
                        echo '<a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors"><i class="fas fa-sign-out-alt w-4 text-center"></i> Đăng xuất</a>';
                        echo '</div></div></div>';
                    } else {
                        echo '<a href="login.php" class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors hidden sm:inline">Đăng nhập</a>';
                        echo '<a href="signup.php" class="px-4 sm:px-6 py-2 sm:py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-white hover:text-primary border hover:border-primary transition-all shadow-lg shadow-primary/20 m-0">Đăng ký</a>';
                    }
                ?> 
                <!-- Mobile menu toggle button -->
                <button class="md:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-100 transition-colors border-0 bg-transparent" id="mobileMenuBtn" aria-label="Mở menu" aria-expanded="false">
                    <i class="fas fa-bars text-lg text-slate-700" id="mobileMenuIcon"></i>
                </button>
            </div>
        </div>
    </header> <!-- End Navigation -->

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Mobile Menu Panel -->
    <div class="mobile-menu-panel" id="mobileMenuPanel">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <span class="text-lg font-bold text-primary">Menu</span>
            <button class="w-9 h-9 rounded-lg hover:bg-slate-100 transition-colors flex items-center justify-center border-0 bg-transparent" id="mobileMenuClose" aria-label="Đóng menu">
                <i class="fas fa-times text-slate-500"></i>
            </button>
        </div>
        <nav class="py-2">
            <a class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors <?php echo $currentPage === 'index.php' ? 'mobile-nav-active' : ''; ?>" href="index.php">
                <i class="fas fa-home w-5 text-center text-slate-400"></i> Trang chủ
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors <?php echo $currentPage === 'courses.php' ? 'mobile-nav-active' : ''; ?>" href="courses.php">
                <i class="fas fa-book w-5 text-center text-slate-400"></i> Khóa học
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors" href="Instructor/instructorLogin.php">
                <i class="fas fa-chalkboard-teacher w-5 text-center text-slate-400"></i> Giảng viên
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors" href="index.php#Feedback">
                <i class="fas fa-comment-alt w-5 text-center text-slate-400"></i> Góp ý
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors" href="index.php#Contact">
                <i class="fas fa-phone-alt w-5 text-center text-slate-400"></i> Liên hệ
            </a>
        </nav>
        <?php if (!isset($_SESSION['is_login'])): ?>
        <div class="px-5 py-4 border-t border-slate-100 space-y-2">
            <a href="login.php" class="block w-full text-center px-4 py-2.5 text-sm font-semibold text-primary border border-primary rounded-lg hover:bg-primary/5 transition-colors">Đăng nhập</a>
            <a href="signup.php" class="block w-full text-center px-4 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 transition-colors">Đăng ký</a>
        </div>
        <?php else: ?>
        <div class="border-t border-slate-100 py-2">
            <a class="flex items-center gap-3 px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors" href="Student/studentProfile.php">
                <i class="fas fa-user w-5 text-center text-slate-400"></i> Hồ sơ của tôi
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors" href="Student/myCourse.php">
                <i class="fas fa-book-reader w-5 text-center text-slate-400"></i> Khóa học của tôi
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors" href="Student/myOrders.php">
                <i class="fas fa-receipt w-5 text-center text-slate-400"></i> Đơn hàng của tôi
            </a>
            <a class="flex items-center gap-3 px-5 py-3 text-sm text-red-500 hover:bg-red-50 transition-colors" href="logout.php">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Đăng xuất
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
    (function() {
        // Mobile menu
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenuPanel = document.getElementById('mobileMenuPanel');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuClose = document.getElementById('mobileMenuClose');

        function openMobileMenu() {
            mobileMenuPanel.classList.add('active');
            mobileMenuOverlay.classList.add('active');
            mobileMenuBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            mobileMenuPanel.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileMenu);
        if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMobileMenu);
        if (mobileMenuOverlay) mobileMenuOverlay.addEventListener('click', closeMobileMenu);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenuPanel.classList.contains('active')) {
                closeMobileMenu();
            }
        });

        // User dropdown: toggle on click instead of hover
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const userDropdownArrow = document.getElementById('userDropdownArrow');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = userDropdownMenu.classList.contains('open');
                userDropdownMenu.classList.toggle('open');
                userDropdownBtn.setAttribute('aria-expanded', !isOpen);
                if (userDropdownArrow) {
                    userDropdownArrow.style.transform = isOpen ? '' : 'rotate(180deg)';
                }
            });

            document.addEventListener('click', function(e) {
                if (!document.getElementById('userDropdownContainer').contains(e.target)) {
                    userDropdownMenu.classList.remove('open');
                    userDropdownBtn.setAttribute('aria-expanded', 'false');
                    if (userDropdownArrow) userDropdownArrow.style.transform = '';
                }
            });
        }
    })();
    </script>

    <!-- Page content starts below fixed header -->
    <div class="pt-16 sm:pt-20 min-h-screen flex flex-col">
