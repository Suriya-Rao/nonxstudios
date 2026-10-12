<?php $email = \App\Models\Setting::value('contact_email'); ?>
<header class="page-head">
    <div class="container">
        <p class="eyebrow">Legal</p>
        <h1>Terms of Service</h1>
        <p>The terms that govern use of the NONXSTUDIOS website and services.</p>
    </div>
</header>

<section class="section">
    <div class="container">
        <div class="legal">
            <p><em>This is a template provided with the site. Review and adapt it with legal advice before publishing.</em></p>

            <h2>Use of this website</h2>
            <p>This website is provided for information about NONXSTUDIOS and our services. You agree to use it lawfully and not to interfere with its operation or security.</p>

            <h2>Portfolio content</h2>
            <p>Projects shown in the portfolio that are labelled as demo or sample content are illustrative and do not represent commissioned client work. Where client work is shown, it is displayed with permission.</p>

            <h2>Intellectual property</h2>
            <p>All site design, text, and original visual assets are the property of NONXSTUDIOS unless otherwise noted. Software names and marks referenced on this site belong to their respective owners.</p>

            <h2>Enquiries</h2>
            <p>Submitting an enquiry does not create a contract. Any engagement is governed by a separate written agreement.</p>

            <h2>Contact</h2>
            <p>Questions about these terms<?= $email !== '' ? ' can be sent to <a href="mailto:' . e($email) . '">' . e($email) . '</a>' : ' can be sent via the contact page' ?>.</p>
        </div>
    </div>
</section>
