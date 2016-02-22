(function (window) {
  if (wx) {
    wx.config({
      debug: false,
      appId: options.appId,
      timestamp: options.timestamp,
      nonceStr: options.nonceStr,
      signature: options.signature,
      jsApiList: [
        'onMenuShareAppMessage',
        'onMenuShareTimeline',
        'onMenuShareQQ',
        'onMenuShareWeibo'
      ]
    });

    wx.ready(function() {
      var message = {
        title: '全渠道经营客户第一平台',
        link: 'https://www.quncrm.com',
        desc: '群脉Social CRM，最专业的B2C客户经营平台。帮商家通过互联网及实体店连接客户，基于大数据全方位了解客户，建立会员制度持久性留住客户，线上线下全渠道实现个性化客户服务，高转化市场营销和场景化销售管理',
        imgUrl: 'https://dn-quncrm.qbox.me/build/landing/images/wechat_share_image.925027cd.png'
      };
      wx.onMenuShareAppMessage(message);
      wx.onMenuShareQQ(message);
      wx.onMenuShareWeibo(message);
      wx.onMenuShareTimeline(message);
    });
  }
})(window);
