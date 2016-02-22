<?php
/**
 * The global config
 */
/**
 * Mongo db config
 * 'dsn' => 'mongodb://developer:password@localhost:27017/mydatabase'
 */
define('MONGO_USER_NAME', '{{ mongodb.db.username }}');
define('MONGO_USER_PASSWORD', '{{ mongodb.db.password }}');
define('MONGO_HOST', '{{ mongodb.host }}');
define('MONGO_PORT', '{{ mongodb.port }}');
define('MONGO_DATABASE', '{{ mongodb.db.name }}');
/**
 * Send Cloud config
 */
define('SENDCLOUD_API_USER', '{{ settings.mail.api_user }}');
define('SENDCLOUD_API_KEY', '{{ settings.mail.api_key }}');
define('SENDCLOUD_FROM', '{{ settings.mail.from }}');
define('SENDCLOUD_FROM_NAME', '{{ settings.mail.from_name }}');
/**
 * Resque config
 */
define('RESQUE_HOST', '{{ resque.host }}');
define('RESQUE_PORT', '{{ resque.port }}');
define('RESQUE_DB', '{{ resque.db }}');
define('RESQUE_PASSWD', {{resque.passwd}});
/**
 * Qiniu config
 */
define('QINIU_BUCKET', '{{ qiniu.bucket }}');
define('QINIU_BUCKET_PRIVATE', '{{ qiniu.private_bucket }}');
define('QINIU_ACCESS_KEY', '{{ qiniu.access_key }}');
define('QINIU_SECRET_KEY', '{{ qiniu.secret_key }}');
define('QINIU_DOMAIN', '{{ qiniu.domain }}');
define('QINIU_DOMAIN_PRIVATE', '{{ qiniu.private_domain }}');
define('QINIU_UPLOAD_DOMAIN', '{{ qiniu.upload_domain }}');
define('QINIU_THUMBNAIL_LITE', '{{ qiniu.thumbnail_lite }}');
define('QINIU_THUMBNAIL_SMALL', '{{ qiniu.thumbnail_small }}');
define('QINIU_THUMBNAIL_MIDDLE', '{{ qiniu.thumbnail_middle }}');
/**
 * Shorten URL Service config
 */
define('SHORT_URL_DOMAIN', '{{ shorturl.domain }}');
/**
 * 3rd party
 */
define('WECONNECT_DOMAIN', '{{ wechat.domain }}');
define('TUISONGBAO_DOMAIN', '{{ tuisongbao.domain }}');
define('TUISONGBAO_APP_ID', '{{ tuisongbao.app_id}}');
define('TUISONGBAO_SECRET', '{{ tuisongbao.secret}}');
define('TUISONGBAO_PUSH_APP_ID', '{{ tuisongbao.push_app_id}}');
define('TUISONGBAO_PUSH_SECRET', '{{ tuisongbao.push_secret}}');
/**
 * Config for cache
 */
define('CACHE_PORT', '{{ cache.port }}');
define('CACHE_HOSTNAME', '{{ cache.host }}');
define('CACHE_DB', '{{ cache.db }}');
define('CACHE_PASSWD', {{cache.passwd}});
/**
 * Wechat JS SDK config
 */
define('WECAHT_JSSDK_APP_ID', '{{ wechat.jssdk.app_id }}');
define('WECAHT_JSSDK_APP_SECRET', '{{ wechat.jssdk.app_secret }}');
define('DOMAIN', '{{ wechat.jssdk.domain }}');
define('CHANNEL_ID', '{{wechat.jssdk.channel_id}}');
/**
 * Yunpian config
 */
define('YUNPIAN_DOMAIN', '{{ yunpian.domain }}');
define('YUNPIAN_API_KEY', '{{ yunpian.api_key }}');
/**
 * Weibo config
 */
define('WEIBO_APP_KEY', '{{ weibo.app_key }}');
define('WEIBO_APP_SECRET', '{{ weibo.app_secret }}');
define('WEIBO_REDIRECT_URI', '{{ weibo.redirect_url }}');
/**
 * webhook
 */
define('WEBHOOK_DOMAIN', '{{ webhook.domain }}');
/**
 * Log config
 */
define('FILE_LOG', '{{ log.file_log }}');
define('LOG_LEVEL', '{{ log.level }}');
/* TODO: Change to internal endpoint after deployment */
define('SLS_ENDPOINT', '{{ log.sls.endpoint }}');
define('SLS_ACCESS_KEY_ID', '{{ log.sls.access_key_id }}');
define('SLS_ACCESS_KEY_SECRET', '{{ log.sls.access_key_secret }}');
define('SLS_PROJECT_ID', '{{ log.sls.project_id }}');
define('SLS_LOG_STORE', '{{ log.sls.log_store }}');
define('SLS_LOG_TOPIC', '{{ log.sls.log_topic }}');
define('FRONTEND_TRACK_URL', '{{ log.frontend.track_url }}');
define('CURRENT_ENV', '{{ global.current_env }}');

/**
 * KLP Config
 */
define('KLP_ACCOUNT_ID', '55a8709d137473b60d8b4573');
define('KLP', false);

/**
 * online helpdesk account id
 */
define('HELPDESK_ACCOUNT_ID', '{{ helpdesk.account_id }}');

/**
 * Wechat Corp Suite Config
 */
define('WECHAT_CP_HELPDESK_SUITE_ID', 'tjdd59ac93afc01004');
define('WECHAT_CORP_DOMAIN', 'https://qy.weixin.qq.com');
define('WECHAT_CORP_APP_ID', '2');
