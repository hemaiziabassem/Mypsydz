<?php
session_start();

$host = 'localhost';
$dbname = 'mypsydz';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $advisorName = "اسم المستشار";
    $doctors = [];
    $messages = [];
    $selectedMessage = null;
    $successMessage = '';
    $errorMessage = '';
    $advisor = null;

    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'medical_advisor') {
        $advisorId = $_SESSION['user']['id'];

        $stmt = $pdo->prepare("SELECT * FROM medical_advisors WHERE id = ?");
        $stmt->execute([$advisorId]);
        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($advisor) {
            $advisorName = $advisor['name'];
        }

        $stmt = $pdo->prepare("
            SELECT m.id, m.content, m.created_at, p.name as patient_name 
            FROM messages m
            JOIN patients p ON m.patient_id = p.id
            WHERE m.is_used = FALSE
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['message_id'])) {
            $stmt = $pdo->prepare("
                SELECT m.id, m.content, m.created_at, p.name as patient_name, p.gender, p.dob 
                FROM messages m
                JOIN patients p ON m.patient_id = p.id
                WHERE m.id = ?
            ");
            $stmt->execute([$_GET['message_id']]);
            $selectedMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_doctor'])) {
            $messageId = $_POST['message_id'];
            $doctorType = $_POST['doctor_type'];
            $doctorId = $_POST['doctor_id'];
            

            $stmt = $pdo->prepare("UPDATE messages SET is_used = TRUE WHERE id = ?");
            $stmt->execute([$messageId]);

            if (!empty($doctorId) && !empty($doctorType)) {
                $stmt = $pdo->prepare("
                    INSERT INTO assignments 
                    (message_id, assigned_by, assigned_to_role, assigned_to_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $messageId,
                    $advisorId,
                    $doctorType,
                    $doctorId,
                    
                ]);
               $successMessage = "تم تعيين المريض بنجاح إلى الطبيب المختص";

            } else {
                $errorMessage = "حدث خطأ أثناء تعيين المريض، يرجى المحاولة مرة أخرى";            }
        }

        $professionalTables = [
            'educational_psychologists' => 'أخصائي تربوي',
            'speech_therapists' => 'معالج نطق',
            'social_workers' => 'أخصائي اجتماعي'
        ];

        foreach ($professionalTables as $table => $title) {
            $query = "SHOW TABLES LIKE '$table'";
            if ($pdo->query($query)->rowCount() > 0) {
                $stmt = $pdo->query("SELECT id, name FROM $table");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $doctors[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'type' => $table,
                        'title' => $title
                    ];
                }
            }
        }
    }

} catch (PDOException $e) {
    $errorMessage = "\u26d4 \u062e\u0637\u0623 \u0641\u064a \u0642\u0627\u0639\u062f\u0629 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="لوحة تحكم المستشار - MyPsyDz">
    <meta name="author" content="MyPsyDz">
    <link href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,800,900&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <title>لوحة تحكم المستشار - MyPsyDz</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Additional CSS Files -->
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
        .message-container {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .message {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: white;
            border-left: 4px solid #6e8efb;
        }
        .patient-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .patient-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
        }
        .message-time {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .btn-assign {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            border: none;
        }
        .unread-badge {
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-right: 5px;
        }
        .doctor-select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        .status-new {
            background-color: #FFEB3B;
            color: #000;
        }
        .status-assigned {
            background-color: #4CAF50;
            color: white;
        }
        .sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
}

    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/advisor.jpg" alt="صورة المستشار">
            <h5><?= htmlspecialchars($advisorName) ?></h5>
            <p>مستشار رئيسي</p>
        </div>
        <div class="sidebar-menu">
    <a href="advisor_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
    <a href="#"><i class="fas fa-user-injured"></i> المرضى</a>
    <a href="advisor_dashboard.php"><i class="fas fa-envelope-open-text"></i> الرسائل <span class="unread-badge"><?= count($messages) ?></span></a>
    <a href="#"><i class="fas fa-user-md"></i> الأطباء</a>
    <a href="#"><i class="fas fa-calendar-check"></i> المواعيد</a>
    <a href="#"><i class="fas fa-sliders-h"></i> الإعدادات</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
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
                        <h4>مرحباً بك <?= htmlspecialchars($advisorName) ?>!</h4>
                        <p class="mb-0">لديك <?= count($messages) ?> رسائل جديدة من المرضى تحتاج إلى مراجعة</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Messages List -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>رسائل المرضى</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary">الجديدة</button>
                            <button class="btn btn-sm btn-outline-secondary">الكل</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($messages as $message): ?>
                            <a href="?message_id=<?= $message['id'] ?>" class="list-group-item list-group-item-action <?= $selectedMessage && $selectedMessage['id'] == $message['id'] ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($message['patient_name']) ?></h6>
                                    <small class="text-muted"><?= date('Y-m-d H:i', strtotime($message['created_at'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars(substr($message['content'], 0, 50)) ?>...</p>
                                <span class="status-badge status-new">جديد</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Message Details and Assignment -->
            <div class="col-md-7">
                <?php if ($selectedMessage): ?>
                <div class="card">
                    <div class="card-header">
                        تفاصيل الرسالة وتعيين الطبيب
                    </div>
                    <div class="card-body">
                        <!-- Patient Info -->
                        <div class="patient-info">
                            <img src="assets/images/malade.jpg" alt="صورة المريض">
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($selectedMessage['patient_name']) ?></h5>
                                <small class="text-muted">
                                    الجنس: <?= $selectedMessage['gender'] == 'male' ? 'ذكر' : 'أنثى' ?> | 
                                    العمر: <?= date_diff(date_create($selectedMessage['dob']), date_create('today'))->y ?> سنة
                                </small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Message Content -->
                        <div class="message">
                            <h6>طلب استشارة</h6>
                            <p><?= nl2br(htmlspecialchars($selectedMessage['content'])) ?></p>
                            <div class="message-time">تم الإرسال: <?= date('Y-m-d H:i', strtotime($selectedMessage['created_at'])) ?></div>
                        </div>
                        
                        <!-- Assignment Form -->
                        <div class="mt-4">
                            <h5>تعيين المريض إلى طبيب</h5>
                            <form method="POST" action="">
                                <input type="hidden" name="message_id" value="<?= $selectedMessage['id'] ?>">
                                
                                <div class="form-group">
                                    <label for="doctorSelect">اختر الطبيب المتخصص</label>
                                    <select class="doctor-select" id="doctorSelect" name="doctor_id" required>
                                        <option value="">-- اختر الطبيب --</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                        <option value="<?= $doctor['id'] ?>" data-type="<?= $doctor['type'] ?>">
                                            <?= htmlspecialchars($doctor['name']) ?> - <?= $doctor['title'] ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="doctor_type" id="doctorType" value="">
                                </div>
                                
                                <div class="form-group">
                                    <label for="advisorNotes">ملاحظات إضافية</label>
                                    <textarea class="form-control" id="advisorNotes" name="notes" rows="3" placeholder="أضف أي ملاحظات أو توجيهات للطبيب..."></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-outline-secondary">طلب معلومات إضافية</button>
                                    <button type="submit" name="assign_doctor" class="btn btn-assign">تعيين المريض</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-envelope-open-text fa-3x mb-3 text-muted"></i>
                        <h5>اختر رسالة لعرض التفاصيل</h5>
                        <p class="text-muted">اضغط على أي رسالة من القائمة لعرض محتواها وخيارات التعيين</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/fontawesome.js"></script>

<script>
$(document).ready(function() {
    // Set doctor type when selection changes
    $('#doctorSelect').change(function() {
        var selectedOption = $(this).find('option:selected');
        $('#doctorType').val(selectedOption.data('type'));
    });

    // Handle form submission
    $('form').submit(function(e) {
        if ($('#doctorSelect').val() === '') {
            e.preventDefault();
            alert('الرجاء اختيار طبيب لتعيين المريض إليه');
        }
    });
});
</script>

</body>
</html>