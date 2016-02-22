<div class="m-coupon">
    <?php if($style == '1') { ?>
        <a class="m-coupon-wrapper" href="<?php if (!empty($url)) {echo $url;}?>">
            <div class="m-coupon-image-wrapper">
                <img class="m-coupon-image" src="<?php if (!empty($image)) {echo $image;}?>">
            </div>
            <div class="m-coupon-title-wrapper">
                <span class="m-coupon-title"><?php if (!empty($title)) {echo $title;}?></span>
            </div>
        </a>
    <?php } else { ?>
        <a class="m-coupon-btn clearfix m-bgcolor" href="<?php if (!empty($url)) {echo $url;}?>" >
            立即领取
        </a>
    <?php } ?>
    <div class="m-coupon-blank"></div>
</div>
