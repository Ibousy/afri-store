</main>

<!-- NEWSLETTER BANNER -->
<div class="newsletter-bar">
    <div class="container newsletter-inner">
        <div class="newsletter-text">
            <span class="newsletter-icon">📧</span>
            <div>
                <strong>Inscrivez-vous à notre newsletter</strong>
                <span>Recevez les meilleures offres et nouveautés en avant-première !</span>
            </div>
        </div>
        <form class="newsletter-form" onsubmit="event.preventDefault(); this.innerHTML='<span style=\'color:#27AE60;font-weight:800\'>✅ Merci ! Vous êtes inscrit(e).</span>'">
            <input type="email" placeholder="Votre adresse email..." required>
            <button type="submit">S'inscrire</button>
        </form>
    </div>
</div>

<!-- AVANTAGES FOOTER -->
<div class="footer-benefits">
    <div class="container footer-benefits-grid">
        <div class="benefit-item">
            <span class="benefit-icon">🚚</span>
            <div>
                <strong>Livraison gratuite</strong>
                <span>Dès 19 000 FCFA d'achat</span>
            </div>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">↩️</span>
            <div>
                <strong>Retours gratuits</strong>
                <span>Sous 90 jours</span>
            </div>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">🔒</span>
            <div>
                <strong>Paiement sécurisé</strong>
                <span>100% protégé</span>
            </div>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">🎁</span>
            <div>
                <strong>Offres exclusives</strong>
                <span>Chaque jour</span>
            </div>
        </div>
        <div class="benefit-item">
            <span class="benefit-icon">⭐</span>
            <div>
                <strong>Qualité garantie</strong>
                <span>Produits vérifiés</span>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER PRINCIPAL -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Colonne marque -->
            <div class="footer-col footer-brand">
                <a href="<?= SITE_URL ?>" class="footer-logo">
                    <span class="footer-logo-icon">🛍️</span>
                    <span><?= SITE_NAME ?></span>
                </a>
                <p>Des millions de produits à prix imbattables. Livraison rapide partout en France et dans le monde.</p>

                <!-- App badges -->
                <div class="app-badges">
                    <a href="#" class="app-badge">
                        <span class="app-badge-icon">🍎</span>
                        <div>
                            <small>Télécharger sur</small>
                            <strong>App Store</strong>
                        </div>
                    </a>
                    <a href="#" class="app-badge">
                        <span class="app-badge-icon">▶️</span>
                        <div>
                            <small>Disponible sur</small>
                            <strong>Google Play</strong>
                        </div>
                    </a>
                </div>

                <!-- Réseaux sociaux -->
                <div class="social-links">
                    <a href="#" class="social-btn" title="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a>
                    <a href="#" class="social-btn" title="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="#" class="social-btn" title="Twitter / X">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="social-btn" title="YouTube">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/></svg>
                    </a>
                    <a href="#" class="social-btn" title="TikTok">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.73a4.85 4.85 0 0 1-1.01-.04z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Aide & Support -->
            <div class="footer-col">
                <h4 class="footer-title">Aide & Support</h4>
                <ul class="footer-links">
                    <li><a href="#">💬 Centre d'aide</a></li>
                    <li><a href="#">📦 Suivi de commande</a></li>
                    <li><a href="#">↩️ Retours & Remboursements</a></li>
                    <li><a href="#">🚩 Signaler un problème</a></li>
                    <li><a href="#">🤝 Service client 24/7</a></li>
                </ul>
                <div class="footer-contact">
                    <div class="contact-item">
                        <span>📞</span>
                        <div>
                            <small>Service client</small>
                            <strong>+33 1 23 45 67 89</strong>
                        </div>
                    </div>
                    <div class="contact-item">
                        <span>📧</span>
                        <div>
                            <small>Email</small>
                            <strong>support@<?= strtolower(SITE_NAME) ?>.fr</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- À propos -->
            <div class="footer-col">
                <h4 class="footer-title">À propos</h4>
                <ul class="footer-links">
                    <li><a href="#">🏢 Qui sommes-nous ?</a></li>
                    <li><a href="#">🏪 Vendre sur <?= SITE_NAME ?></a></li>
                    <li><a href="#">💼 Programme d'affiliation</a></li>
                    <li><a href="#">👔 Carrières</a></li>
                    <li><a href="#">📰 Presse & Médias</a></li>
                    <li><a href="#">🌍 Développement durable</a></li>
                </ul>
            </div>

            <!-- Légal -->
            <div class="footer-col">
                <h4 class="footer-title">Légal & Confidentialité</h4>
                <ul class="footer-links">
                    <li><a href="#">📋 Conditions d'utilisation</a></li>
                    <li><a href="#">🔒 Politique de confidentialité</a></li>
                    <li><a href="#">🍪 Politique des cookies</a></li>
                    <li><a href="#">⚖️ Mentions légales</a></li>
                    <li><a href="#">♿ Accessibilité</a></li>
                </ul>

                <!-- Langues -->
                <div style="margin-top:20px">
                    <h5 style="font-size:13px;font-weight:700;margin-bottom:10px;color:rgba(255,255,255,.6)">Langue</h5>
                    <select class="footer-select">
                        <option>🇫🇷 Français</option>
                        <option>🇬🇧 English</option>
                        <option>🇩🇪 Deutsch</option>
                        <option>🇪🇸 Español</option>
                        <option>🇮🇹 Italiano</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- BARRE DE PAIEMENT -->
        <div class="footer-payments">
            <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,.5)">Moyens de paiement acceptés :</span>
            <div class="payment-badges">
                <span class="pay-badge" title="Visa">VISA</span>
                <span class="pay-badge" title="Mastercard">MC</span>
                <span class="pay-badge" title="PayPal">PayPal</span>
                <span class="pay-badge" title="Apple Pay">🍎 Pay</span>
                <span class="pay-badge" title="Google Pay">G Pay</span>
                <span class="pay-badge" title="Virement">Virement</span>
            </div>
        </div>

        <!-- COPYRIGHT -->
        <div class="footer-bottom">
            <p>
                &copy; <?= date('Y') ?> <strong><?= SITE_NAME ?></strong>. Tous droits réservés.
                — Conçu avec ❤️ en France
            </p>
            <div class="footer-bottom-links">
                <a href="#">Plan du site</a>
                <span>·</span>
                <a href="#">CGU</a>
                <span>·</span>
                <a href="#">Confidentialité</a>
                <span>·</span>
                <a href="#">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<!-- BOUTON RETOUR EN HAUT -->
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Retour en haut">↑</button>

<!-- CART NOTIFICATION -->
<div id="cartNotif" class="cart-notif"></div>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
