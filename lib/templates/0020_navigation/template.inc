<ul class="navigation">
    <?php
    foreach (rex_category::getRootCategories(true) as $item) {
        if ($item->isOnline()) {
            $active = "";
            if (rex_article::getCurrentId() == $item->getId()) {
                $active = "active";
            }
            echo '<li class="nav-item"><a href="' . $item->getUrl() . '" class="nav-link '.$active.'">' . htmlspecialchars($item->getValue('name')) . '</a></li>';
        }
    }
    ?>
</ul>