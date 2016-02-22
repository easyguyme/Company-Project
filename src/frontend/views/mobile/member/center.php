<!--Member center page-->
<div class="member-center-wrapper">
    <div id="open-member">
        <div class="member-center-card">
            <div class="member-center-card-icon">
                <div class="center-icon-component">
                    <span class="form-icon"></span>
                    <button class="start-member-btn" onclick="openMemberShip()">点击开通会员卡</button>
                </div>
            </div>
        </div>
      <!-- privilege -->
        <div class="member-privilege-wrapper clearfix">
            <div class="member-title">
                <span class="member-icon"></span>
                <span class="member-word">会员专属特权</span>
            </div>
            <div class="member-body">
                <div class="member-group">
                    <div class="member-title-child" id="privilege"></div>
                </div>
            </div>
        </div>
      <!-- scoreRule -->
        <div class="member-scorerule-wrapper clearfix">
            <div class="member-title">
                <span class="member-icon"></span>
                <span class="member-word">积分规则</span>
            </div>
            <div class="member-body" id="score-rule">
            </div>
        </div>
      <!-- usageGuide -->
        <div class="member-usageguide-wrapper clearfix md40">
            <div class="member-title">
                <span class="member-icon"></span>
                <span class="member-word">会员卡使用说明</span>
            </div>
            <div class="member-body">
                <div class="member-group">
                    <div class="member-title-child" id="usageguide"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Opened memberShip -->
    <div id="opened-member">
        <div class="center-card">
            <img src="/images/mobile/img_qrcode.png" id="btn-toggle-qrcode" data-type="card" class="icon-toggle-qrcode" onclick="toggleQrcode()">
            <div class="card-info-wrapper">
                <div class="center-left">
                    <div class="center-card-name"></div>
                    <div class="center-card-number"></div>
                </div>
                <div class="center-member-name">
                    <div class="center-card-username"></div>
                </div>
                <div class="center-component">
                    <span class="form-expired-icon"></span>
                    <button class="start-member-expired-btn">已失效</button>
                </div>
            </div>
            <div class="qrcode-info-wrapper clearfix">
                <div class="qrcode-picture-wrapper">
                    <img class="picture-qrcode" src="https://dn-quncrm.qbox.me/55b98efa137473fa418b4575.png">
                </div>
            </div>
        </div>
        <div class="center-list">

            <div class="center-list-block block-top memberpoints">
                <span class="center-list-block-number"></span>
                <span class="center-list-block-text">积分</span>
                <img src="/images/mobile/detail.png" class="center-list-block-arrow">
                <span class="center-list-block-tip">积分明细</span>
            </div>

            <div class="center-list-block block-middle memberprivilege">
                <span class="center-list-block-text">会员专属特权</span>
                <img src="/images/mobile/detail.png" class="center-list-block-arrow">
            </div>

            <div class="center-list-block block-middle personalinformation">
                <span class="center-list-block-text">个人信息</span>
                <img src="/images/mobile/detail.png" class="center-list-block-arrow">
                <span class="center-list-block-tip">完善个人信息领取奖励</span>
            </div>

            <div class="center-list-block block-middle mycoupon">
                <span class="center-list-block-text">我的优惠</span>
                <img src="/images/mobile/detail.png" class="center-list-block-arrow">
            </div>

            <div class="center-list-block block-bottom exchangerecord">
                <span class="center-list-block-text">兑换记录</span>
                <img src="/images/mobile/detail.png" class="center-list-block-arrow">
            </div>
        </div>

        <div class="member-tip">
            <div class="member-font-style">您的会员卡已经失效,</div>
            <div class="member-font-style">如有问题请联系我们客服</div>
        </div>
    </div>

    <div class="mb-popup-container">
        <div class="qrcode-guide-wrapper"></div>
    </div>
</div>
