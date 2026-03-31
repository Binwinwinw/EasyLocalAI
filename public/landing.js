/**
 * landing.js - Moteur Kinetic 3D (Three.js)
 * Gère le rendu des sphères irisées et l'interaction parallaxe.
 */

let scene, camera, renderer, spheres = [];
let mouseX = 0, mouseY = 0;
let targetScroll = 0;

function init() {
    const container = document.getElementById('canvas-container');
    if (!container) return;

    // 1. SCÈNE & CAMÉRA
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 20;

    // 2. RENDERER
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(window.innerWidth, window.innerHeight);
    container.appendChild(renderer.domElement);

    // 3. LUMIÈRES
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
    scene.add(ambientLight);

    const light1 = new THREE.PointLight(0x7c3aed, 2, 50); // Violet
    light1.position.set(10, 10, 10);
    scene.add(light1);

    const light2 = new THREE.PointLight(0x0ea5e9, 2, 50); // Blue
    light2.position.set(-10, -10, 10);
    scene.add(light2);

    // 4. CRÉATION DES SPHÈRES (KINETIC)
    const geometry = new THREE.IcosahedronGeometry(1, 15);
    
    // Matériau Iridescent / Crystal
    const createMaterial = (color) => new THREE.MeshPhysicalMaterial({
        color: color,
        metalness: 0.1,
        roughness: 0.05,
        transmission: 0.9,
        thickness: 0.5,
        transparent: true,
        opacity: 0.8,
        reflectivity: 1,
        clearcoat: 1,
        clearcoatRoughness: 0.1,
        emissive: color,
        emissiveIntensity: 0.2
    });

    const sphereData = [
        { size: 5, pos: [10, 5, -5], speed: 0.002, color: 0x7c3aed },
        { size: 8, pos: [-12, -8, 2], speed: 0.001, color: 0x0ea5e9 },
        { size: 3, pos: [2, -12, -2], speed: 0.003, color: 0xd946ef }
    ];

    sphereData.forEach(data => {
        const mesh = new THREE.Mesh(geometry, createMaterial(data.color));
        mesh.scale.set(data.size, data.size, data.size);
        mesh.position.set(...data.pos);
        mesh.userData = { 
            baseX: data.pos[0], 
            baseY: data.pos[1], 
            baseZ: data.pos[2],
            rotSpeed: data.speed 
        };
        scene.add(mesh);
        spheres.push(mesh);
    });

    // 5. EVENTS
    window.addEventListener('resize', onWindowResize);
    document.addEventListener('mousemove', onMouseMove);
    window.addEventListener('scroll', onScroll);

    animate();
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function onMouseMove(event) {
    mouseX = (event.clientX - window.innerWidth / 2) / 100;
    mouseY = (event.clientY - window.innerHeight / 2) / 100;
}

function onScroll() {
    targetScroll = window.scrollY / 100;
}

function animate() {
    requestAnimationFrame(animate);

    const time = Date.now() * 0.001;

    spheres.forEach((s, i) => {
        const ud = s.userData;
        
        // Flottement sinusoïdal
        s.position.y = ud.baseY + Math.sin(time + i) * 2;
        s.position.x = ud.baseX + Math.cos(time * 0.5 + i) * 1;

        // Réaction à la souris (Interactivité)
        s.position.x += (mouseX * (i + 1) * 0.5 - (s.position.x - ud.baseX)) * 0.1;
        s.position.y += (-mouseY * (i + 1) * 0.5 - (s.position.y - ud.baseY)) * 0.1;

        // Parallaxe au Scroll
        s.position.z = ud.baseZ + targetScroll * (i + 1) * 1.5;

        // Rotation lente
        s.rotation.x += ud.rotSpeed;
        s.rotation.y += ud.rotSpeed * 1.1;
    });

    renderer.render(scene, camera);
}

// Lancement au chargement
document.addEventListener('DOMContentLoaded', init);
