</main>

<script>
function addToCart(productId) {
    fetch('/ecommerce/cart/add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast('✅ Produit ajouté au panier !', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('❌ ' + data.message, 'danger');
        }
    })
    .catch(() => showToast('❌ Erreur lors de l\'ajout au panier', 'danger'));
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `hairroots-toast toast-${type}`;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000);
}
</script>

<!-- NEWSLETTER BANNER -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-box">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <h4 class="mb-1"> Rejoignez la communauté HairRoots</h4>
                    <p class="mb-0 opacity-75">Recevez nos conseils capillaires, tendances et offres exclusives</p>
                </div>
                <div class="col-lg-6">
                    <form class="d-flex gap-2">
                        <input type="email" class="form-control newsletter-input" placeholder="Votre adresse email...">
                        <button type="submit" class="btn btn-gold px-4 text-nowrap">S'abonner </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="hairroots-footer">
    <div class="container">
        <div class="row g-4 py-5">
            <!-- Brand -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand mb-3"> HairRoots</div>
                <p class="footer-text">Votre destination capillaire pour tous les types de cheveux. Mèches, soins et coiffeuses expertes pour femmes, hommes et enfants.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="footer-social"><i class="bi bi-tiktok"></i></a>
                    <a href="#" class="footer-social"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <!-- Boutique -->
            <div class="col-lg-2 col-md-6">
                <h6 class="footer-title">Boutique</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="/ecommerce/products/index.php?category=1"> Mèches Bouclées</a></li>
                    <li><a href="/ecommerce/products/index.php?category=2"> Mèches Crépues</a></li>
                    <li><a href="/ecommerce/products/index.php?category=3"> Mèches Lisses</a></li>
                    <li><a href="/ecommerce/products/index.php?category=4"> Mèches Ondulées</a></li>
                    <li><a href="/ecommerce/products/index.php?category=5"> Soins Cheveux</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-lg-2 col-md-6">
                <h6 class="footer-title">Services</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="/ecommerce/coiffeuses.php"> Nos Coiffeuses</a></li>
                    <li><a href="/ecommerce/coiffures.php"> Inspirations</a></li>
                    <li><a href="/ecommerce/rendez-vous.php"> Prendre RDV</a></li>
                    <li><a href="#"> Suivi commande</a></li>
                    <li><a href="#"> Conseils capillaires</a></li>
                </ul>
            </div>

            <!-- Infos -->
            <div class="col-lg-4 col-md-6">
                <h6 class="footer-title">Nous contacter</h6>
                <ul class="list-unstyled footer-links">
                    <li> Paris, France</li>
                    <li> +33 1 23 45 67 89</li>
                    <li> contact@hairroots.fr</li>
                    <li class="mt-2"> Lun-Sam : 9h - 19h</li>
                </ul>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <span class="payment-badge"> CB</span>
                    <span class="payment-badge"> PayPal</span>
                    <span class="payment-badge"> Apple Pay</span>
                </div>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="row py-3 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0 footer-text small">© <?php echo date('Y'); ?> HairRoots  — Tous droits réservés</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="#" class="footer-link-sm me-3">Conditions générales</a>
                <a href="#" class="footer-link-sm me-3">Confidentialité</a>
                <a href="#" class="footer-link-sm">Contact</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/ecommerce/assets/js/script.js"></script>
</body>
</html>