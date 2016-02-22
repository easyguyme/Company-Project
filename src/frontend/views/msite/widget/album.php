<div class='m-album'>
    <!-- display title of atlas -->
    <div class='m-album-title'>
        <span class='title-tip'>图集</span>
        <span class='title-content'><?php echo isset($title) && trim($title) !== '' ? htmlspecialchars($title) : $defaultTitle; ?></span>
    </div>
    <!-- display preview of atlas -->
    <?php $column = intval($column);?>
    <?php $imageCount = count($album);?>
    <?php $groupCount = ceil($imageCount / $column);?>
    <div class='m-album-items m-album-swipe' data-column="<?php echo $column;?>">
        <div class="m-album-swipe-wrap">
        <?php $left = $imageCount % $column;?>
        <?php foreach($album as $index => $picture) {
            $url = empty($picture['url']) ? $picture['defaultUrl'] : $picture['url'];
            if (!$index) { ?>
            <div class="m-album-group">
            <?php } ?>
                <a href='<?php echo $url;?>' class='m-album-item <?php echo empty($picture['url']) ? 'm-album-bg' : 'm-album-bgcover'; ?>' data-original="<?php echo empty($picture['url']) ? '' : $url; ?>" style="border-radius: 0.125rem; <?php echo 'background-image:url(\''.$url.'\')';?>">
                    <img style='display:none;' id='<?php echo ($index+1)."/".count($album); ?>' title='<?php echo $title; ?>' alt='<?php echo $picture['description']; ?>'/>
                </a>
            <?php if ($index < $imageCount - 1 && (0 == ($index + 1) % $column)) { ?>
            </div>
            <div class="m-album-group">
            <?php } ?>
            <?php if ($index == $imageCount - 1) { ?>
            <?php if ($left) { ?>
                <?php for ($i = 0; $i < $column - $left; $i++) { ?>
                    <span class='m-album-item m-album-placeholder'></span>
                <?php } ?>
            <?php } ?>
            </div>
            <?php } ?>
        <?php } ?>
        </div>
    </div>

    <div class="m-album-center" <?php if (1 == $groupCount) echo 'style="height:0.94rem"'; ?>>
        <?php if ($groupCount > 1) { ?>
        <ul class="m-album-dots">
            <?php for ($i = 0; $i < $groupCount; $i++) { ?>
                <li class="m-album-dot <?php if (0 == $i) echo 'm-album-bgcolor'; ?>"></li>
            <?php } ?>
        </ul>
        <?php } ?>
    </div>
</div>
