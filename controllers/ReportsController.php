<?php
require_once ROOT_PATH . '/controllers/BaseController.php';
require_once ROOT_PATH . '/models/Telemetry.php';
require_once ROOT_PATH . '/models/Budget.php';

class ReportsController extends BaseController
{
    // GET /reports
    public function index(): void
    {
        $this->requireAuth();
        $uid   = (int)$this->currentUser['id'];
        $tel   = new Telemetry();
        $bud   = new Budget();

        $date           = $this->get('date', date('Y-m-d'));
        $dailySummary   = $tel->dailySummary($date);
        $weeklySummary  = $tel->weeklySummary();
        $forecast       = $tel->predictMonthlyBill($uid);
        $co2Today       = $tel->carbonFootprintToday();
        $budgets        = $bud->dashboardSummary($uid);

        // Uploaded files list
        $files = Database::getInstance()->fetchAll(
            'SELECT * FROM uploaded_files WHERE user_id=? ORDER BY created_at DESC',
            [$uid]
        );

        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();
        $this->render('reports/index', compact(
            'date','dailySummary','weeklySummary','forecast',
            'co2Today','budgets','files','flash','csrf'
        ));
    }

    // POST /reports/upload
    public function upload(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $uid  = (int)$this->currentUser['id'];
        $file = $_FILES['report_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('danger', 'Upload failed. Please try again.');
            $this->redirect('/reports');
        }

        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $this->flash('danger', 'File exceeds 5MB limit.');
            $this->redirect('/reports');
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_TYPES)) {
            $this->flash('danger', 'Only JPEG, PNG, and PDF files are allowed.');
            $this->redirect('/reports');
        }

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('report_', true) . '.' . $ext;
        $dest     = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->flash('danger', 'Could not save file.');
            $this->redirect('/reports');
        }

        Database::getInstance()->insert(
            'INSERT INTO uploaded_files (user_id, filename, original, mime_type, size_bytes)
             VALUES (?,?,?,?,?)',
            [$uid, $filename, $this->sanitize($file['name']), $mimeType, $file['size']]
        );

        $this->audit->audit($uid, 'reports', 'FILE_UPLOAD', null, $file['name']);
        $this->flash('success', 'File uploaded successfully.');
        $this->redirect('/reports');
    }

    // GET /reports/pdf — generate & stream a basic PDF report
    public function generatePdf(): void
    {
        $this->requireAuth();
        $uid  = (int)$this->currentUser['id'];
        $tel  = new Telemetry();
        $bud  = new Budget();

        $forecast = $tel->predictMonthlyBill($uid);
        $budgets  = $bud->dashboardSummary($uid);
        $weekly   = $tel->weeklySummary();
        $co2      = $tel->carbonFootprintToday();
        $user     = $this->currentUser;

        // Simple HTML→PDF via browser print; a real deployment would use TCPDF or mPDF
        header('Content-Type: text/html; charset=utf-8');
        include ROOT_PATH . '/views/reports/pdf_template.php';
        exit;
    }
}
