<!--Open membership card page-->
<div class="member-active-wrapper">
    <div class="input-form-group">
        <label class="input-label">手机号：</label>
        <input type="tel" class="input-form" id="phone">
        <span class="center-phone-form-tip"></span>
    </div>
    <div class="input-form-group verification-form-group">
        <input type="text" class="input-form" placeholder="请输入下图中的字符，不区分大小写" maxlength="4" id="verification">
    </div>
    <div class="input-form-group pic-form-group">
        <img id="iconVerificationCode" class="icon-verification-code" src="">
        <span id="btnChangePic" class="mb-link-component link-change-validate">看不清？换一张</span>
        <span class="center-verification-form-tip"></span>
    </div>
    <div class="input-form-group">
        <div class="validate-form">
            <label class="input-label">验证码：</label>
            <input type="text" class="input-form" id="captcha">
        </div>
        <button class="validation-btn" id="btnSendCode">获取验证码</button>
        <span class="center-captcha-form-tip"></span>
    </div>
    <div class="input-form-group">
        <button class="mb-buttom-component md" id="activateBtn">提交</button>
    </div>
</div>
