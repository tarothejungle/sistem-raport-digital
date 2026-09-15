(function () {
    const colors = ['#2563eb', '#38bdf8', '#22d3ee', '#fbbf24', '#a78bfa', '#f8fafc'];

    function gamifiedLogin() {
        return {
            entered: false,
            particles: [],
            animationFrame: null,
            resizeHandler: null,
            submitHandler: null,
            successHandler: null,

            init() {
                this.$nextTick(() => {
                    this.resizeCanvas();
                    this.resizeHandler = () => this.resizeCanvas();
                    window.addEventListener('resize', this.resizeHandler, { passive: true });

                    const submit = this.$el.querySelector('.srd-login-submit');
                    this.submitHandler = () => this.burst(64, submit);
                    submit?.addEventListener('click', this.submitHandler);

                    this.successHandler = () => this.burst(110, submit);
                    window.addEventListener('gamified-login-success', this.successHandler);

                    requestAnimationFrame(() => {
                        this.entered = true;
                    });
                });
            },

            resizeCanvas() {
                const canvas = this.$refs.confetti;

                if (!canvas) {
                    return;
                }

                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.floor(window.innerWidth * dpr);
                canvas.height = Math.floor(window.innerHeight * dpr);
                canvas.style.width = `${window.innerWidth}px`;
                canvas.style.height = `${window.innerHeight}px`;
                canvas.getContext('2d').setTransform(dpr, 0, 0, dpr, 0, 0);
            },

            burst(amount = 64, origin = null) {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                this.resizeCanvas();

                const rect = origin?.getBoundingClientRect();
                const startX = rect ? rect.left + rect.width / 2 : window.innerWidth / 2;
                const startY = rect ? rect.top + rect.height / 2 : window.innerHeight / 2;

                for (let index = 0; index < amount; index += 1) {
                    const angle = Math.random() * Math.PI * 2;
                    const speed = 4 + Math.random() * 9;

                    this.particles.push({
                        x: startX,
                        y: startY,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed - 5,
                        gravity: 0.18 + Math.random() * 0.12,
                        drag: 0.985,
                        size: 4 + Math.random() * 7,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        rotation: Math.random() * Math.PI,
                        spin: (Math.random() - 0.5) * 0.35,
                        life: 1,
                        decay: 0.009 + Math.random() * 0.012,
                    });
                }

                if (!this.animationFrame) {
                    this.drawConfetti();
                }
            },

            drawConfetti() {
                const canvas = this.$refs.confetti;
                const context = canvas?.getContext('2d');

                if (!context) {
                    return;
                }

                context.clearRect(0, 0, window.innerWidth, window.innerHeight);

                this.particles = this.particles.filter((particle) => {
                    particle.vx *= particle.drag;
                    particle.vy = particle.vy * particle.drag + particle.gravity;
                    particle.x += particle.vx;
                    particle.y += particle.vy;
                    particle.rotation += particle.spin;
                    particle.life -= particle.decay;

                    if (particle.life <= 0 || particle.y > window.innerHeight + 24) {
                        return false;
                    }

                    context.save();
                    context.globalAlpha = Math.max(particle.life, 0);
                    context.translate(particle.x, particle.y);
                    context.rotate(particle.rotation);
                    context.fillStyle = particle.color;
                    context.fillRect(-particle.size / 2, -particle.size / 4, particle.size, particle.size / 2);
                    context.restore();

                    return true;
                });

                if (this.particles.length) {
                    this.animationFrame = requestAnimationFrame(() => this.drawConfetti());
                } else {
                    this.animationFrame = null;
                    context.clearRect(0, 0, window.innerWidth, window.innerHeight);
                }
            },

            tilt(event) {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                const rect = event.currentTarget.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width - 0.5;
                const y = (event.clientY - rect.top) / rect.height - 0.5;
                event.currentTarget.style.setProperty('--card-rx', `${y * -2.5}deg`);
                event.currentTarget.style.setProperty('--card-ry', `${x * 2.5}deg`);
            },

            resetTilt(event) {
                event.currentTarget.style.setProperty('--card-rx', '0deg');
                event.currentTarget.style.setProperty('--card-ry', '0deg');
            },

            destroy() {
                const submit = this.$el.querySelector('.srd-login-submit');
                submit?.removeEventListener('click', this.submitHandler);
                window.removeEventListener('resize', this.resizeHandler);
                window.removeEventListener('gamified-login-success', this.successHandler);
                cancelAnimationFrame(this.animationFrame);
            },
        };
    }

    function register() {
        if (!window.Alpine || window.__srdGamifiedLoginRegistered) {
            return;
        }

        window.__srdGamifiedLoginRegistered = true;
        window.Alpine.data('gamifiedLogin', gamifiedLogin);
    }

    document.addEventListener('alpine:init', register, { once: true });
    document.addEventListener('livewire:init', register, { once: true });
    register();
})();
