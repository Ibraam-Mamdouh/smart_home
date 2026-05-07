<?php
require_once ROOT_PATH . '/controllers/BaseController.php';
require_once ROOT_PATH . '/models/Automation.php';

class AutomationController extends BaseController
{
    private Automation $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Automation();
    }

    // GET /automation
    public function index(): void
    {
        $this->requireAuth();
        $uid        = (int)$this->currentUser['id'];
        $rules      = $this->model->allRules($uid);
        $challenges = $this->model->activeChallenges();
        $myChallenges = $this->model->userChallenges($uid);
        $flash      = $this->getFlash();
        $csrf       = $this->csrfToken();
        $this->render('automation/index', compact('rules','challenges','myChallenges','flash','csrf'));
    }

    // POST /automation/rule/store
    public function storeRule(): void
    {
        $this->requireRole('admin');
        $this->verifyCsrf();
        $uid  = (int)$this->currentUser['id'];
        $data = [
            'user_id'            => $uid,
            'name'               => $this->post('name'),
            'condition_field'    => $this->post('condition_field'),
            'condition_operator' => $this->post('condition_operator'),
            'condition_value'    => (float)$this->post('condition_value'),
            'action_type'        => $this->post('action_type'),
            'action_value'       => $this->post('action_value'),
            'priority'           => (int)$this->post('priority', 5),
        ];
        $id = $this->model->createRule($data);
        $this->model->audit($uid, 'rules', 'CREATE', null, $data);
        $this->flash('success', 'Rule created.');
        $this->redirect('/automation');
    }

    // POST /automation/rule/delete/{id}
    public function deleteRule(int $id): void
    {
        $this->requireRole('admin');
        $this->verifyCsrf();
        $this->model->deleteRule($id);
        $this->model->audit((int)$this->currentUser['id'], 'rules', 'DELETE', $id);
        $this->flash('success', 'Rule deleted.');
        $this->redirect('/automation');
    }

    // POST /automation/rule/toggle/{id}  — AJAX
    public function toggleRule(int $id): void
    {
        $this->requireRole('admin');
        $db   = Database::getInstance();
        $rule = $db->fetchOne('SELECT * FROM automation_rules WHERE id=?', [$id]);
        if (!$rule) { $this->json(['error'=>'Not found'], 404); }
        $new = $rule['is_active'] ? 0 : 1;
        $db->execute('UPDATE automation_rules SET is_active=? WHERE id=?', [$new, $id]);
        $this->model->audit((int)$this->currentUser['id'], 'rules', 'TOGGLE', $rule['is_active'], $new);
        $this->json(['success'=>true, 'is_active'=>$new]);
    }

    // GET /automation/audit
    public function auditLog(): void
    {
        $this->requireRole('admin');
        $logs  = $this->model->auditLog(200);
        $this->render('automation/audit', compact('logs'));
    }

    // POST /automation/challenge/join/{id}
    public function joinChallenge(int $id): void
    {
        $this->requireAuth();
        $uid = (int)$this->currentUser['id'];
        $this->model->joinChallenge($uid, $id);
        $this->flash('success', 'You joined the challenge!');
        $this->redirect('/automation');
    }

    // POST /automation/vacation
    public function vacationMode(): void
    {
        $this->requireRole('admin');
        $this->verifyCsrf();
        $on = $this->post('vacation') === '1';
        $this->model->setVacationMode((int)$this->currentUser['id'], $on);
        $this->flash('success', $on ? 'Vacation Mode ON — Ultra-sensitivity enabled.' : 'Vacation Mode OFF.');
        $this->redirect('/automation');
    }
}
