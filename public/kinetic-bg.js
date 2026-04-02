/**
 * EasyLocalAI - Kinetic 3D Bubble Engine
 * Moteur de particules interactif pour Login et Landing Page.
 */

class KineticEngine {
    constructor(containerId = 'canvas-container') {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        
        this.bubbles = [];
        this.mouse = new THREE.Vector2();
        this.raycaster = new THREE.Raycaster();
        
        this.init();
    }

    init() {
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);

        this.camera.position.z = 5;

        // Lumières Premium
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        this.scene.add(ambientLight);

        const pointLight1 = new THREE.PointLight(0x7c3aed, 5, 20); // Violet
        pointLight1.position.set(5, 5, 5);
        this.scene.add(pointLight1);

        const pointLight2 = new THREE.PointLight(0x0ea5e9, 5, 20); // Blue
        pointLight2.position.set(-5, -5, 5);
        this.scene.add(pointLight2);

        // 1. Starfield (Optimized Background)
        this.createStarfield(1000);

        // 2. Interactive Premium Bubbles
        this.createBubbles(20);

        // Events
        window.addEventListener('resize', () => this.onResize());
        window.addEventListener('mousemove', (e) => this.onMouseMove(e));

        this.animate();
    }

    createStarfield(count) {
        const geometry = new THREE.BufferGeometry();
        const vertices = [];
        for (let i = 0; i < count; i++) {
            vertices.push((Math.random() - 0.5) * 20, (Math.random() - 0.5) * 20, (Math.random() - 0.5) * 10);
        }
        geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
        const material = new THREE.PointsMaterial({ color: 0x7c3aed, size: 0.02, transparent: true, opacity: 0.5 });
        this.starfield = new THREE.Points(geometry, material);
        this.scene.add(this.starfield);
    }

    createBubbles(count) {
        const geometry = new THREE.SphereGeometry(1, 32, 32);
        
        for (let i = 0; i < count; i++) {
            const material = new THREE.MeshPhysicalMaterial({
                color: 0xffffff,
                metalness: 0,
                roughness: 0.05,
                transmission: 0.9,
                thickness: 1,
                transparent: true,
                opacity: 0.2,
            });

            const mesh = new THREE.Mesh(geometry, material);
            const s = Math.random() * 0.4 + 0.1;
            mesh.scale.set(s, s, s);
            mesh.position.set((Math.random() - 0.5) * 12, (Math.random() - 0.5) * 10, (Math.random() - 0.5) * 5);

            mesh.userData = {
                velocity: new THREE.Vector3((Math.random() - 0.5) * 0.003, (Math.random() - 0.5) * 0.003, 0),
                originalScale: s,
                pulse: Math.random() * Math.PI
            };

            this.bubbles.push(mesh);
            this.scene.add(mesh);
        }
    }

    onMouseMove(event) {
        this.mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
        this.mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;
    }

    onResize() {
        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, window.innerHeight);
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        const targetX = this.mouse.x * 5;
        const targetY = this.mouse.y * 5;

        this.bubbles.forEach(b => {
            // Movement
            b.position.add(b.userData.velocity);
            
            // Magnetic Easing (Agency-Agents pattern)
            const dist = b.position.distanceTo(new THREE.Vector3(targetX, targetY, 0));
            if (dist < 3) {
                const force = (3 - dist) / 100;
                const attraction = new THREE.Vector3(targetX, targetY, 0).sub(b.position).multiplyScalar(force);
                b.position.add(attraction);
            }

            // Pulse
            b.userData.pulse += 0.005;
            const s = b.userData.originalScale + Math.sin(b.userData.pulse) * 0.02;
            b.scale.set(s, s, s);

            // Wrap
            if (Math.abs(b.position.x) > 8) b.position.x *= -0.99;
            if (Math.abs(b.position.y) > 6) b.position.y *= -0.99;
        });

        if (this.starfield) this.starfield.rotation.y += 0.0005;
        this.scene.rotation.y += 0.0002;

        this.renderer.render(this.scene, this.camera);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('canvas-container')) {
        window.kineticEngine = new KineticEngine();
    }
});
