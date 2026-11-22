<?= \ShopCMS\Core\ThemeCustomizer::renderRichFooter($themeSlug ?? 'fashion', $storeName ?? 'Fashion', $baseUrl ?? '') ?>

<?php \ShopCMS\Core\PluginManager::doAction('theme_footer'); ?>
</body>
</html>
