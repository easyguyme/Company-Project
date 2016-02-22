<!-- signup -->
<div class="signup">
    <div class="spaceship-bg"></div>

    <div class="signup-reminder">
        <span class="reminder-line reminder-line-left"></span>
        <span>
            <span class="visible-md-block">提交申请后我们会尽快联系您</span>
            <span class="hidden-md">，</span>
            <span class="visible-md-block">并为您创建账号</span>
        </span>
        <span class="reminder-line reminder-line-right"></span>
    </div>

    <form class="pure-form pure-form-stacked form-signup">
        <div class="pure-control-group">
            <input type="email" class="form-control-email" placeholder="邮箱" demanded />
        </div>
        <div class="pure-control-group">
            <input type="text" class="form-control-company" placeholder="企业名称" demanded />
        </div>
        <div class="form-reminder-info">
            <span>请完善您的个人信息方便我们联系您哦～</span>
        </div>
        <div class="pure-control-group">
            <input type="text" class="form-control-name" placeholder="联系人" demanded />
        </div>
        <div class="pure-control-group">
            <input type="text" class="form-control-phone" placeholder="手机号" demanded />
        </div>
        <div class="pure-control-group">
            <input type="text" class="form-control-position" placeholder="职位" demanded />
        </div>
        <div class="pure-control-group">
            <input type="text" class="form-control-verification" placeholder="请输入下图中的字符，不区分大小写" demanded />
        </div>
        <div class="pure-control-group">
            <img class="icon-verification-code">
            <a id="getVerificationCode" class="change-captcha-link">看不清？换一张</a>
        </div>
        <div class="pure-control-group pure-g phone-group ">
            <div class="pure-u-1-2 pure-u-md-5-8 custom-captcha-input">
                <input type="text" class="form-control-captcha" placeholder="验证码" demanded />
            </div>
            <div class="pure-u-1-2 pure-u-md-3-8 custom-captcha-get">
                <a id="getCaptcha" class="pure-button button-obtain-captcha">获取验证码</a>
            </div>
        </div>
        <div class="pure-control-group text-center">
            <a id="submit" class="pure-button button-default button-signup-submit">提交</a>
        </div>
    </form>

    <div class="agreement-reminder-info">
        点击提交即表示你同意<a href="/site/agreement?type=agreement">群脉服务协议</a>
    </div>
</div>
