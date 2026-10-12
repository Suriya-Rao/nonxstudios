/* ==========================================================================
   NONXSTUDIOS — hero 3D composition (Three.js)
   A restrained abstract scene: a faceted metallic form, a wireframe shell and
   a particle field. Loaded only on the homepage, only when it is appropriate
   (fine pointer / capable device / motion allowed). If anything is missing the
   CSS gradient fallback underneath simply remains visible.
   ========================================================================== */
(function () {
    'use strict';

    var mount = document.querySelector('[data-hero-canvas]');
    if (!mount) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer  = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var smallScreen  = window.matchMedia('(max-width: 860px)').matches;

    // Respect user preference and avoid loading a heavy scene where it would
    // hurt more than help. The CSS fallback stays in place.
    if (reduceMotion || typeof window.THREE === 'undefined') {
        mount.setAttribute('data-disabled', 'true');
        return;
    }

    var THREE = window.THREE;
    var ACCENT = 0xc7ff35;

    var scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x080808, 0.055);

    var camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.set(0, 0, 9);

    var renderer;
    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    } catch (e) {
        mount.setAttribute('data-disabled', 'true');
        return;
    }

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, smallScreen ? 1.5 : 2));
    renderer.setClearColor(0x000000, 0);
    mount.appendChild(renderer.domElement);

    var group = new THREE.Group();
    scene.add(group);

    // --- Central faceted form ------------------------------------------
    var coreGeo = new THREE.IcosahedronGeometry(2.1, 1);
    var coreMat = new THREE.MeshStandardMaterial({
        color: 0x141414,
        metalness: 0.92,
        roughness: 0.28,
        flatShading: true
    });
    var core = new THREE.Mesh(coreGeo, coreMat);
    group.add(core);

    // --- Wireframe shell -------------------------------------------------
    var shellGeo = new THREE.IcosahedronGeometry(2.75, 1);
    var shellMat = new THREE.MeshBasicMaterial({
        color: ACCENT,
        wireframe: true,
        transparent: true,
        opacity: 0.16
    });
    var shell = new THREE.Mesh(shellGeo, shellMat);
    group.add(shell);

    // --- Accent ring -----------------------------------------------------
    var ringGeo = new THREE.TorusGeometry(3.4, 0.012, 8, 160);
    var ringMat = new THREE.MeshBasicMaterial({ color: ACCENT, transparent: true, opacity: 0.5 });
    var ring = new THREE.Mesh(ringGeo, ringMat);
    ring.rotation.x = Math.PI / 2.4;
    group.add(ring);

    var ring2 = new THREE.Mesh(
        new THREE.TorusGeometry(4.0, 0.008, 8, 160),
        new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.12 })
    );
    ring2.rotation.x = Math.PI / 1.7;
    ring2.rotation.y = 0.6;
    group.add(ring2);

    // --- Particle field --------------------------------------------------
    var count = smallScreen ? 320 : 800;
    var positions = new Float32Array(count * 3);
    for (var i = 0; i < count; i++) {
        var r = 6 + Math.random() * 16;
        var theta = Math.random() * Math.PI * 2;
        var phi = Math.acos(2 * Math.random() - 1);
        positions[i * 3]     = r * Math.sin(phi) * Math.cos(theta);
        positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
        positions[i * 3 + 2] = r * Math.cos(phi);
    }
    var pGeo = new THREE.BufferGeometry();
    pGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    var pMat = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 0.035,
        transparent: true,
        opacity: 0.55,
        sizeAttenuation: true
    });
    var particles = new THREE.Points(pGeo, pMat);
    scene.add(particles);

    // --- Lighting --------------------------------------------------------
    scene.add(new THREE.AmbientLight(0xffffff, 0.35));

    var key = new THREE.DirectionalLight(0xffffff, 1.1);
    key.position.set(5, 6, 6);
    scene.add(key);

    var accentLight = new THREE.PointLight(ACCENT, 2.4, 30);
    accentLight.position.set(-6, -3, 4);
    scene.add(accentLight);

    var rim = new THREE.PointLight(0x88aaff, 1.2, 40);
    rim.position.set(6, 4, -8);
    scene.add(rim);

    // --- Pointer parallax -------------------------------------------------
    var targetX = 0, targetY = 0, curX = 0, curY = 0;
    if (finePointer) {
        window.addEventListener('mousemove', function (e) {
            targetX = (e.clientX / window.innerWidth - 0.5) * 2;
            targetY = (e.clientY / window.innerHeight - 0.5) * 2;
        }, { passive: true });
    }

    // --- Resize -----------------------------------------------------------
    function resize() {
        var w = mount.clientWidth || window.innerWidth;
        var h = mount.clientHeight || window.innerHeight;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize);

    // --- Animate (paused when offscreen) ---------------------------------
    var running = true;
    var clock = new THREE.Clock();

    function animate() {
        if (!running) return;
        requestAnimationFrame(animate);

        var t = clock.getElapsedTime();

        curX += (targetX - curX) * 0.04;
        curY += (targetY - curY) * 0.04;

        group.rotation.y = t * 0.12 + curX * 0.35;
        group.rotation.x = Math.sin(t * 0.18) * 0.12 + curY * 0.22;

        shell.rotation.y = -t * 0.22;
        shell.rotation.z = t * 0.06;

        ring.rotation.z  = t * 0.25;
        ring2.rotation.z = -t * 0.18;

        particles.rotation.y = t * 0.02;

        accentLight.position.x = Math.sin(t * 0.5) * 6;
        accentLight.position.y = Math.cos(t * 0.4) * 4;

        renderer.render(scene, camera);
    }

    // Only render while the hero is in view.
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var was = running;
                running = entry.isIntersecting;
                if (running && !was) { clock.start(); animate(); }
            });
        }, { threshold: 0 });
        io.observe(mount);
    }
    animate();

    // Pause when the tab is hidden.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            running = false;
        } else {
            var rect = mount.getBoundingClientRect();
            if (rect.bottom > 0 && rect.top < window.innerHeight) {
                running = true;
                clock.start();
                animate();
            }
        }
    });
})();
