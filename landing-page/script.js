document.addEventListener('DOMContentLoaded', () => {
    // Select elements to animate
    const hero = document.getElementById('hero-section');
    const cards = document.querySelectorAll('.portal-card');
    const features = document.getElementById('features-section');
    const metricsSection = document.getElementById('metrics-section');
    const archSection = document.getElementById('architecture-section');
    const detailedFeatures = document.getElementById('detailed-features');

    // Trigger animations sequentially after page load for smooth entry
    setTimeout(() => {
        if(hero) hero.classList.add('visible');
    }, 100);

    setTimeout(() => {
        cards.forEach(card => card.classList.add('visible'));
    }, 300);

    setTimeout(() => {
        if(features) features.classList.add('visible');
    }, 500);

    setTimeout(() => {
        if(metricsSection) metricsSection.classList.add('visible');
    }, 700);

    setTimeout(() => {
        if(archSection) archSection.classList.add('visible');
    }, 900);

    setTimeout(() => {
        if(detailedFeatures) detailedFeatures.classList.add('visible');
    }, 1100);

    // Track mouse coordinates for the background glow overlay
    window.addEventListener('mousemove', (e) => {
        document.documentElement.style.setProperty('--mouse-x', `${e.clientX}px`);
        document.documentElement.style.setProperty('--mouse-y', `${e.clientY}px`);
    });
});
