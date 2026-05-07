<?php
require_once ROOT_PATH . '/controllers/BaseController.php';
require_once ROOT_PATH . '/models/Appliance.php';
require_once ROOT_PATH . '/models/Telemetry.php';
require_once ROOT_PATH . '/models/Budget.php';

class DashboardController extends BaseController
{
    // GET /dashboard
    public function index(): void
    {
        $this->requireAuth();

        $appliance = new Appliance();
        $telemetry = new Telemetry();
        $budget    = new Budget();

        $uid = (int)$this->currentUser['id'];

        // KPI counts
        $statusCounts  = $appliance->countByStatus();
        $applianceList = $appliance->all();

        // Consumption summary
        $monthlySummary = $telemetry->monthlySummaryByResource();
        $weeklySummary  = $telemetry->weeklySummary();
        $co2Today       = $telemetry->carbonFootprintToday();

        // Billing forecast
        $forecast = $telemetry->predictMonthlyBill($uid);

        // Budget status
        $budgets  = $budget->dashboardSummary($uid);

        // Unread alerts
        $alerts   = $budget->unreadAlerts($uid, 5);

        // Anomalies
        $anomalies = $telemetry->detectAnomalies();

        // Reward points
        $totalPoints = $this->userModel->totalRewardPoints($uid);

        $flash = $this->getFlash();

        $this->render('dashboard/index', compact(
            'statusCounts', 'applianceList', 'monthlySummary',
            'weeklySummary', 'co2Today', 'forecast',
            'budgets', 'alerts', 'anomalies', 'totalPoints', 'flash'
        ));
    }
}
