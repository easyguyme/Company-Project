<div class="clearfix m-tel m-rel">
    <div class="m-tel-height">
        <?php if($style == '1') { ?>
            <a class="m-a-style clearfix" href=<?php echo '"tel:' . $tel . '"'; ?> >
                <div class="m-tel-style-one-box m-bgcolor clearfix">
                    <span class="m-tel-style-one-word"><?php echo (isset($tag) && (!empty($tag) || $tag == '0')) ? htmlspecialchars($tag, ENT_QUOTES) : '联系我们'; ?></span>
                </div>
            </a>
        <?php } else { ?>
            <a class="m-a-style clearfix" href=<?php echo '"tel:' . $tel . '"'; ?> >
                <div class="m-border-color m-tel-style-two-box clearfix">
                    <!-- <span class="m-tel-style-two-word m-bgcolor"></span> -->
                    <img src="/images/microsite/phoneicon2.png" class="m-tel-style-two-word m-bgcolor">
                    <span class="m-tel-style-two-word-left">+86<?php echo htmlspecialchars(strtr($tel, array('-' => ' - ')), ENT_QUOTES); ?></span>
                </div>
            </a>
        <?php } ?>
    </div>
</div>
