<account>
  <section class="c-account">
    <label if={ opts.item.key } class="c-account__key">{ opts.item.key }：</label>
    <div each={ account in accounts } class="c-account__item">
      <span class="c-account__item__icon" riot-style="background-image: { account.icon }"></span>
      <span>{ account.name }</span>
    </div>
  </section>

  var self = this, _formateAccount, origins

  origins = {
    WECHAT: 'wechat',
    WEIBO: 'weibo',
    ALIPAY: 'alipay',
    PORTAL: 'portal',
    APP_ANDROID: 'app:android',
    APP_IOS: 'app:ios',
    APP_WEB: 'app:web',
    APP_WEBVIEW: 'app:webview',
    OTHERS: 'others'
  }

  self.accounts = []

  this.on('updated', () => {
    _formateAccount();
  });

  _formateAccount = () => {
    if (opts.item) {
      if (opts.item.accounts) {
        for (let index = 0, len = opts.item.accounts.length; index < len; index++) {
          let item, account, accountTypeName, accountName
          item = opts.item.accounts[index]
          if (item.type) {
            item.typeName = item.type.toLowerCase()
          }
          switch (item.origin) {
            case origins.WEIBO:
              accountTypeName = 'weibo'
              accountName = item.name
              break
            case origins.WECHAT:
              let serviceAccounts = ['service_auth_account', 'service_account']
              if ($.inArray(item.typeName, serviceAccounts) > -1) {
                accountTypeName = 'wechat_service'
              } else {
                accountTypeName = 'wechat_subscription'
              }
              accountName = item.name
              break
            case origins.ALIPAY:
              accountTypeName = 'alipay'
              accountName = item.name
              break
            case origins.PORTAL:
              accountTypeName = 'portal'
              accountName = 'SCRM后台'
              break
            case origins.APP_ANDROID:
              accountTypeName = origin.replace(':', '_')
              accountName = 'Android App'
              break
            case origins.APP_IOS:
              accountTypeName = origin.replace(':', '_')
              accountName = 'iOS App'
              break
            case origins.APP_WEB:
              accountTypeName = origin.replace(':', '_')
              accountName = '网站'
              break
            case origins.APP_WEBVIEW:
              accountTypeName = origin.replace(':', '_')
              accountName = '手机版网站'
              break
            default:
              accountTypeName = 'others'
              accountName = '其他'
          }

          account = {
            icon: 'url(\'/images/customer/' + accountTypeName + '.png\')',
            name: accountName
          }
          self.accounts.push(account)
        }
        self.update()
      }
    }
  }
</account>
