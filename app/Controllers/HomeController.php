<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Project;
use App\Models\Setting;

final class HomeController extends BaseController
{
    public function index(): void
    {
        $projects = Project::published();
        $featured = Project::featured(3);

        $this->view('pages/home', array_merge(
            $this->meta(
                Setting::value('company_name') . ' — Creative Studio for Motion, Design & 3D',
                'An independent creative studio crafting motion graphics, design, video and immersive 3D experiences. We create what doesn\'t exist.'
            ),
            [
                'bodyClass' => 'page-home',
                'loadHero3d' => true,
                'projects'  => $projects,
                'featured'  => $featured,
                'counts'    => Project::countsByCategory(),
                'services'  => $this->serviceList(),
                'process'   => $this->processSteps(),
                'software'  => $this->softwareList(),
            ]
        ));
    }

    public function services(): void
    {
        $this->view('pages/services', array_merge(
            $this->meta(
                'Services — ' . Setting::value('company_name'),
                'Motion graphics, graphic design, video editing, 3D and CGI, Unreal Engine production and creative direction.'
            ),
            ['bodyClass' => 'page-services', 'services' => $this->serviceList()]
        ));
    }

    public function about(): void
    {
        $this->view('pages/about', array_merge(
            $this->meta(
                'About — ' . Setting::value('company_name'),
                'NONXSTUDIOS is an independent creative studio working across design, motion, video and real-time 3D production.'
            ),
            ['bodyClass' => 'page-about', 'software' => $this->softwareList(), 'process' => $this->processSteps()]
        ));
    }

    public function privacy(): void
    {
        $this->view('pages/privacy', $this->meta('Privacy Policy — ' . Setting::value('company_name'), 'How NONXSTUDIOS handles the information you share with us.'));
    }

    public function terms(): void
    {
        $this->view('pages/terms', $this->meta('Terms of Service — ' . Setting::value('company_name'), 'The terms that govern use of the NONXSTUDIOS website and services.'));
    }

    public function faq(): void
    {
        $this->view('pages/faq', $this->meta(
            'FAQ — ' . Setting::value('company_name'),
            'Answers to common questions about working with NONXSTUDIOS: timelines, budgets, process, revisions and deliverables.'
        ) + ['faqs' => $this->faqList()]);
    }

    /** @return array<int,array{q:string,a:string}> */
    private function faqList(): array
    {
        return [
            [
                'q' => 'What kinds of projects do you take on?',
                'a' => 'Motion graphics, brand and graphic design, video editing, 3D modelling and rendering, real-time 3D in Unreal Engine, product visualisation, cinematic animation, title sequences and social media creative. If your project is visual and needs craft, it is likely a fit.',
            ],
            [
                'q' => 'How does a project usually start?',
                'a' => 'You send an enquiry through the contact form describing what you are imagining. We reply to arrange a short call, then send a written proposal covering scope, timeline, deliverables and cost. Work begins once the proposal is approved and a deposit is received.',
            ],
            [
                'q' => 'How long does a project take?',
                'a' => 'A focused motion piece or a small batch of social assets typically takes one to three weeks. A full brand identity, product visualisation set or cinematic sequence usually runs four to eight weeks. Timelines are confirmed in writing before we begin.',
            ],
            [
                'q' => 'How is pricing structured?',
                'a' => 'Projects are quoted as a fixed fee based on scope, not billed hourly. The budget ranges on the contact form give us a starting point; we confirm an exact figure in the proposal once the brief is clear. There are no hidden extras.',
            ],
            [
                'q' => 'How many revisions are included?',
                'a' => 'Every proposal includes a defined number of revision rounds, usually two or three, at named stages. That keeps feedback structured and avoids endless loops. Additional rounds are always available and quoted transparently.',
            ],
            [
                'q' => 'What do you need from me to begin?',
                'a' => 'Your brand assets if you have them (logo, fonts, colour values, existing guidelines), any reference material you like, and a clear sense of the goal and audience. If assets do not exist yet, we can build them as part of the project.',
            ],
            [
                'q' => 'What files do I receive at the end?',
                'a' => 'Deliverables are supplied in the formats your channels need: high-resolution and web-optimised video, source renders, stills at print and screen resolutions, and exported graphics. Project source files can be included on request.',
            ],
            [
                'q' => 'Do you work with clients remotely?',
                'a' => 'Yes. The studio works remotely by default, sharing progress through review links and scheduled calls. Time zones have not been a barrier; we agree a communication rhythm at the start of each project.',
            ],
            [
                'q' => 'Who owns the finished work?',
                'a' => 'On final payment, you receive full usage rights to the delivered work for the purposes agreed in the proposal. We retain the right to show the work in our portfolio unless you ask us not to, and we are happy to agree confidentiality in writing.',
            ],
            [
                'q' => 'Do you offer ongoing support?',
                'a' => 'Yes. Many clients return for seasonal campaigns, additional social content or updates to existing assets. Ongoing work is quoted per batch, or as a monthly retainer for a steady stream of creative output.',
            ],
        ];
    }

    /** @return array<int,array{title:string,desc:string,icon:string}> */
    private function serviceList(): array
    {
        return [
            ['title' => 'Motion Graphics',   'icon' => 'motion',  'desc' => 'Dynamic typography, kinetic visuals, brand animations, and cinematic sequences.'],
            ['title' => 'Graphic Design',    'icon' => 'design',  'desc' => 'Visual identities, campaign graphics, digital artwork, and promotional creatives.'],
            ['title' => 'Video Editing',     'icon' => 'video',   'desc' => 'Cinematic editing, promotional films, trailers, social media videos, and storytelling.'],
            ['title' => '3D Design & CGI',   'icon' => 'cube',    'desc' => '3D modeling, texturing, lighting, rendering, and product visualization with Blender.'],
            ['title' => 'Unreal Engine',     'icon' => 'engine',  'desc' => 'Real-time environments, cinematic scenes, virtual production concepts, and immersive experiences.'],
            ['title' => 'Creative Direction','icon' => 'compass', 'desc' => 'Visual development, art direction, concept design, and campaign storytelling.'],
        ];
    }

    /** @return array<int,array{num:string,title:string,desc:string,meta:string}> */
    private function processSteps(): array
    {
        return [
            ['num' => '01', 'title' => 'Discover', 'meta' => 'Research', 'desc' => 'Understand the project, goals, and creative direction.'],
            ['num' => '02', 'title' => 'Design',   'meta' => 'Concepts', 'desc' => 'Develop concepts, references, and visual treatments.'],
            ['num' => '03', 'title' => 'Create',   'meta' => 'Production', 'desc' => 'Produce, animate, render, edit, and refine the work.'],
            ['num' => '04', 'title' => 'Deliver',  'meta' => 'Handoff', 'desc' => 'Prepare final assets for the intended platforms and formats.'],
        ];
    }

    /** @return array<int,array{name:string,mark:string,use:string}> */
    private function softwareList(): array
    {
        return [
            ['name' => 'Blender',            'mark' => 'Bl', 'use' => '3D & Rendering'],
            ['name' => 'Unreal Engine',      'mark' => 'UE', 'use' => 'Real-Time 3D'],
            ['name' => 'After Effects',      'mark' => 'Ae', 'use' => 'Motion Graphics'],
            ['name' => 'Premiere Pro',       'mark' => 'Pr', 'use' => 'Video Editing'],
            ['name' => 'Photoshop',          'mark' => 'Ps', 'use' => 'Digital Imaging'],
            ['name' => 'Illustrator',        'mark' => 'Ai', 'use' => 'Vector & Identity'],
        ];
    }
}
