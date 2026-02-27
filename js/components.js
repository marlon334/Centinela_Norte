/**
 * Centinela del Norte - Core Engine
 * Handles component loading and UI interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    initLayout();
    initScrollEffects();
});

async function initLayout() {
    // Load Header & Footer
    await Promise.all([
        loadComponent('#header-container', 'components/header.html'),
        loadComponent('#footer-container', 'components/footer.html')
    ]);

    highlightActiveLink();
    initMobileMenu();

    // Trigger animations immediately
    revealImmediately();
}

async function loadComponent(selector, url) {
    const el = document.querySelector(selector);
    if (!el) return;

    try {
        const response = await fetch(url);
        const html = await response.text();
        el.innerHTML = html;
    } catch (error) {
        console.error(`Error loading ${url}:`, error);
    }
}

function highlightActiveLink() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.html';
    const links = document.querySelectorAll('nav ul li a');
    links.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
}

function initScrollEffects() {
    const header = document.querySelector('header');

    window.addEventListener('scroll', () => {
        // Sticky Header Effect
        if (window.scrollY > 50) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
    });
}

function revealImmediately() {
    const reveals = document.querySelectorAll('.animate-on-scroll');
    reveals.forEach(el => {
        el.classList.add('visible');
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });
}

function initMobileMenu() {
    const toggle = document.querySelector('.mobile-toggle');
    const nav = document.querySelector('nav');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            nav.classList.toggle('mobile-active');
            toggle.classList.toggle('active');
        });
    }
}

