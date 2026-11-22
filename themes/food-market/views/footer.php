<section class="site-footer-features" style="background: #F3F4F6; padding: 28px 0; border-top: 1px solid #E5E7EB;">
    <div class="container">
        <div class="footer-features" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
            <div class="feature-box">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <div>
                    <h4>100% Calidad Garantizada</h4>
                    <p>Selección rigurosa de productores locales y estándares de frescura.</p>
                </div>
            </div>

            <div class="feature-box">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <div>
                    <h4>Cadena de Frío Segura</h4>
                    <p>Entrega rápida con control térmico para preservar nutrientes y sabor.</p>
                </div>
            </div>

            <div class="feature-box">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <div>
                    <h4>Comercio Justo & Local</h4>
                    <p>Apoyo directo a granjas y productores artesanales de la región.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
// Evita etiqueta footer duplicada; reemplaza <footer class="site-footer"> superior por el rich footer editable
?>
<?= \ShopCMS\Core\ThemeCustomizer::renderRichFooter($themeSlug ?? 'food-market', $storeName ?? 'Food & Fresh Market', $baseUrl ?? '') ?>

<?php \ShopCMS\Core\PluginManager::doAction('theme_footer'); ?>
</body>
</html>
