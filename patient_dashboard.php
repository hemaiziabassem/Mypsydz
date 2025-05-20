<?php
session_start();

// Rediriger si non connecté ou pas patient
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}

// Configuration DB
$host = 'localhost';
$dbname = 'mypsydz';
$username = 'root';
$password = '';
$successMessage = '';
$errorMessage = '';
$advisor = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Récupérer l'ID du patient
    $patientId = $_SESSION['user']['id'];

    // Récupérer le seul advisor existant (on suppose qu’il y en a toujours un seul dans la BDD)
    $stmt = $pdo->query("SELECT id, name, email, phone FROM medical_advisors LIMIT 1");
    $advisor = $stmt->fetch();

    // Traitement de l'envoi de message
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty(trim($_POST['message_text']))) {
            $errorMessage = "الرجاء إدخال نص الرسالة";
        } else {
            $message = trim($_POST['message_text']);

            // Insérer le message
            $stmt = $pdo->prepare("
                INSERT INTO messages (patient_id, content) 
                VALUES (?, ?)
            ");
            $stmt->execute([$patientId, $message]);
            $messageId = $pdo->lastInsertId();

            if ($advisor) {
                // Assigner automatiquement ce message à l’advisor
                $stmt = $pdo->prepare("
                    INSERT INTO assignments (message_id, assigned_by, assigned_to_role, assigned_to_id) 
                    VALUES (?, ?, 'medical_advisor', ?)
                ");
                $stmt->execute([$messageId, $advisor['id'], $advisor['id']]);

                $successMessage = "✅ تم إرسال الرسالة بنجاح إلى مستشارك";
            } else {
                $errorMessage = "❌ لم يتم العثور على مستشار في قاعدة البيانات";
            }
        }
    }
} catch (PDOException $e) {
    $errorMessage = "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="لوحة تحكم المريض - MyPsyDz">
    <meta name="author" content="MyPsyDz">
    <link href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <title>لوحة تحكم المريض - MyPsyDz</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fontawesome.css">

    <style>
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fa;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 0;
        }
        .main-content {
            flex: 1;
            padding: 30px;
        }
        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header img {
            width: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .sidebar-menu {
            padding: 20px;
        }
        .sidebar-menu a {
            display: block;
            color: white;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar-menu a i {
            margin-left: 10px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 700;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        .welcome-card {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
        }
        .message-form-container {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .btn-send {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            width: 100%;
        }
        .advisor-card {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .advisor-info {
            margin-right: 15px;
        }
        .advisor-name {
            font-weight: 700;
            margin-bottom: 5px;
        }
        .advisor-specialty {
            color: #6c757d;
            font-size: 14px;
        }
        .sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    text-decoration: none;
    color: #fff; /* ou autre couleur */
}
    
    </style>
</head>

<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/malade.jpg" alt="صورة المريض">
            <h5><?= htmlspecialchars($_SESSION['user']['name'] ?? 'مريض') ?></h5>
            <p>مريض</p>
        </div>
        <div class="sidebar-menu" style="direction: rtl; text-align: right;">
    <a href="patient_dashboard.php" class="active" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-home"></i> لوحة التحكم
    </a>
    <a href="#" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-calendar-alt"></i> مواعيدي
    </a>
    <a href="payment.php" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-user-md"></i> دفع المستحقات
    </a>
    <a href="#" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-comments"></i> إرسال رسالة
    </a>
    <a href="#" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-file-medical"></i> السجلات الطبية
    </a>
    <a href="#" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-cog"></i> الإعدادات
    </a>
    <a href="logout.php" style="display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
    </a>
</div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card welcome-card">
                    <div class="card-body">
                        <h4>مرحباً بك <?= htmlspecialchars($_SESSION['user']['name'] ?? 'مريض') ?></h4>
                        <p class="mb-0">يمكنك التواصل مع مستشارك من خلال هذه الصفحة</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Advisor Info Section -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">مستشارك المعين</div>
                    <div class="card-body">
                        
                            <div class="advisor-card">
                                <img src="assets/images/advisor.jpg" width="80" height="80" class="rounded-circle">
                                <div class="advisor-info">
                                    <div class="advisor-name"><?= htmlspecialchars($advisor['name']) ?></div>
                                    
                                    <div class="mt-2"><span class="badge badge-success">متصل الآن</span></div>
                                </div>
                            </div>
                            <div class="card mt-4">
                                <div class="card-header">معلومات الاتصال</div>
                                <div class="card-body">
                                    <p><i class="fas fa-phone mr-2"></i> <?= htmlspecialchars($advisor['phone']) ?></p>
                                    <p><i class="fas fa-envelope mr-2"></i> <?= htmlspecialchars($advisor['email']) ?></p>
                                    <p><i class="fas fa-clock mr-2"></i> متاح من 9 صباحاً إلى 5 مساءً</p>
                                </div>
                            </div>
                        
                            
                        
                    </div>
                </div>
            </div>

            <!-- Message Form Section -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">إرسال رسالة جديدة إلى مستشارك</div>
                    <div class="card-body">
                        <div class="message-form-container">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="messageText">الرسالة</label>
                                    <textarea class="form-control" id="messageText" name="message_text" rows="5" 
                                        required <?= $advisor ? '' : 'disabled' ?>
                                        placeholder="اكتب رسالتك لمستشارك هنا..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-send" <?= $advisor ? '' : 'disabled' ?>>
                                    <i class="fas fa-paper-plane"></i> إرسال الرسالة
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">التعليمات</div>
                    <div class="card-body">
                        <ul>
                            <li> سيقوم المستشار بقراءة الرسالة وتوجيهك إلى الطبيب المختص</li>
                            <li>للحالات الطارئة، يرجى الاتصال بالرقم المباشر</li>
                            <li>الرسائل مسجلة وحاصة على الخصوصية التامة</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/fontawesome.js"></script>
</body>
</html>