<?php
session_start();

$host = 'localhost';
$dbname = 'mypsydz';
$username = 'root';
$password = '';

$roleTables = [
    'patient' => 'patients',
    'medical_advisor' => 'medical_advisors',
    'educational_psychologist' => 'educational_psychologists',
    'speech_therapist' => 'speech_therapists',
    'social_worker' => 'social_workers',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';

        if (empty($email) || empty($passwordInput)) {
            $error = "❌ يرجى إدخال البريد الإلكتروني وكلمة المرور.";
        } else {
            foreach ($roleTables as $role => $table) {
                $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($passwordInput, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'role' => $role
                    ];

                    if ($role === 'patient') {
                        header("Location: patient_dashboard.php");
                    } elseif ($role === 'medical_advisor') {
                        header("Location: advisor_dashboard.php");
                    } else {
                        header("Location: doctor_dashboard.php");
                    }
                    exit;
                }
            }

            $error = "❌ البريد الإلكتروني أو كلمة المرور غير صحيحة.";
        }
    } catch (PDOException $e) {
        $error = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="تسجيل الدخول إلى منصة MyPsyDz - الدعم النفسي والاستشارات النفسية أونلاين">
    <meta name="author" content="MyPsyDz">
    <link href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,800,900&display=swap" rel="stylesheet">
    <title>تسجيل الدخول - MyPsyDz</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-eduwell-style.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #2a2a2a;
            font-weight: 700;
        }
        .form-control {
            height: 50px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            border: none;
            color: white;
            padding: 12px 30px;
            width: 100%;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 10px;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
        .login-footer a {
            color: #6e8efb;
            text-decoration: none;
        }
        .rtl-text {
            text-align: right;
            direction: rtl;
        }
        .header-area {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .header-area .logo {
            color: white;
            font-weight: 700;
            font-size: 24px;
            padding: 15px 0;
            display: block;
        }
        .alert {
            margin-top: 15px;
        }
    </style>
</head>

<body>
<header class="header-area header-sticky">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <nav class="main-nav">
                    <a href="index.html" class="logo">
                        MyPsyDz
                          <img src="assets/images/logo.png " style="max-width: 75px; max-width: 75px;">
                          
                      </a>
                      <!-- ***** Logo End ***** -->
                      <!-- ***** Menu Start ***** -->
                      <ul class="nav">
                          <li><a href="index.html" >الرئيسية</a></li>
                          
                          
                          <li><a href="login.php" class="active">تسجيل الدخول</a></li> 
                          <li ><a href="register.php">التسجيل</a></li> 
                      </ul>        
                      <a class='menu-trigger'>
                          <span>القائمة</span>
                      </a>
                </nav>
            </div>
        </div>
    </div>
</header>

<section class="login-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="login-container rtl-text">
                    <div class="login-header">
                        <h2>تسجيل الدخول إلى حسابك</h2>
                        <p>أدخل بياناتك للوصول إلى خدماتنا النفسية المتخصصة</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger text-center"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="أدخل بريدك الإلكتروني" required>
                        </div>

                        <div class="form-group">
                            <label for="password">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                        </div>

                        <button type="submit" class="btn btn-login">تسجيل الدخول</button>

                        <div class="login-footer">
                            <a href="forgot-password.html">نسيت كلمة المرور؟</a>
                            <p>ليس لديك حساب؟ <a href="register.php">انشئ حساب جديد</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
