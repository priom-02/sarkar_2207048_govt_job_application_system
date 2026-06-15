// main.js — Government Job Application System

// Auto-hide alerts after 4 seconds
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (a) {
            a.style.transition = 'opacity 0.5s';
            a.style.opacity = '0';
            setTimeout(function () { a.remove(); }, 500);
        });
    }, 4000);
});
