<?php
session_start();

$host = 'localhost';
$dbname = 'mypsydz';
$username = 'root';
$password = '';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $userType = $_POST['userType'] ?? '';
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $name = $firstName . ' ' . $lastName;
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $passwordRaw = $_POST['password'] ?? '';

        if (!$userType || !$name || !$phone || !$passwordRaw || !$gender || !$dob) {
            $errorMessage = "❌ جميع الحقول مطلوبة.";
        } else {
            $passwordHashed = password_hash($passwordRaw, PASSWORD_BCRYPT);

            $validRoles = [
                'patient' => 'patients',
                'medical_advisor' => 'medical_advisors',
                'educational_psychologist' => 'educational_psychologists',
                'speech_therapist' => 'speech_therapists',
                'social_worker' => 'social_workers'
            ];

            if (!array_key_exists($userType, $validRoles)) {
                $errorMessage = "❌ نوع المستخدم غير صالح.";
            } else {
                $table = $validRoles[$userType];
                $stmt = $pdo->prepare("INSERT INTO $table (name, phone, email, password, gender, dob) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $phone, $email, $passwordHashed, $gender, $dob]);
                $successMessage = "✅ تم إنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.";
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "❌ خطأ في قاعدة البيانات: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="إنشاء حساب جديد في منصة MyPsyDz - الدعم النفسي والاستشارات النفسية أونلاين">
    <meta name="author" content="MyPsyDz">
    <link href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,800,900&display=swap" rel="stylesheet">
    <title>تسجيل جديد - MyPsyDz</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-eduwell-style.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            height: 50px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        select.form-control {
            padding: 10px 15px;
        }
        .btn-register {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            border: none;
            color: white;
            padding: 12px 30px;
            width: 100%;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 20px;
        }
        .register-footer {
            text-align: center;
            margin-top: 20px;
        }
        .rtl-text {
            text-align: right;
            direction: rtl;
        }
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: -15px;
            margin-bottom: 15px;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength span {
            display: block;
            height: 100%;
            width: 0;
            background: transparent;
            transition: all 0.3s ease;
        }
        .gender-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .gender-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .section-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
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
                          <li><a href="index.html">الرئيسية</a></li>
                          
                          
                          <li><a href="login.php">تسجيل الدخول</a></li> 
                          <li ><a href="register.php" class="active">التسجيل</a></li> 
                      </ul>        
                      <a class='menu-trigger'>
                          <span>القائمة</span>
                      </a>
                </nav>
            </div>
        </div>
    </div>
</header>

<section class="register-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="register-container rtl-text">
                    <div class="register-header">
                        <h2>إنشاء حساب جديد</h2>
                        <p>املأ النموذج أدناه للانضمام إلى منصتنا</p>
                    </div>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?= $errorMessage ?></div>
                    <?php endif; ?>
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?= $successMessage ?></div>
                    <?php endif; ?>

                    <form id="registerForm" action="register.php" method="post">
                        <div class="form-group">
                            <label for="userType">نوع المستخدم</label>
                            <select class="form-control" id="userType" name="userType" required>
                                <option value="" disabled selected>اختر نوع المستخدم</option>
                                <option value="medical_advisor">عيادي</option>
<option value="educational_psychologist">تربوي</option>
<option value="speech_therapist">أرطوفوني</option>
<option value="social_worker">اجتماعي</option>
<option value="patient">مريض</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="firstName" name="firstName" placeholder="الاسم" required>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="lastName" name="lastName" placeholder="اللقب" required>
                            </div>
                        </div>

                        <input type="email" class="form-control" id="email" name="email" placeholder="البريد الإلكتروني" required>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="رقم الهاتف" required>
                        <input type="date" class="form-control" id="dob" name="dob" required>

                        <div class="form-group">
                            <div class="section-title">الجنس</div>
                            <div class="gender-options">
                                <label><input type="radio" name="gender" value="male" required> <span>ذكر</span></label>
                                <label><input type="radio" name="gender" value="female"> <span>أنثى</span></label>
                            </div>
                        </div>

                        <input type="password" class="form-control" id="password" name="password" placeholder="كلمة المرور" required>
                        <input type="password" class="form-control" id="confirmPassword" placeholder="تأكيد كلمة المرور" required>

                        <button type="submit" class="btn btn-register">إنشاء حساب</button>

                        <div class="register-footer">
                            <p>لديك حساب بالفعل؟ <a href="login.php">سجل الدخول الآن</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    $('#password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        var width = (strength / 5) * 100;
        var color = strength <= 1 ? '#ff4d4d' : strength <= 3 ? '#ffcc00' : '#00cc66';
        $('#passwordStrength').css({'width': width + '%','background-color': color});
    });

    $('#registerForm').submit(function(e) {
        var password = $('#password').val();
        var confirmPassword = $('#confirmPassword').val();
        if (password !== confirmPassword) {
            alert('كلمة المرور غير متطابقة!');
            e.preventDefault();
            return false;
        }
    });
</script>
</body>
</html>
