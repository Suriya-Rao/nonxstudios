<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Inquiry;
use App\Models\Project;
use App\Models\Setting;

final class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/dashboard', [
            'title'      => 'Dashboard',
            'active'     => 'dashboard',
            'projectTotal'   => Project::countAll(),
            'projectPublished' => Project::countPublished(),
            'inquiryTotal'   => Inquiry::countAll(),
            'inquiryNew'     => Inquiry::countByStatus('new'),
            'inquiryProgress'=> Inquiry::countByStatus('in_progress'),
            'inquiryDone'    => Inquiry::countByStatus('completed'),
            'recentInquiries'=> Inquiry::recent(5),
            'recentProjects' => array_slice(Project::allForAdmin(), 0, 5),
            'settingsReady'  => Setting::get('company_name') !== null,
            'mailEnabled'    => \App\Services\Mailer::enabled(),
        ]);
    }
}
