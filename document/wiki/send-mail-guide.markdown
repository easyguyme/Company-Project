## Send email with SendCloud
### 1. Create template in SendCloud
Login in [SendCloud](http://sendcloud.sohu.com/), and create an email subject and an email template in SendCloud. 

The template likes the following:

```html
<p>{{name}}，</p>

<p>　　你好</p>

<pre style="color: rgb(0, 0, 0); font-size: 14px; line-height: 23.32400131225586px;">
　　你现在可以通过点击下面的链接或者复制到你的浏览器里进行激活:
　　<a href="http://wm.com/site/activate?code=XXXXXXXXXXX">http://wm.com/site/activate?code=XXXXXXXXXXX</a>
　　这个登录链接只能使用一次。
</pre>

<p>-- WeMarketing 团队</p>
```

### 2. Create template in aug-marketing
Create a layout template in backend\views\layouts folder or use the default layout template backend\views\layouts\mail.php. Then, create a mail view in backend\views\mail folder. The mail view be similar with template in SendCloud.

The mail view likes the following:

```php
<p><?php echo $name;?>，</p>

<p>　　你好</p>

<pre style="color: rgb(0, 0, 0); font-size: 14px; line-height: 23.32400131225586px;">
　　你现在可以通过点击下面的链接或者复制到你的浏览器里进行激活:
　　<a href="<?php echo $link;?>"><?php echo $link;?></a>
　　这个登录链接只能使用一次。
</pre>
```

### 3. Use it in your controller
Use like the following:

```php
$mail = Yii::$app->mail;
$vars = [
    'name' => $user->name,
    'link' => Yii::$app->request->hostInfo . '/api/site/activate?code=' . $validation->code
];
$mail->setView('//mail/register', $vars, '//layouts/email');
$mail->sendMail($user->email, '欢迎注册ＷeMarketing');
```

Yii::$app\-\>mail is a backend\components\mail\Mailer instance, $var is the variables in views, setView function used to set a view with view, variables and layout. '//mail/register' is the email view, '//layouts/email' is the email layout. '欢迎注册ＷeMarketing' is the email subject.