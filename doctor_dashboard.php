<?php
session_start();

$host = 'localhost';
$dbname = 'mypsydz';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $doctorName = "اسم الطبيب";
    $assignedPatients = [];
    $selectedPatient = null;
    $patientMessages = [];
    $successMessage = '';
    $errorMessage = '';
    $doctor = null;
    $doctorType = '';
    $tableName = '';
    $assignedToRole = '';

    if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['social_worker', 'speech_therapist', 'educational_psychologist'])) {
        $doctorId = $_SESSION['user']['id'];
        $doctorType = $_SESSION['user']['role'];

        switch ($doctorType) {
            case 'social_worker':
                $tableName = 'social_workers';
                $assignedToRole = 'social_workers';
                break;
            case 'speech_therapist':
                $tableName = 'speech_therapists';
                $assignedToRole = 'speech_therapists';
                break;
            case 'educational_psychologist':
                $tableName = 'educational_psychologists';
                $assignedToRole = 'educational_psychologists';
                break;
        }

        $stmt = $pdo->prepare("SELECT * FROM $tableName WHERE id = ?");
        $stmt->execute([$doctorId]);
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($doctor) {
            $doctorName = $doctor['name'];
        }

        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.gender, p.dob, a.assigned_at
            FROM assignments a
            JOIN messages m ON a.message_id = m.id
            JOIN patients p ON m.patient_id = p.id
            WHERE a.assigned_to_id = ? AND a.assigned_to_role = ?
            ORDER BY a.assigned_at DESC
        ");
        $stmt->execute([$doctorId, $assignedToRole]);
        $assignedPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['patient_id'])) {
            $stmt = $pdo->prepare("
                SELECT m.id, m.content, m.created_at
                FROM messages m
                JOIN assignments a ON m.id = a.message_id
                WHERE m.patient_id = ? AND a.assigned_to_id = ? AND a.assigned_to_role = ?
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([$_GET['patient_id'], $doctorId, $assignedToRole]);
            $patientMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$_GET['patient_id']]);
            $selectedPatient = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_appointment'])) {
            $patientId = $_POST['patient_id'];
            $appointmentDate = $_POST['appointment_date'];
            $appointmentTime = $_POST['appointment_time'];
            $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO appointments 
                    (patient_id, professional_id, professional_type, appointment_date, status)
                    VALUES (?, ?, ?, ?, 'scheduled')
                ");
                $stmt->execute([
                    $patientId,
                    $doctorId,
                    $assignedToRole,
                    $appointmentDateTime
                ]);
                $successMessage = "تم جدولة الموعد بنجاح";
            } catch (PDOException $e) {
                $errorMessage = "حدث خطأ أثناء جدولة الموعد: " . $e->getMessage();
            }
        }
    }

} catch (PDOException $e) {
    $errorMessage = "⚠ خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="لوحة تحكم الطبيب - MyPsyDz">
    <meta name="author" content="MyPsyDz">
    <link href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <title>لوحة تحكم الطبيب - MyPsyDz</title>

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
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
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
        .btn-primary {
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
        .appointment-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 10px; /* espace entre l’icône et le texte */
}
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/doctor.jpg" alt="صورة الطبيب">
            <h5><?= htmlspecialchars($doctorName) ?></h5>
            <p>
                <?php 
                    if ($doctorType == 'social_worker') echo 'أخصائي اجتماعي';
                    elseif ($doctorType == 'speech_therapist') echo 'معالج نطق';
                    elseif ($doctorType == 'educational_psychologist') echo 'أخصائي تربوي';
                ?>
            </p>
        </div>
        <div class="sidebar-menu">
    <a href="doctor_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
    <a href="#"><i class="fas fa-user-injured"></i> المرضى <span class="unread-badge"><?= count($assignedPatients) ?></span></a>
    <a href="#"><i class="fas fa-calendar-check"></i> المواعيد</a>
    <a href="#"><i class="fas fa-chart-line"></i> التقارير</a>
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
                        <h4>مرحباً بك <?= htmlspecialchars($doctorName) ?>!</h4>
                        <p class="mb-0">لديك <?= count($assignedPatients) ?> مريض معين إليك</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Patients List -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>المرضى المعينين</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary">الكل</button>
                            <button class="btn btn-sm btn-outline-secondary">الجدد</button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($assignedPatients as $patient): ?>
                            <a href="?patient_id=<?= $patient['id'] ?>" class="list-group-item list-group-item-action <?= $selectedPatient && $selectedPatient['id'] == $patient['id'] ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($patient['name']) ?></h6>
                                    <small class="text-muted"><?= date('Y-m-d', strtotime($patient['assigned_at'])) ?></small>
                                </div>
                                <p class="mb-1">
                                    الجنس: <?= $patient['gender'] == 'male' ? 'ذكر' : 'أنثى' ?> | 
                                    العمر: <?= date_diff(date_create($patient['dob']), date_create('today'))->y ?> سنة
                                </p>
                                
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Patient Details and Messages -->
            <div class="col-md-7">
                <?php if ($selectedPatient): ?>
                <div class="card">
                    <div class="card-header">
                        تفاصيل المريض والرسائل
                    </div>
                    <div class="card-body">
                        <!-- Patient Info -->
                        <div class="patient-info">
                            <img src="assets/images/malade.jpg" alt="صورة المريض">
                            <div>
                                <h5 class="mb-0"><?= htmlspecialchars($selectedPatient['name']) ?></h5>
                                <small class="text-muted">
                                    الجنس: <?= $selectedPatient['gender'] == 'male' ? 'ذكر' : 'أنثى' ?> | 
                                    العمر: <?= date_diff(date_create($selectedPatient['dob']), date_create('today'))->y ?> سنة |
                                    تاريخ الميلاد: <?= date('Y-m-d', strtotime($selectedPatient['dob'])) ?>
                                </small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Messages -->
                        <h5>الرسائل</h5>
                        <div class="message-container">
                            <?php if (count($patientMessages) > 0): ?>
                                <?php foreach ($patientMessages as $message): ?>
                                <div class="message">
                                    <p><?= nl2br(htmlspecialchars($message['content'])) ?></p>
                                    <div class="message-time">تم الإرسال: <?= date('Y-m-d H:i', strtotime($message['created_at'])) ?></div>
                                    
                                        
                                    
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">لا توجد رسائل لهذا المريض</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Schedule Appointment Form -->
                        <div class="mt-4 appointment-form">
                            <h5>جدولة موعد</h5>
                            <form method="POST" action="">
                                <input type="hidden" name="patient_id" value="<?= $selectedPatient['id'] ?>">
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="appointmentDate">تاريخ الموعد</label>
                                        <input type="date" class="form-control" id="appointmentDate" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="appointmentTime">وقت الموعد</label>
                                        <input type="time" class="form-control" id="appointmentTime" name="appointment_time" required>
                                    </div>
                                </div>
                                
                                
                                
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" name="schedule_appointment" class="btn btn-primary">تأكيد الموعد</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-user-injured fa-3x mb-3 text-muted"></i>
                        <h5>اختر مريضاً لعرض التفاصيل</h5>
                        <p class="text-muted">اضغط على أي مريض من القائمة لعرض معلوماته ورسائله وجدولة موعد</p>
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
    // Set minimum time to current time + 1 hour
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    document.getElementById('appointmentTime').min = `${hours}:${minutes}`;
    
    // If today is selected, set min time to current time + 1 hour
    $('#appointmentDate').change(function() {
        const selectedDate = new Date($(this).val());
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate.getTime() === today.getTime()) {
            const now = new Date();
            const nextHour = (now.getHours() + 1) % 24;
            document.getElementById('appointmentTime').min = `${nextHour.toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`;
        } else {
            document.getElementById('appointmentTime').removeAttribute('min');
        }
    });
});
</script>

</body>
</html>