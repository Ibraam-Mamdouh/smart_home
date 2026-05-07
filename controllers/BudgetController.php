<?php
require_once ROOT_PATH . '/controllers/BaseController.php';
require_once ROOT_PATH . '/models/Budget.php';
require_once ROOT_PATH . '/models/Telemetry.php';

class BudgetController extends BaseController
{
    private Budget $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Budget();
    }

    // GET /budget
    public function index(): void
    {
        $this->requireAuth();
        $uid      = (int)$this->currentUser['id'];
        $budgets  = $this->model->dashboardSummary($uid);
        $alerts   = $this->model->allAlerts($uid);
        $forecast = (new Telemetry())->predictMonthlyBill($uid);

        // Suggested budgets for next month
        $suggestions = [];
        foreach (['electricity','water','gas'] as $r) {
            $suggestions[$r] = $this->model->suggestNext($uid, $r);
        }

        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();
        $this->render('budget/index', compact('budgets','alerts','forecast','suggestions','flash','csrf'));
    }

    // POST /budget/save
    public function save(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $uid      = (int)$this->currentUser['id'];
        $resource = $this->post('resource_type');
        $limit    = (float)$this->post('monthly_limit');

        if (!in_array($resource, ['electricity','water','gas'])) {
            $this->flash('danger','Invalid resource type.');
            $this->redirect('/budget');
        }
        if ($limit <= 0) {
            $this->flash('danger','Limit must be positive.');
            $this->redirect('/budget');
        }

        $this->model->upsert($uid, $resource, $limit);
        $this->audit->audit($uid, 'budget', 'SET_LIMIT', null, compact('resource','limit'));
        $this->flash('success', ucfirst($resource) . ' budget updated.');
        $this->redirect('/budget');
    }

    // POST /budget/mark-read — AJAX
    public function markRead(): void
    {
        $this->requireAuth();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) $this->model->markRead($id);
        $this->json(['success' => true]);
    }

    // POST /budget/mark-all-read
    public function markAllRead(): void
    {
        $this->requireAuth();
        $this->model->markAllRead((int)$this->currentUser['id']);
        $this->flash('success', 'All alerts marked as read.');
        $this->redirect('/budget');
    }
}
