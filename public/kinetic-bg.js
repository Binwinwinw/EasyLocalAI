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

        // Lumières
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
        this.scene.add(ambientLight);

        const pointLight = new THREE.PointLight(0x6366f1, 2);
        pointLight.position.set(2, 3, 4);
        this.scene.add(pointLight);

        const pointLight2 = new THREE.PointLight(0xa855f7, 2);
        pointLight2.position.set(-2, -3, 4);
        this.scene.add(pointLight2);

        // Création des bulles
        this.createBubbles(25);

        // Events
        window.addEventListener('resize', () => this.onResize());
        window.addEventListener('mousemove', (e) => this.onMouseMove(e));

        this.animate();
    }

    createBubbles(count) {
        const geometry = new THREE.SphereGeometry(1, 32, 32);
        
        for (let i = 0; i < count; i++) {
            const material = new THREE.MeshPhysicalMaterial({
                color: 0xffffff,
                metalness: 0,
                roughness: 0.1,
                transmission: 0.95,
                thickness: 0.5,
                ior: 1.5,
                transparent: true,
                opacity: 0.15,
                envMapIntensity: 1
            });

            const mesh = new THREE.Mesh(geometry, material);
            
            // Randomize
            const s = Math.random() * 0.5 + 0.2;
            mesh.scale.set(s, s, s);
            
            mesh.position.x = (Math.random() - 0.5) * 10;
            mesh.position.y = (Math.random() - 0.5) * 10;
            mesh.position.z = (Math.random() - 0.5) * 5;

            // Velocities
            mesh.userData = {
                velocity: new THREE.Vector3(
                    (Math.random() - 0.5) * 0.005,
                    (Math.random() - 0.5) * 0.005,
                    (Math.random() - 0.5) * 0.002
                ),
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

        this.bubbles.forEach(b => {
            // Mouvement flottant
            b.position.add(b.userData.velocity);
            
            // Pulse effect
            b.userData.pulse += 0.01;
            const s = b.userData.originalScale + Math.sin(b.userData.pulse) * 0.05;
            b.scale.set(s, s, s);

            // Interaction Mouse (Rejet doux)
            this.raycaster.setFromCamera(this.mouse, this.camera);
            const dist = b.position.distanceTo(new THREE.Vector3(this.mouse.x * 5, this.mouse.y * 5, 0));
            
            if (dist < 2) {
                const dir = b.position.clone().sub(new THREE.Vector3(this.mouse.x * 5, this.mouse.y * 5, 0)).normalize();
                b.position.add(dir.multiplyScalar(0.02));
            }

            // Boundary wrap
            if (Math.abs(b.position.x) > 6) b.position.x *= -0.98;
            if (Math.abs(b.position.y) > 4) b.position.y *= -0.98;
        });

        // Rotation de la scène légère
        this.scene.rotation.y += 0.001;

        this.renderer.render(this.scene, this.camera);
    }
}

// Auto-init si le container existe
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('canvas-container')) {
        window.kineticEngine = new KineticEngine();
    }
});
