<!-- Add issue page -->
<input type="hidden" id="accessToken" value="<?= $token?>">
<div class="feedback-add-wrapper">
    <div class="input-form-group">
        <label class="input-label">姓名：</label>
        <input type="text" class="input-form name" id="name" value="<?= $nickname?>">
        <span class="center-name-form-tip"></span>
    </div>
    <div class="input-form-group">
        <label class="input-label">邮箱：</label>
        <input type="email" class="input-form email" id="email">
        <span class="center-email-form-tip"></span>
    </div>
    <div class="input-form-group">
        <label class="input-label">手机号：</label>
        <input type="tel" class="input-form phone" id="phone">
        <span class="center-phone-form-tip"></span>
    </div>

    <div class="input-form-group">
        <p class="feedback-tip">请告诉我们您的意见和反馈，我们会尽快为您处理。</p>
    </div>

    <div class="input-form-group">
        <label class="input-label">标题：</label>
        <input type="text" class="input-form title" id="title">
        <span class="center-title-form-tip"></span>
    </div>
    <div class="input-form-group">
        <div class="textarea-wrapper description">
            <div class="textarea-form-wrapper">
                <textarea class="textarea-form" id="description">问题描述：</textarea>
            </div>
            <div class="attachments-wrapper">
                <div class="input-wrapper">
                    <img class="img-upload" src="/images/mobile/feedback/phone_addphoto_normal.png">
                    <input class="input-file" type="file" id="attachment" multiple>
                </div>
            </div>
        </div>
        <span class="center-description-form-tip center-attachment-form-tip"></span>
    </div>

    <div class="input-form-group">
        <button class="button-component md" id="submitBtn">提交</button>
    </div>
</div>
<!-- Success page -->
<div class="feedback-success-wrapper">
    <div>
        <div>
            <img class="img-success" src="/images/mobile/feedback/submit_issue_success.jpg">
        </div>
        <div class="success-tip">反馈提交成功</div>
        <div class="thanks-tip">感谢您对我们工作的支持</div>
        <div>我们会尽快为您处理</div>
    </div>
</div>


