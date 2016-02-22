<div class="m-sms m-rel">
    <div class="m-sms-height">
        <a class="m-a-style clearfix" href="sms:<?php echo (isset($tel) && !empty($tel) ? $tel : '13900000000'); ?>">
            <div class="m-sms-style-one-box m-bgcolor clearfix">
                <span class="m-sms-style-one-word"><?php echo (isset($smsText) && (!empty($smsText) || $smsText == '0') ? htmlspecialchars($smsText, ENT_QUOTES) : '短信联系'); ?></span>
            </div>
        </a>
    </div>
</div>
