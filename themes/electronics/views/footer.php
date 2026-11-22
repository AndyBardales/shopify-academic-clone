<?= \ShopCMS\Core\ThemeCustomizer::renderRichFooter($themeSlug ?? 'electronics', $storeName ?? 'Electronics', $baseUrl ?? '') ?>

<?php \ShopCMS\Core\PluginManager::doAction('theme_footer'); ?>
</body>
</html>
