<div class="m-article" data-shownum="<?php if(!empty($showNum)) echo $showNum; ?>" data-channel="<?php if(!empty($channelId)) echo $channelId; ?>" data-style="<?= $style; ?>" data-fields="<?php if(count($fields)>0) echo implode(',', $fields); ?>">
    <div class="m-article-list">
        <div class="m-article-wrapper clearfix">
            <div class="m-pic-box m-pull-left" style="background-image:url(<?php if(!empty($image)) echo $image; ?>)"></div>
            <div class="m-content-detail m-pull-left">
                <div class="m-article-title m-text-overflow"><?php if(!empty($title)) echo $title; ?></div>
                <div class="m-content"><?php if(!empty($content)) echo $content; ?></div>
            </div>
            <div class="m-content-view"></div>
        </div>
    </div>
    <div class="m-load-more">加载更多内容</div>
    <div class="m-loading m-spin"></div>
</div>
