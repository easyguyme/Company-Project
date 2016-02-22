<span class="mb-breadcrumb">
    <span class="mb-breadcrumb-back"></span>
    <span class="mb-breadcrumb-address-back" style="display:none"></span>
    <span class="mb-breadcrumb-pickup-back" style="display:none"></span>
    <span class="mb-breadcrumb-title">兑换详情</span>
</span>

<div class="goods-detail-redeem">
    <div class="goods-address-wrapper">
        <p class="goods-shipping-method">选择配送方式</p>
        <div id="deliveryWrapper" class="goods-shipping-address">
            <img id="deliveryRadio" class="radio-image" src="/images/product/radio_button_checked.png"/>
            <div>
                <input id="chooseDelivery" class="hide" type="radio" name="address" value="express">
                <span class="radio-text">快递送货</span>
            </div>
            <div id="deliveryAddress">
                <hr>
                <div id="displayDeliveryAddress" class="address-text clearfix text-el hide">
                    <div class="flex-wrapper">
                        <div class="address-title">收货地址: </div><div class="address-content text-el" id="shippingAddr"></div><div class="address-choose-link edit-address choose-delivery-address">修改</div>
                    </div>
                </div>
                <div id="chooseDeliveryAddress" class="clearfix">
                    <span class="address-choose-link choose-delivery-address pull-right">选择快递送货地址</span>
                </div>
            </div>
        </div>
        <div id="pickupWrapper" class="goods-shipping-address">
            <img id="pickupRadio" class="radio-image" src="/images/product/radio_button_default.png"/>
            <div>
                <input id="choosePickup" class="hide" type="radio" name="address" value="self">
                <span class="radio-text">上门自提</span>
            </div>
            <div id="pickupAddress" class="hide">
                <hr>
                <div id="displayPickupAddress" class="address-text clearfix text-el hide">
                    <div class="flex-wrapper">
                        <div class="address-title">自提地址: </div><div class="address-content text-el"  id="pickupAddr"></div><div class="address-choose-link edit-address choose-pickup-address">修改</div>
                    </div>
                </div>
                <div id="choosePickupAddress" class="clearfix">
                    <span class="address-choose-link choose-pickup-address pull-right">选择上门自提地址</span>
                </div>
            </div>
        </div>
    </div>
    <div class="goods-snap-wrapper">
        <div class="goods-snap">
            <img id="goods-pic" src="/images/content/default.png" />
            <span id="goods-name" class="goods-snap-title">Goods</span>
            <span id="goods-points" class="goods-snap-points goods-points">0</span>
            <span class="goods-quantity">×<span id="goods-count"></span></span>
        </div>
        <div class="goods-consume-scores clearfix">
            <span class="consume-score-tip fl">消耗积分</span>
            <span class="fr"><span style="font-size:1.5rem" id="totalScore"></span>积分</span>
        </div>
    </div>


    <form class="mb-form">
        <div class="mb-form-group">
            <label>手机号</label>
            <input type="tel" class="mb-input-form" id="phone"/>
            <div id="phone-tip" class="mb-error-tip"></div>
        </div>
        <div class="mb-form-group verification-form-group">
            <input type="text" class="mb-input-form" placeholder="请输入下图中的字符，不区分大小写" maxlength="4" id="verification">
        </div>
        <div class="mb-form-group pic-form-group">
            <img id="icon-verification-code" class="icon-verification-code" src="">
            <span id="btn-change-pic" class="mb-link link-change-validate">看不清？换一张</span>
            <span id="verification-tip" class="mb-error-tip"></span>
        </div>
        <div class="mb-form-group">
            <label>验证码</label>
            <input type="text" class="mb-input-form" id="captcha"/>
            <a href="#" class="mb-validate-btn mb-btn" id="get-code">获取验证码</a>
            <div id="captcha-tip" class="mb-error-tip"></div>
        </div>
        <a href="#" class="mb-confrim-btn mb-btn mb-btn-disable" id="submit">提交</a>
    </form>
</div>

<div class="shipping-address-wrapper">
    <div class="address-wrapper">
        <div class="clearfix">
            <div class="address-box address-box-half" data-btn="province">
                <img class="select-address-btn" src="/images/mobile/detail.png">
                <span id="province">省份/直辖市</span>
            </div>
            <div class="address-box address-box-half" data-btn="city">
                <img class="select-address-btn" src="/images/mobile/detail.png">
                <span id="city">市</span>
            </div>
        </div>

        <div class="address-box" data-btn="district">
            <img class="select-address-btn" src="/images/mobile/detail.png">
            <span id="district">区/县</span>
        </div>
        <input id="detailAddr" class="address-box detail-address" />
    </div>
    <div>
        <div id="addrCancelBtn" class="mb-btn btn-half">取消</div>
        <div id="addrOkBtn" class="mb-btn btn-half">确定</div>
    </div>
</div>

<div class="pickup-address-wrapper">
</div>

<div class="select-address-shadow">
    <div class="popup-container">
        <div class="popup-item clearfix">
            <span class="fl" id="btnCancel">取消</span>
            <span class="popup-btn-ok fr" id="btnOk">确定</span>
        </div>
        <ul class="address-items-wrapper">
        </ul>

    </div>
</div>

<div id="exchange-msg-wrapper" style="display:none">
    <div class="exchange-msg-card">
        <div class="exchange-msg-title">兑换成功<span id="time" class="exchange-time fr"></span></div>
        <div class="exchange-msg">手机：<span id="telephone"></span></div>
        <div class="exchange-msg">配送方式: <span id="shippingMethod"></span></div>
        <div class="exchange-msg"><span id="expressMethod">收货</span><span id="pickupMethod">自提</span>地址：<span id="shipAddr"></span></div>
        <div class="error-msg"></div>
    </div>
    <div style="overflow:hidden;">
        <a id="index-btn" class="mb-btn return-btn fr" href="#">返回首页</a>
        <a id="view-btn" class="mb-link view-order fr" href="#">查看我的订单</a>
    </div>
</div>
