<div id="wbattention" class="wb-error" data-app-id="<?php echo (!empty($channel) && !empty($channel['appId'])) ? $channel['appId'] : ''; ?>">
    <img class="wb-attention-header" src="/images/mobile/wbattention/bg_weibo.png" />
    <div class="wb-attention-target">
        <div class="attention-images">
            <img id="avatar" class="attention-avatar" src="<?php echo (!empty($channel) && !empty($channel['headImageUrl'])) ? $channel['headImageUrl'] : '/images/management/image_hover_default_avatar.png'; ?>">
            <div id="verified" class="attention-verified <?php echo (!empty($channel) && !empty($channel['weiboAccountType']) && $channel['weiboAccountType'] === 'AUTH_ACCOUNT') ? 'show' : 'hide'; ?>"></div>
        </div>
        <div id="name" class="attention-name"><?php echo (!empty($channel) && !empty($channel['name'])) ? $channel['name'] : ''; ?></div>
    </div>
    <div class="wb-attention-illustration1">您需要关注我们的微博账号即可继续访问</div>
    <div class="wb-attention-illustration2">点击头像关注我们</div>
</div>
