<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\Inquiry;

final class InquiryController extends AdminController
{
    private const SERVICES = [
        'Motion Graphics', 'Graphic Design', 'Video Editing', '3D Design & CGI',
        'Unreal Engine', 'Creative Direction', 'Something else',
    ];

    public function index(): void
    {
        $page    = max(1, Request::int('page', 1));
        $status  = Request::string('status');
        $service = Request::string('service');
        $search  = Request::string('q');

        if ($status !== '' && !in_array($status, Inquiry::STATUSES, true)) {
            $status = '';
        }

        $result = Inquiry::paginate($page, [
            'status'  => $status,
            'service' => $service,
            'search'  => $search,
        ]);

        $this->view('admin/inquiries/index', [
            'title'    => 'Inquiries',
            'active'   => 'inquiries',
            'result'   => $result,
            'status'   => $status,
            'service'  => $service,
            'search'   => $search,
            'services' => self::SERVICES,
            'counts'   => [
                'all'         => Inquiry::countAll(),
                'new'         => Inquiry::countByStatus('new'),
                'in_progress' => Inquiry::countByStatus('in_progress'),
                'completed'   => Inquiry::countByStatus('completed'),
                'archived'    => Inquiry::countByStatus('archived'),
            ],
        ]);
    }

    public function show(array $params): void
    {
        $inquiry = Inquiry::find((int) ($params['id'] ?? 0));
        if ($inquiry === null) {
            Response::abort(404, 'That inquiry does not exist.');
        }

        $this->view('admin/inquiries/show', [
            'title'   => 'Inquiry #' . $inquiry['id'],
            'active'  => 'inquiries',
            'inquiry' => $inquiry,
        ]);
    }

    public function updateStatus(array $params): void
    {
        $this->verifyPost();

        $id     = (int) ($params['id'] ?? 0);
        $status = Request::string('status');

        if (Inquiry::find($id) === null) {
            Response::abort(404, 'That inquiry does not exist.');
        }
        if (!in_array($status, Inquiry::STATUSES, true)) {
            $this->back('/admin/inquiries/' . $id, 'error', 'That is not a valid status.');
        }

        Inquiry::updateStatus($id, $status);
        $this->back('/admin/inquiries/' . $id, 'success', 'Status updated to ' . Inquiry::statusLabel($status) . '.');
    }

    public function updateNotes(array $params): void
    {
        $this->verifyPost();

        $id    = (int) ($params['id'] ?? 0);
        $notes = (string) Request::input('internal_notes', '');

        if (Inquiry::find($id) === null) {
            Response::abort(404, 'That inquiry does not exist.');
        }
        if (mb_strlen($notes) > 5000) {
            $this->back('/admin/inquiries/' . $id, 'error', 'Notes are limited to 5000 characters.');
        }

        Inquiry::updateNotes($id, $notes);
        $this->back('/admin/inquiries/' . $id, 'success', 'Internal notes saved.');
    }

    public function destroy(array $params): void
    {
        $this->verifyPost();

        $id = (int) ($params['id'] ?? 0);
        if (Inquiry::find($id) === null) {
            Response::abort(404, 'That inquiry does not exist.');
        }

        Inquiry::delete($id);
        $this->back('/admin/inquiries', 'success', 'Inquiry deleted.');
    }
}
