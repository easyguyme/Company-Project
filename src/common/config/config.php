<?php
/**
 * The global config
 */
/**
 * Mongo db config
 * 'dsn' => 'mongodb://developer:password@localhost:27017/mydatabase'
 */
define('MONGO_USER_NAME', 'root');
define('MONGO_USER_PASSWORD', 'root');
define('MONGO_HOST', '127.0.0.1');
define('MONGO_PORT', '27017');
define('MONGO_DATABASE', 'wm');
/**
 * Send Cloud config
 */
define('SENDCLOUD_API_USER', 'tubuqulvxing_test_ZUzjGV');
define('SENDCLOUD_API_KEY', 'MO0JLapIEPmlKGpZ');
define('SENDCLOUD_FROM', 'wemarketing@126.com');
define('SENDCLOUD_FROM_NAME', 'WeMarketing');
/**
 * Resque config
 */
define('RESQUE_HOST', '127.0.0.1');
define('RESQUE_PORT', '6379');
define('RESQUE_DB', 2);
define('RESQUE_PASSWD', null);
/**
 * Qiniu config
 */
define('QINIU_BUCKET', 'vincenthou');
define('QINIU_BUCKET_PRIVATE', 'pvincenthou');
define('QINIU_ACCESS_KEY', 'QK5YJSJHDKQmlXQq5W4RQqNDTOr5RPCPiVbTqoW-');
define('QINIU_SECRET_KEY', '6JJVtM3IfwfOdbjRgsdTJx-6hHMoNspKqRC_Idlo');
define('QINIU_DOMAIN', 'http://vincenthou.qiniudn.com');
define('QINIU_DOMAIN_PRIVATE', '7xjkkn.dl1.z0.glb.clouddn.com');
define('QINIU_UPLOAD_DOMAIN', 'http://upload.qiniu.com');
/**
 * Shorten URL Service config
 */
define('SHORT_URL_DOMAIN', 'http://u.omnisocials.com');
/**
 * 3rd party
 */
define('WECONNECT_DOMAIN', 'http://dev.wx.quncrm.com');
define('TUISONGBAO_DOMAIN', 'https://api.tuisongbao.com');
define('TUISONGBAO_APP_ID', '551ba61ce944fbf0571b19f4');
define('TUISONGBAO_SECRET', 'd4a5d2b57af36e2c2aa35248');
define('TUISONGBAO_PUSH_APP_ID', '551b4f48e944fbf0571b0b06');
define('TUISONGBAO_PUSH_SECRET', '4a6b5454a977e1dd1a5087ae');
/**
 * Config for cache
 */
define('CACHE_PORT', 6379);
define('CACHE_HOSTNAME', '127.0.0.1');
define('CACHE_DB', 1);
define('CACHE_PASSWD', null);
/**
 * Wechat JS SDK config
 */
define('WECAHT_JSSDK_APP_ID', 'wx6559dc399869bc69');
define('WECAHT_JSSDK_APP_SECRET', '772712d3ea97e3d5cdf13c131c89536f');
define('DOMAIN', 'http://wm.com/');
define('CHANNEL_ID', '54e1a24ce4b02c78eb8d3753');
/**
 * Yunpian config
 */
define('YUNPIAN_DOMAIN', 'http://yunpian.com/v1/sms/send.json');
define('YUNPIAN_API_KEY', 'c87163327283e2dc44d3cfe71a251cde');
/**
 * Weibo config
 */
define('WEIBO_APP_KEY', '481734740');
define('WEIBO_APP_SECRET', '7ca11e61530ef9a3a14cfe5b9d2c6233');
define('WEIBO_REDIRECT_URI', '/management/oauth2/accesstoken');
/**
 * webhook
 */
define('WEBHOOK_DOMAIN', 'https://sandbox-api.quncrm.com');
/**
 * Log config
 */
define('FILE_LOG', 1);
define('LOG_LEVEL', 'info.error');
/* TODO: Change to internal endpoint after deployment */
define('SLS_ENDPOINT', 'cn-hangzhou.sls.aliyuncs.com');
define('SLS_ACCESS_KEY_ID', 'HCY16IorxExaTugH');
define('SLS_ACCESS_KEY_SECRET', '7uG7gJ1APOlbL5ZXuGTUaVqxhhndVY');
define('SLS_PROJECT_ID', 'omnisocials');
define('SLS_LOG_STORE', 'errorlogs');
define('SLS_LOG_TOPIC', 'WebPortal');
define('FRONTEND_TRACK_URL', '/api/log/frontend');
define('CURRENT_ENV', 'local');

/**
 * KLP Config
 */
define('KLP_ACCOUNT_ID', '559b7615fd604ab7768b4567');
define('KLP', false);

/**
 * online helpdesk account id
 */
define('HELPDESK_ACCOUNT_ID', '54f6cfef8f5e88b96a8b4567');

/**
 * Wechat Corp Suite Config
 */
define('WECHAT_CP_HELPDESK_SUITE_ID', 'tjdd59ac93afc01004');
define('WECHAT_CORP_DOMAIN', 'https://qy.weixin.qq.com');
define('WECHAT_CORP_APP_ID', '2');
