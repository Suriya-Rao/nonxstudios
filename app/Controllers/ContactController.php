<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Inquiry;
use App\Models\Setting;
use App\Services\Mailer;

final class ContactController extends BaseController
{
    private const SERVICES = [
        'Motion Graphics', 'Graphic Design', 'Video Editing', '3D Design & CGI',
        'Unreal Engine', 'Creative Direction', 'Something else',
    ];

    private const BUDGETS = [
        'Under $1,000', '$1,000 – $5,000', '$5,000 – $10,000',
        '$10,000 – $25,000', '$25,000+', 'Not sure yet',
    ];

    private const TIMELINES = [
        'ASAP', '1–2 weeks', '1 month', '1–3 months', '3+ months', 'Flexible',
    ];

    private const CONTACT_METHODS = ['Email', 'Video call', 'Phone', 'Any'];

    public function show(): void
    {
        $this->view('pages/contact', array_merge(
            $this->meta(
                'Contact — ' . Setting::value('company_name'),
                'Tell us what you\'re imagining. We\'ll help bring it to life. Start a project with NONXSTUDIOS.'
            ),
            [
                'bodyClass' => 'page-contact',
                'services'  => self::SERVICES,
                'budgets'   => self::BUDGETS,
                'timelines' => self::TIMELINES,
                'methods'   => self::CONTACT_METHODS,
                'old'       => Session::pull('_old', []),
                'errors'    => Session::pull('_errors', []),
            ]
        ));
    }

    public function submit(): void
    {
        Csrf::validateOrFail(Request::input('_token'));

        $ip = Request::ip();

        // --- Honeypot: bots fill hidden fields, humans do not. -------------
        if (Request::string('website') !== '') {
            Logger::warning('Contact honeypot triggered', ['ip' => $ip]);
            Session::flash('success', 'Thanks — your message has been received.');
            Response::redirect('/contact');
        }

        // --- Rate limiting -------------------------------------------------
        $limit  = (int) config('app.security.contact_rate_limit', 3);
        $recent = Inquiry::recentCountByIp($ip);
        if ($recent >= $limit) {
            Logger::warning('Contact rate limit hit', ['ip' => $ip, 'count' => $recent]);
            Session::flash('error', 'You have submitted several messages recently. Please wait a little while before trying again.');
            Response::redirect('/contact');
        }

        // --- Validation ----------------------------------------------------
        $data = [
            'name'           => Request::string('name'),
            'email'          => Request::string('email'),
            'company'        => Request::string('company'),
            'service'        => Request::string('service'),
            'budget'         => Request::string('budget'),
            'timeline'       => Request::string('timeline'),
            'description'    => Request::string('description'),
            'contact_method' => Request::string('contact_method'),
        ];

        $validator = new Validator($data, [
            'name'        => 'required|min:2|max:120',
            'email'       => 'required|email|max:190',
            'company'     => 'max:190',
            'service'     => 'required|in:' . implode(';', self::SERVICES),
            'budget'      => 'in:' . implode(';', array_merge([''], self::BUDGETS)),
            'timeline'    => 'in:' . implode(';', array_merge([''], self::TIMELINES)),
            'description' => 'required|min:20|max:5000',
            'contact_method' => 'in:' . implode(';', array_merge([''], self::CONTACT_METHODS)),
        ]);

        if (!$validator->validate()) {
            Session::put('_old', $data);
            Session::put('_errors', $validator->errors());
            Session::flash('error', 'Please check the highlighted fields and try again.');
            // PRG: redirect back so a refresh does not resubmit.
            Response::redirect('/contact');
        }

        $clean = $validator->clean();

        // --- Persist -------------------------------------------------------
        try {
            $id = Inquiry::create(array_merge($clean, [
                'ip'         => $ip,
                'user_agent' => Request::userAgent(),
            ]));
        } catch (\Throwable $e) {
            Logger::error('Failed to store inquiry: ' . $e->getMessage());
            Session::flash('error', 'We could not save your message right now. Please email us directly and we will reply as soon as possible.');
            Response::redirect('/contact');
        }

        // --- Notify (best effort; never blocks the visitor) ----------------
        $this->notify($clean, $id);

        Logger::info('New inquiry received', ['id' => $id, 'service' => $clean['service']]);
        Session::flash('success', 'Thank you — your message is with us. We typically reply within one to two business days.');
        Response::redirect('/contact');
    }

    private function notify(array $data, int $id): void
    {
        if (!Mailer::enabled()) {
            return;
        }

        $to = (string) config('app.mail.to');
        if ($to === '') {
            $to = Setting::value('contact_email');
        }
        if ($to === '') {
            return;
        }

        $rows = '';
        foreach ([
            'Name' => $data['name'], 'Email' => $data['email'], 'Company' => $data['company'],
            'Service' => $data['service'], 'Budget' => $data['budget'], 'Timeline' => $data['timeline'],
            'Preferred contact' => $data['contact_method'],
        ] as $label => $value) {
            if ((string) $value === '') { continue; }
            $rows .= '<tr><td style="padding:6px 12px 6px 0;color:#888">' . htmlspecialchars($label, ENT_QUOTES)
                   . '</td><td style="padding:6px 0">' . htmlspecialchars((string) $value, ENT_QUOTES) . '</td></tr>';
        }

        $body = '<div style="font-family:Arial,sans-serif;max-width:640px">'
              . '<h2 style="font-size:18px">New project inquiry #' . $id . '</h2>'
              . '<table style="border-collapse:collapse;font-size:14px">' . $rows . '</table>'
              . '<h3 style="font-size:15px;margin-top:24px">Project description</h3>'
              . '<p style="white-space:pre-wrap;font-size:14px">' . htmlspecialchars((string) $data['description'], ENT_QUOTES) . '</p>'
              . '</div>';

        Mailer::send($to, 'New inquiry: ' . $data['service'] . ' — ' . $data['name'], $body, (string) $data['email']);
    }
}
