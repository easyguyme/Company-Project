<div class="wm-cover1-image wm-rel wm-load-pic" data-original="<?php echo isset($imageUrl) && !empty($imageUrl) ? $imageUrl.QINIU_THUMBNAIL_MIDDLE : ''; ?>">
    <a class="wm-cover-bglink" href="<?php echo isset($linkUrl) && !empty($linkUrl)  ? (strpos($linkUrl, 'http') === false ? ('http://'. $linkUrl) : $linkUrl) : ''; ?>" <?php echo !empty($linkUrl) && strpos($linkUrl, DOMAIN) === false ? 'target="_blank"' : ''; ?>></a>
    <div class="wm-cover2-nav wm-abs">
        <?php
        $navLen = count($navInfo);
        for($i = 0; $i < $navLen; $i++)
        {
            $navItemLink = isset($navInfo[$i]['linkUrl']) && !empty($navInfo[$i]['linkUrl']) ? (strpos($navInfo[$i]['linkUrl'], 'http') === false ? ('http://'. $navInfo[$i]['linkUrl']) : $navInfo[$i]['linkUrl']) : '';
            $navItemTarget = isset($navInfo[$i]['linkUrl']) && !empty($navInfo[$i]['linkUrl']) && strpos($navInfo[$i]['linkUrl'], DOMAIN) === false ? '_blank' : '_self';
            if ($i % 2 == 0)
            {
                echo '<div class="wm-cnav-line clearfix">
                            <div class="wm-cnav-item wm-fl wm-bgcolor">
                                <a href="' . $navItemLink . '" target="' . $navItemTarget . '" class="wm-a wm-cnav-link">
                                    <div class="wm-cnav-title">' . $navInfo[$i]['name'] . '</div>
                                </a>
                            </div>';
                continue;
            }
            if ($i % 2 == 1)
            {
                echo '<div class="wm-cnav-item wm-fl wm-bgcolor">
                                <a href="' . $navItemLink . '" target="' . $navItemTarget . '" class="wm-a wm-cnav-link">
                                    <div class="wm-cnav-title">' . $navInfo[$i]['name'] . '</div>
                                </a>
                            </div>
                        </div>';
                continue;
            }
        }
    ?>
    </div>
</div>