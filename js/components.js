document.addEventListener('DOMContentLoaded', () => {
    // Load Header
    const headerEl = document.querySelector('#header-container');
    if (headerEl) {
        fetch('components/header.html')
            .then(response => response.text())
            .then(data => {
                headerEl.innerHTML = data;
            });
    }

    // Load Footer
    const footerEl = document.querySelector('#footer-container');
    if (footerEl) {
        fetch('components/footer.html')
            .then(response => response.text())
            .then(data => {
                footerEl.innerHTML = data;
            });
    }
});
