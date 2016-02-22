<div class="message<?php if (Yii::$app->request->getQueryParam('from') === 'wechat') { echo ' from-wechat'; } ?>">
    <div class="spaceship-bg"></div>

    <div class="message-reminder">
        <span class="reminder-line reminder-line-left"></span>
        <span class="reminder-word">我们会在3个工作日内给您回复。若有紧急需要，请联系我们</span>
        <span class="reminder-line reminder-line-right"></span>
    </div>

    <div class="message-contact">
        <div class="message-contact-email">
            <span class="contact-icon contact-icon-email"></span><span>quncrm@augmentum.com</span>
        </div>
        <div class="message-contact-phone">
            <span class="contact-icon contact-icon-phone"></span>
            <span>
                <span class="main-phone-number">021-51314277</span>
                <span class="middle-word">转</span>400
            </span>
        </div>
    </div>

    <div class="register-in-wechat" <?php if (Yii::$app->request->getQueryParam('from') !== 'wechat') { echo 'hidden'; } ?> >
        <img class="pure-img" src="/build/landing/images/registeredsuccessfully_img_qrcode.png" />
    </div>
</div>
