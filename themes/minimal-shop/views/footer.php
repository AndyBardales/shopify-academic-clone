<?= \ShopCMS\Core\ThemeCustomizer::renderRichFooter($themeSlug ?? 'minimal-shop', $storeName ?? 'ShopCMS Store', $baseUrl ?? '') ?>

<?php \ShopCMS\Core\PluginManager::doAction('theme_footer'); ?>
</body>
</html>
