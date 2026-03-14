<?php if(!isset($_SESSION)){ session_start(); } ?>
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    
     <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" type="text/css" href="css/all.min.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet">

    <!-- Student Testimonial Owl Slider CSS -->
    <link rel="stylesheet" type="text/css" href="css/owl.min.css">
    <link rel="stylesheet" type="text/css" href="css/owl.theme.min.css">
    <link rel="stylesheet" type="text/css" href="css/testyslider.css">

    <!-- Tailwind CSS (AI Generated) -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#003366",
                        "accent-green": "#10b981",
                        "background-light": "#f5f7f8",
                        "background-dark": "#0f1923",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
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
        /* Override Bootstrap for Tailwind compatibility */
        a:hover { text-decoration: none; }
    </style>

    <!-- Custom Style CSS -->
    <link rel="stylesheet" type="text/css" href="./css/style.css" />
    <title>CTU E-Learning - Truyền thông Đa phương tiện</title>
  </head>
  <body class="font-display bg-background-light text-slate-900">
     <!-- Start Navigation -->
     <header class="fixed top-0 w-full z-50 glass-header border-b border-primary/10">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-10">
                <a href="index.php" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    <div class="text-primary">
                        <i class="fas fa-graduation-cap text-4xl font-bold"></i>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-primary m-0">CTU E-Learning</h1>
                </a>
                <nav class="hidden md:flex items-center gap-8">
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors" href="index.php">Trang chủ</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors" href="courses.php">Khóa học</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors" href="index.php#Feedback">Góp ý</a>
                    <a class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors" href="index.php#Contact">Liên hệ</a>
                </nav>
            </div>
            <div class="flex items-center gap-6">
                <?php 
                    if (isset($_SESSION['is_login'])){
                        $stuEmail = $_SESSION['stuLogEmail'];
                        $sqlAvatar = "SELECT stu_img, stu_name FROM student WHERE stu_email = '$stuEmail'";
                        $resAvatar = $conn->query($sqlAvatar);
                        $stuImg = "";
                        $stuName = "Học viên";
                        if ($resAvatar && $resAvatar->num_rows > 0) {
                            $rowAvatar = $resAvatar->fetch_assoc();
                            $stuImg = $rowAvatar['stu_img'];
                            $stuName = $rowAvatar['stu_name'];
                        }
                        if(empty($stuImg)){
                            $stuImg = 'image/stu/default_avatar.png';
                        } else {
                            $stuImg = ltrim(str_replace('../', '', $stuImg), '/');
                        }

                        echo '<a href="Student/myCart.php" class="relative text-slate-700 hover:text-primary transition-colors pr-4"><i class="fas fa-shopping-cart text-xl"></i><span class="absolute -top-1 -right-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full" id="cartCount">0</span></a>';
                        
                        echo '<div class="relative group">';
                        echo '<button class="flex items-center gap-2 focus:outline-none cursor-pointer border-0 bg-transparent p-0 m-0">';
                        echo '<img src="'.$stuImg.'" onerror="this.onerror=null;this.src=\'image/stu/default_avatar.png\'" class="w-9 h-9 rounded-full object-cover border-2 border-primary/30 shadow-sm" alt="Avatar">';
                        echo '<span class="text-sm font-semibold text-slate-700 hidden md:inline">'.$stuName.'</span>';
                        echo '<i class="fas fa-chevron-down text-xs text-slate-400"></i>';
                        echo '</button>';
                        echo '<div class="absolute right-0 top-full pt-2 w-48 opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition-all duration-200 z-50">';
                    echo '<div class="bg-white rounded-xl shadow-xl border border-slate-100 py-1">';
                    echo '<a href="Student/studentProfile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary"><i class="fas fa-user w-4 text-center"></i> Hồ sơ của tôi</a>';
                    echo '<a href="Student/myCourse.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary"><i class="fas fa-book-reader w-4 text-center"></i> Khóa học của tôi</a>';
                    echo '<hr class="my-1 border-slate-100">';
                    echo '<a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50"><i class="fas fa-sign-out-alt w-4 text-center"></i> Đăng xuất</a>';
                    echo '</div></div></div>';
                    } else {
                        echo '<a href="login.php" class="text-sm font-semibold text-slate-700 hover:text-primary transition-colors">Đăng nhập</a>';
                        echo '<a href="signup.php" class="px-6 py-2.5 bg-primary text-white text-sm font-bold rounded-lg hover:bg-white hover:text-primary border hover:border-primary transition-all shadow-lg shadow-primary/20 m-0">Đăng ký</a>';
                    }
                ?> 
            </div>
        </div>
    </header> <!-- End Navigation -->