<?php
require_once ROOT_PATH . '/controllers/BaseController.php';
require_once ROOT_PATH . '/models/Appliance.php';

class ApplianceController extends BaseController
{
    private Appliance $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Appliance();
    }

    // GET /appliances
    public function index(): void
    {
        $this->requireAuth();
        $appliances  = $this->model->all();
        $maintenance = $this->model->needsMaintenance();
        $rooms       = Database::getInstance()->fetchAll('SELECT * FROM rooms ORDER BY name');
        $flash       = $this->getFlash();
        $this->render('appliances/index', compact('appliances', 'maintenance', 'rooms', 'flash'));
    }

    // GET /appliances/create
    public function create(): void
    {
        $this->requireRole('admin');
        $rooms = Database::getInstance()->fetchAll('SELECT * FROM rooms ORDER BY name');
        $csrf  = $this->csrfToken();
        $this->render('appliances/create', compact('rooms', 'csrf'));
    }

    // POST /appliances/store
    public function store(): void
    {
        $this->requireRole('admin');
        $this->verifyCsrf();

        $data = [
            'room_id'       => (int)$this->post('room_id'),
            'name'          => $this->post('name'),
            'type'          => $this->post('type'),
            'status'        => $this->post('status', 'OFF'),
            'base_wattage'  => (float)$this->post('base_wattage'),
            'age_hours'     => (int)$this->post('age_hours', 0),
            'resource_type' => $this->post('resource_type', 'electricity'),
        ];

        if (empty($data['name']) || $data['base_wattage'] <= 0) {
            $this->flash('danger', 'Name and wattage are required.');
            $this->redirect('/appliances/create');
        }

        $id = $this->model->create($data);
        $this->audit->audit($this->currentUser['id'], 'appliances', 'CREATE', null, $data);
        $this->flash('success', 'Appliance added successfully.');
        $this->redirect('/appliances');
    }

    // GET /appliances/edit/{id}
    public function edit(int $id): void
    {
        $this->requireRole('admin');
        $appliance = $this->model->findById($id);
        if (!$appliance) { $this->flash('danger','Appliance not found.'); $this->redirect('/appliances'); }
        $rooms = Database::getInstance()->fetchAll('SELECT * FROM rooms ORDER BY name');
        $csrf  = $this->csrfToken();
        $this->render('appliances/edit', compact('appliance', 'rooms', 'csrf'));
    }

    // POST /appliances/update/{id}
    public function update(int $id): void
    {
        $this->requireRole('admin');
        $this->verifyCsrf();

        $old  = $this->model->findById($id);
        $data = [
            'room_id'       => (int)$this->post('room_id'),
            'name'          => $this->post('name'),
            'type'          => $this->post('type'),
            'status'        => $this->post('status'),
            'base_wattage'  => (float)$this->post('base_wattage'),
            'resource_type' => $this->post('resource_type'),
        ];

        $this->model->update($id, $data);
        $this->audit->audit($this->currentUser['id'], 'appliances', 'UPDATE', $old, $data);
        $this->flash('success', 'Appliance updated.');
        $this->redirect('/appliances');
    }

    // POST /appliances/delete/{id}
    public function delete(int $id): void
    {
        $this->requireRole('admin');
        $this->verifyCsrf();
        $old = $this->model->findById($id);
        $this->model->delete($id);
        $this->audit->audit($this->currentUser['id'], 'appliances', 'DELETE', $old);
        $this->flash('success', 'Appliance deleted.');
        $this->redirect('/appliances');
    }

    // POST /appliances/toggle/{id}  — AJAX
    public function toggle(int $id): void
    {
        $this->requireAuth();
        $appliance = $this->model->findById($id);
        if (!$appliance) { $this->json(['error' => 'Not found'], 404); }

        // RBAC: guests cannot toggle appliances
        if ($this->currentUser['role'] === 'guest') {
            $this->json(['error' => 'Insufficient permissions.'], 403);
        }

        $newStatus = $appliance['status'] === 'ON' ? 'OFF' : 'ON';
        $this->model->setStatus($id, $newStatus);
        $this->audit->audit(
            $this->currentUser['id'], 'appliances', 'TOGGLE',
            $appliance['status'], $newStatus
        );
        $this->json(['success' => true, 'status' => $newStatus]);
    }
}
