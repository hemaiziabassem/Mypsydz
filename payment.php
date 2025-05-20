<?php
session_start();

// Redirect if not logged in or not a patient
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="دفع إلكتروني - MyPsyDz">
    <meta name="author" content="MyPsyDz">
    <link href="https://fonts.googleapis.com/css?family=Tajawal:300,400,500,700,800,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>دفع إلكتروني - MyPsyDz</title>
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
            padding: 10px;
            text-decoration: none;
            color: #fff;
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
        .payment-method {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #667eea;
            background-color: #f8f9fa;
        }
        .payment-method.selected {
            border-color: #667eea;
            background-color: #f0f5ff;
        }
        .btn-pay {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
        }
        .payment-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
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
            <a href="patient_dashboard.php" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-home"></i> لوحة التحكم
            </a>
            <a href="#" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-calendar-alt"></i> مواعيدي
            </a>
            
            <a href="patient_dashboard.php" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-comments"></i> إرسال رسالة
            </a>
            <a href="payment.php" class="active" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-credit-card"></i> الدفع الإلكتروني
            </a>
            <a href="" style="display: flex; align-items: center; gap: 10px;">
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
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>الدفع الإلكتروني</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Payment Methods -->
                            <div class="col-md-8">
                                <h5>اختر طريقة الدفع</h5>
                                
                                <div class="payment-method selected" data-method="credit-card">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-credit-card fa-2x mr-3 text-primary"></i>
                                        <div>
                                            <h6>بطاقة ائتمان/خصم</h6>
                                            <p class="text-muted mb-0">ادخل تفاصيل بطاقتك للدفع الآمن</p>
                                        </div>
                                    </div>
                                    <div id="credit-card-form" class="mt-3">
                                        <div class="form-group">
                                            <label>رقم البطاقة</label>
                                            <input type="text" class="form-control" placeholder="1234 5678 9012 3456">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>تاريخ الانتهاء</label>
                                                    <input type="text" class="form-control" placeholder="MM/YY">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>رمز الأمان (CVV)</label>
                                                    <input type="text" class="form-control" placeholder="123">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>اسم حامل البطاقة</label>
                                            <input type="text" class="form-control" placeholder="كما هو مدون على البطاقة">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="payment-method" data-method="paypal">
                                    <div class="d-flex align-items-center">
                                        <i class="fab fa-paypal fa-2x mr-3 text-primary"></i>
                                        <div>
                                            <h6>باي بال</h6>
                                            <p class="text-muted mb-0">سوف يتم توجيهك إلى موقع باي بال</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="payment-method" data-method="bank-transfer">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-university fa-2x mr-3 text-primary"></i>
                                        <div>
                                            <h6>حوالة بنكية</h6>
                                            <p class="text-muted mb-0">قم بالتحويل إلى حسابنا البنكي</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Summary -->
                            <div class="col-md-4">
                                <div class="payment-summary">
                                    <h5 class="mb-4">ملخص الدفع</h5>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>استشارة نفسية</span>
                                        <span>5,000 د.ج</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>ضريبة القيمة المضافة</span>
                                        <span>0 د.ج</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-3">
                                        <strong>المجموع</strong>
                                        <strong>5,000 د.ج</strong>
                                    </div>
                                    <button class="btn btn-pay btn-block">
                                        <i class="fas fa-lock mr-2"></i> تأكيد الدفع
                                    </button>
                                    <p class="text-muted small mt-3">
                                        <i class="fas fa-lock mr-2"></i> جميع عمليات الدفع مشفرة وآمنة
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        // Payment method selection
        $('.payment-method').click(function() {
            $('.payment-method').removeClass('selected');
            $(this).addClass('selected');
            
            // Hide all forms
            $('#credit-card-form').hide();
            
            // Show selected form
            if ($(this).data('method') === 'credit-card') {
                $('#credit-card-form').show();
            }
        });
        
        // Initially show credit card form
        $('#credit-card-form').show();
    });
</script>
</body>
</html>