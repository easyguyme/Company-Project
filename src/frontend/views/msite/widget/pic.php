<?php echo !empty($linkUrl) ? '<a href="' : ''?><?php echo !empty($linkUrl) && strpos($linkUrl, 'http') === false ? ('http://'. $linkUrl) : $linkUrl; ?><?php echo !empty($linkUrl) ? '"' : ''?> <?php echo !empty($linkUrl) && strpos($linkUrl, DOMAIN) === false ? 'target="_blank"' : ''; ?><?php echo !empty($linkUrl) ? '>' : ''?>
    <div class="m-pic">
        <div id="pic" class="m-pic-swipe">
            <div class="m-swipe-wrap">
                <div id="m-wrap-pic-box" class="m-pic-bg m-load-pic" data-original='<?php echo (isset($imageUrl) && !empty($imageUrl) ? $imageUrl : ''); ?>'></div>
            </div>
        </div>
        <?php echo (isset($imageUrl) && !empty($imageUrl) ? '' : '<div id="m-default-pic-box" class="m-default-wrap"></div>'); ?>
        <div class="m-title-wrapper m-pic-title-bgcolor clearfix <?php echo (isset($name) && (!empty($name) || $name == '0')? '' : 'm-dn'); ?>">
            <div class="m-pic-title" style="width: 100%; box-sizing: border-box;">
                <?php echo (isset($name) && (!empty($name) || $name == '0')? htmlspecialchars($name, ENT_QUOTES) : ''); ?>
            </div>
        </div>
    </div>
<?php echo !empty($linkUrl) ? '</a>' : ''?>
