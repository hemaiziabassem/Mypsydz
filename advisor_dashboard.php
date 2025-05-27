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
    $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'assign';

    // Clear message details if action was successful
    if (isset($_GET['action']) && $_GET['action'] === 'success') {
        $selectedMessage = null;
    }

    if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'medical_advisor') {
        $advisorId = $_SESSION['user']['id'];

        $stmt = $pdo->prepare("SELECT * FROM medical_advisors WHERE id = ?");
        $stmt->execute([$advisorId]);
        $advisor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($advisor) {
            $advisorName = $advisor['name'];
        }

        // Get unread messages
        $stmt = $pdo->prepare("
            SELECT m.id, m.content, m.created_at, m.patient_id, p.name as patient_name 
            FROM messages m
            JOIN patients p ON m.patient_id = p.id
            WHERE m.is_used = FALSE
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get selected message details if not clearing
        if (isset($_GET['message_id']) && !isset($_GET['action'])) {
            $stmt = $pdo->prepare("
                SELECT m.id, m.content, m.created_at, m.patient_id, p.name as patient_name, p.gender, p.dob 
                FROM messages m
                JOIN patients p ON m.patient_id = p.id
                WHERE m.id = ?
            ");
            $stmt->execute([$_GET['message_id']]);
            $selectedMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Assign doctor action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_doctor'])) {
            $messageId = $_POST['message_id'];
            $doctorType = $_POST['doctor_type'];
            $doctorId = $_POST['doctor_id'];
            
            try {
                $pdo->beginTransaction();

                // Mark message as used
                $stmt = $pdo->prepare("UPDATE messages SET is_used = TRUE WHERE id = ?");
                $stmt->execute([$messageId]);

                // Create assignment
                $stmt = $pdo->prepare("
                    INSERT INTO assignments 
                    (message_id, assigned_by, assigned_to_role, assigned_to_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $messageId,
                    $advisorId,
                    $doctorType,
                    $doctorId
                ]);

                $pdo->commit();
                header("Location: advisor_dashboard.php?action=success&success=assign");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = "حدث خطأ أثناء تعيين المريض: " . $e->getMessage();
            }
        }

        // Schedule appointment action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_appointment'])) {
            $messageId = $_POST['message_id'];
            $patientId = $_POST['patient_id'];
            $appointmentDate = $_POST['appointment_date'];
            $appointmentTime = $_POST['appointment_time'];
            $appointmentDateTime = $appointmentDate . ' ' . $appointmentTime;

            try {
                $pdo->beginTransaction();

                // Mark message as used
                $stmt = $pdo->prepare("UPDATE messages SET is_used = TRUE WHERE id = ?");
                $stmt->execute([$messageId]);

                // Create appointment
                $stmt = $pdo->prepare("
                    INSERT INTO appointments 
                    (patient_id, professional_id, professional_type, appointment_date, status)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $patientId,
                    $advisorId,
                    'advisor',
                    $appointmentDateTime,
                    'scheduled'
                ]);

                $pdo->commit();
                header("Location: advisor_dashboard.php?action=success&success=appointment");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $errorMessage = "حدث خطأ أثناء جدولة الموعد: " . $e->getMessage();
            }
        }

        // Get available doctors
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
    $errorMessage = "⚠ خطأ في قاعدة البيانات: " . $e->getMessage();
}

// Set success message if redirected after success
if (isset($_GET['success'])) {
    $successMessage = ($_GET['success'] === 'assign') 
        ? "تم تعيين المريض بنجاح إلى الطبيب المختص" 
        : "تم جدولة الموعد بنجاح";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>لوحة تحكم المستشار - MyPsyDz</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
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
        .btn-appointment {
            background: linear-gradient(135deg, #2196F3, #0b7dda);
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
        .appointment-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -5px;
            margin-left: -5px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 5px;
            padding-left: 5px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #6e8efb;
            border-bottom: 2px solid #6e8efb;
            background-color: transparent;
        }
        .tab-content {
            padding: 20px 0;
        }
        .success-message {
            text-align: center;
            padding: 20px;
        }
        .success-message i {
            font-size: 3rem;
            color: #4CAF50;
            margin-bottom: 15px;
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
            <p>أخصائي نفسي عيادي</p>
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
            
            <!-- Message Details and Actions -->
            <div class="col-md-7">
                <?php if ($selectedMessage): ?>
                <div class="card">
                    <div class="card-header">
                        تفاصيل الرسالة
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
                        
                        <!-- Actions Tabs -->
                        <ul class="nav nav-tabs mt-4">
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'assign' ? 'active' : '' ?>" href="?message_id=<?= $selectedMessage['id'] ?>&tab=assign">تعيين لطبيب</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $activeTab === 'appointment' ? 'active' : '' ?>" href="?message_id=<?= $selectedMessage['id'] ?>&tab=appointment">جدولة موعد</a>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- Assign to Doctor Tab -->
                            <div class="tab-pane <?= $activeTab === 'assign' ? 'active' : '' ?>">
                                <form method="POST" action="">
                                    <input type="hidden" name="message_id" value="<?= $selectedMessage['id'] ?>">
                                    <input type="hidden" name="patient_id" value="<?= $selectedMessage['patient_id'] ?>">
                                    
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
                                    
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" name="assign_doctor" class="btn btn-assign">تعيين المريض</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Schedule Appointment Tab -->
                            <div class="tab-pane <?= $activeTab === 'appointment' ? 'active' : '' ?>">
                                <form method="POST" action="">
                                    <input type="hidden" name="message_id" value="<?= $selectedMessage['id'] ?>">
                                    <input type="hidden" name="patient_id" value="<?= $selectedMessage['patient_id'] ?>">
                                    
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
                                        <button type="submit" name="schedule_appointment" class="btn btn-appointment">تأكيد الموعد</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'success'): ?>
                <div class="card">
                    <div class="card-body success-message">
                        <i class="fas fa-check-circle"></i>
                        <h5><?= $successMessage ?></h5>
                        <p class="text-muted">يمكنك اختيار رسالة أخرى من القائمة</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-envelope-open-text fa-3x mb-3 text-muted"></i>
                        <h5>اختر رسالة لعرض التفاصيل</h5>
                        <p class="text-muted">اضغط على أي رسالة من القائمة لعرض محتواها واتخاذ الإجراء المناسب</p>
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
        if ($(this).find('select[required]').val() === '') {
            e.preventDefault();
            alert('الرجاء اختيار الطبيب المطلوب');
        }
    });
    
    // Set minimum time to current time + 1 hour when today is selected
    $('#appointmentDate').change(function() {
        const today = new Date().toISOString().split('T')[0];
        if ($(this).val() === today) {
            const now = new Date();
            const nextHour = (now.getHours() + 1) % 24;
            $('#appointmentTime').attr('min', `${nextHour.toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}`);
        } else {
            $('#appointmentTime').removeAttr('min');
        }
    });
});
</script>

</body>
</html>