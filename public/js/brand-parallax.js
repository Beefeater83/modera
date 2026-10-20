(() => {
    const scene = document.getElementById('brand-scene');

    if (!scene) {
        return;
    }

    const logos = [...scene.querySelectorAll('.brand-item')];
    const core = scene.querySelector('.brand-core');
    const coreShadow = scene.querySelector('.core-shadow');

    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    );

    let frame = null;
    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;

    function updatePositions() {
        frame = null;

        currentX += (targetX - currentX) * 0.12;
        currentY += (targetY - currentY) * 0.12;

        for (const logo of logos) {
            const depth = Number(logo.dataset.depth) || 0.1;
            const style = getComputedStyle(logo);

            const x = parseFloat(style.getPropertyValue('--x')) || 0;
            const y = parseFloat(style.getPropertyValue('--y')) || 0;

            logo.style.transform =
                `translate3d(${x + currentX * depth}px, ` +
                `${y + currentY * depth}px, 0)`;
        }

        const coreTransform =
            `translate3d(${currentX * 0.12}px, ` +
            `${currentY * 0.12}px, 0)`;

        if (core) {
            core.style.transform = coreTransform;
        }

        if (coreShadow) {
            coreShadow.style.transform = coreTransform;
        }

        if (
            Math.abs(targetX - currentX) > 0.1 ||
            Math.abs(targetY - currentY) > 0.1
        ) {
            frame = requestAnimationFrame(updatePositions);
        }
    }

    // Движение логотипов при перемещении мыши по всей странице.
    document.addEventListener('pointermove', (event) => {
        if (reduceMotion.matches || event.pointerType === 'touch') {
            return;
        }

        const x = event.clientX / window.innerWidth - 0.5;
        const y = event.clientY / window.innerHeight - 0.5;

        // Логотипы движутся в направлении, противоположном курсору.
        targetX = -x * 70;
        targetY = -y * 70;

        if (frame === null) {
            frame = requestAnimationFrame(updatePositions);
        }
    });

    document.addEventListener('pointerleave', (event) => {
        if (event.relatedTarget !== null) {
            return;
        }

        targetX = 0;
        targetY = 0;

        if (!reduceMotion.matches && frame === null) {
            frame = requestAnimationFrame(updatePositions);
        }
    });

    updatePositions();
})();
