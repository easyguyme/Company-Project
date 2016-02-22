<?php
    $navLen = count($navInfo);
    if(3 < $navLen && $navLen < 6) {
        for ($i = 0; $i < 6 - $navLen; $i++) {
            $navInfo[] = ['name' => '', 'linkUrl' => '', 'iconUrl' => ''];
        }
    }
    $info = $slideInfo;
    $slideNames = '';
    foreach ($info as $slide) {
        if (isset($slide['name']) && (!empty($slide['name']) || $slide['name'] == '0')) {
            $slideNames = $slideNames . $slide['name'] . ',';
        } else {
            $slideNames = $slideNames . ',';
        }
    };
    $slideNames = substr($slideNames, 0, -1);
    $lastSlideName = substr($slideNames, 0, strpos($slideNames, ','));

?>
<div class="m-cover1">
    <div class="m-slide">
        <div id='cover1' class='m-swipe'>
          <div class="m-swipe-wrap" data-auto="<?= $setting;?>" data-name-list="<?= $slideNames;?>">
            <?php $len = count($info); ?>
            <?php foreach ($info as $slide) { ?>
                <div class="m-img-bg m-load-pic"
                    <?php if(isset($slide['pic']) && !empty($slide['pic'])) { ?>
                        <?= 'data-original="' . (isset($slide['pic']) && !empty($slide['pic']) ? $slide['pic'] : '') .'">' ?>
                    <?php } else { ?>
                        <?= 'style="background-image:url(' . $slide['defaultPic'] . ');">'; ?>
                    <?php } ?>
                    <?php if(!empty($slide['linkUrl'])) { ?>
                        <a class="m-slide-placeholder" href="<?= strpos($slide['linkUrl'], 'http') === false ? 'http://' . $slide['linkUrl'] : $slide['linkUrl']; ?>"
                        target="<?= (isset($slide['linkUrl']) && !empty($slide['linkUrl']) && strpos($slide['linkUrl'], DOMAIN) === false ? '_blank' : '_self'); ?>">
                        </a>
                    <?php } ?>

                </div>

            <?php } ?>
          </div>
            <div class="m-title-wrapper m-pic-title-bgcolor clearfix">
                <div class="m-slide-title m-text-overflow">
                    <?php if(!empty($lastSlideName) || $lastSlideName == '0'){ echo htmlspecialchars($lastSlideName, ENT_QUOTES);} else {echo '';} ?>
                </div>
                <ul class="m-dot-ul clearfix">
                    <?php if ($len > 1) { ?>
                    <?php for ($i = 0; $i < $len; $i++) { ?>
                        <li class="m-swipe-dot"></li>
                    <?php }} ?>
                </ul>
            </div>

        </div>
    </div>
    <div class="m-cover1-nav clearfix">
        <?php
            foreach ($navInfo as $index => $nav) {
                $index ++;
                if (!empty($nav['linkUrl'])) {
                    if (strpos($nav['linkUrl'], 'http') === false) {
                        $linkUrl = 'http://' . $nav['linkUrl'];
                    } else {
                        $linkUrl = $nav['linkUrl'];
                    }
                } else {
                    $linkUrl = '';
                }
        ?>

        <div class="m-cover1-nav-item<?php echo $index;?> m-bgcolor">
            <div class="m-cover1-nav-info">
                <a class="m-cover1-nav-placeholder" <?php if(!empty($linkUrl)) echo 'href="' . $linkUrl .'"'; ?>
                target="<?= strpos($nav['linkUrl'], DOMAIN) === false ? '_blank' : '_self'; ?>">
                <div class="m-cover1-nav-icon" style="background-image:url('<?= $nav['iconUrl']; ?>')"></div>
              <div class="m-cover1-nav-text"><?php echo $nav['name'];?></div></a>
            </div>
        </div>
        <?php
        }
        ?>
    </div>
</div>