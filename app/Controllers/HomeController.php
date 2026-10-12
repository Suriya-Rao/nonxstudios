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
