function showToast(msg, ok=true) {
    const t = document.getElementById('toast-prod');
    t.textContent = msg;
    t.style.borderLeftColor = ok ? '#C9A84C' : '#C1622F';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

function toggleFavori(btn, productId) {
    fetch('/ecommerce/cart/wishlist_toggle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                btn.classList.add('active');
                btn.querySelector('i').className = 'bi bi-heart-fill';
                showToast('Ajoute aux favoris !');
            } else {
                btn.classList.remove('active');
                btn.querySelector('i').className = 'bi bi-heart';
                showToast('Retire des favoris');
            }
        } else {
            showToast(data.message, false);
            if (data.message.includes('Connectez')) {
                setTimeout(() => window.location = '/ecommerce/user/login.php', 1500);
            }
        }
    });
}
function toggleInspiRation(btn, modeleId) {
    fetch('/ecommerce/cart/wishlist_inspiration_toggle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `modele_id=${modeleId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                btn.querySelector('i').className = 'bi bi-heart-fill';
                btn.style.color = '#c62828';
                showToast('Inspiration sauvegardee !');
            } else {
                btn.querySelector('i').className = 'bi bi-heart';
                btn.style.color = '#9a7c5c';
                showToast('Retire des favoris');
            }
        } else {
            showToast(data.message, false);
            if (data.message.includes('Connectez')) {
                setTimeout(() => window.location = '/ecommerce/user/login.php', 1500);
            }
        }
    });
}
// Scroll horizontal au drag souris
document.querySelectorAll('.scroll-horizontal').forEach(el => {
    let isDown = false, startX, scrollLeft;
    el.addEventListener('mousedown', e => {
        isDown = true;
        startX = e.pageX - el.offsetLeft;
        scrollLeft = el.scrollLeft;
    });
    el.addEventListener('mouseleave', () => isDown = false);
    el.addEventListener('mouseup', () => isDown = false);
    el.addEventListener('mousemove', e => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - el.offsetLeft;
        el.scrollLeft = scrollLeft - (x - startX) * 1.5;
    });
});