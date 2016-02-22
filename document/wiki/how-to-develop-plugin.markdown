# Introduction

Every module contains two parts: frontend and backend, so that a feature task can be divided and assigned separately. To develop a new module for our system, you only need to follow the pattern introduced below and focus on your module logic, we provide tools for building and merging your modules into our system.

## Precondition

Please make sure you have setup dependent environment, if not, please get help [here](setup-environment). Make sure all the tools are installed on your development machine and works fine.

## Tech stack

To develop a module, you should make sure you have grasp the basic usage of our technology.
* **Frontend:** [Angularjs](http://ngnice.com/), [Requirejs](http://requirejs.org/), [bootstrap(angular-ui-bootstrap)](https://github.com/angular-ui/bootstrap), [SASS](http://sass-lang.com/), [CoffeeScript](http://coffeescript.org/)
* **Backend:** [Yii2.0](http://www.yiiframework.com/doc-2.0/guide-index.html), [Yii-resque](http://git.augmentum.com.cn/vincenthou/yii-resque-ex)

# Setup Guide

## Create module steps

### Create individual module repository

* Create a repository named as "OmniSocials Module XXX (omnisocials-module-xxx)" under SCRM namespace, example: [demo project](http://git.augmentum.com.cn/scrm/omnisocials-module-demo) .

Clone the project to your local folder

```
git clone git@git.augmentum.com.cn:scrm/omnisocials-module-xxx.git
```

* Generate the template files with [yeoman generator](http://git.augmentum.com.cn/scrm/generator-omnisocials-module)

Setup generator can be used locally:

```sh
git clone git@git.augmentum.com.cn:scrm/generator-omnisocials-module.git
cd generator-omnisocials-module
npm link
```

Generate project template with the tool and answer some questions for your needs

```sh
cd omnisocials-module-xxx
yo omnisocials-module
```

**Notice:** Use the module name correctly, for example: enter **demo** as name instead of **omnisocials-module-demo** or **omnisocials module demo**

* Push initial code to new repo master branch

```sh
git add .
git commit -am 'Init project'
git push origin master
```

### Add module to omnisocials core project

* Clone core project code and checkout a new branch from `develop` branch to send merge request

```
git clone git@git.augmentum.com.cn:scrm/aug-marketing.git
cd aug-marketing
git checkout -b feature-add-xxx-module develop
```

* Generate submodule and module linking related configuration

Add module under SCRM namespace

```sh
cd src
./yii module/add xxx
```

Add module that is not under SCRM namespace

```sh
cd src
./yii module/add xxx git@git.augmentum.com.cn:others/omnisocials-module-xxx.git
```

After execute the `yii` command, open the .gitmodules file, you will see

```sh
[submodule "src/modules/xxx"]
  path = src/modules/xxx
  url = git@git.augmentum.com.cn:scrm/omnisocials-module-xxx.git
```

Add the branch you need to build for stage server and ignore untracked content

```sh
[submodule "src/modules/xxx"]
  path = src/modules/xxx
  url = git@git.augmentum.com.cn:scrm/omnisocials-module-xxx.git
  ignore = dirty
  branch = develop
```

* Ony pick `.gitmodules` , `submodule configuration` and `package.json` file for merging and push the branch to omnisocial repository

```
user@user-OptiPlex-3020:/usr/share/nginx/html/aug-marketing/src$ git status
On branch feature-add-test-module
Changes to be committed:
  (use "git reset HEAD <file>..." to unstage)

  modified:   ../.gitmodules
  new file:   modules/xxx

Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git checkout -- <file>..." to discard changes in working directory)

  modified:   modules/demo (new commits)
  modified:   modules/game (new commits)
  ...
  modified:   package.json
```

**Tip:** `submodule configuration` means `new file:   modules/xxx` shown above

```
git add package.json
git add ../.gitmodules
git add modules/xxx
git commit -m 'feat(xxx): add xxx module configuration'
git rebase -i develop
git push origin feature-add-xxx-module -f
```

**Notice:** Create merge request on gitlab and **assgin merge request to @vincenthou** for reviewing

## Clone repositories

**Notice:** **xxx** talked below is your project name

* Clone omnisocials repository to local folder after @vincenthou create your submodule on gitlab

```sh
git clone git@git.augmentum.com.cn:scrm/aug-marketing.git
```

or existing project

```sh
git pull -r
```

* Follow the get-started introduction in project README.md file to enable module

Then follow the guide below to develop your module.

**Important:** Only commit your code under your module folder, don't push to omnisocials project, all the path below is under your repository.

# Frontend guide

All the frontend modules are located in the folder `/static` and named as the module name defined in `config.json` (we will talk about it later).

## Folder structure

A standard module folder structure looks like this:

```sh
static
  ├── config.json
  ├── controllers
  ├── directives
  ├── filters
  ├── services
  ├── i18n
  ├── index.scss
  ├── introduction.html
  ├── introduction.json
  ├── partials
  ├── images
  └── styles
```

* config.json: The configuration information of the modules, including name, states(the states used by [ui-router](https://github.com/angular-ui/ui-router)), resources(the rest API URL path)
* controllers: All the page controllers are placed here
* i18n: All the page i18n translations are placed here
* index.scss: The loader file for all the styles files, `/grunt build` will merge and compile the css rules into app.css file
* introduction.html: the introduction page template used by the extension detailed page
* introduction.json: the introduction page content configuration used by the extension detail page and list page
* partials: All the page templates
* styles: All the page style files are placed here and loaded with `index.scss`

All the files above following some naming convention. Add a state in `config.json` first, then follow the guide [here](frontend-development-guide#add-angular-controller-for-the-page) to add your controller and partial. There is no special rules for style files naming, just use modular way to struct them and load them in `index.scss`. I18n files related topic can be found [here](frontend-development-guide#i18n-in-modules)

## Add images for module

All the images needed for module should be placed under `static/images` folder, after run `grunt cbuild`, newly added images can be accessed with path `{protocol}://{domain}/images/{moduleName}/xxx.png`. For example. a image named `test.png` is placed under `static/images` folder of member module, run `grunt cbuild`, access it with `{protocol}://{domain}/images/member/test.png`

## Configure your extension

There are three important parts here:

* name: the extension name, same as the backend module name
* states: an array used to mapping URL path for partials and controllers
* resources: a map used to define rest API URL path, key is the resource name, value is the API URL path

## Add menu icons

Create a `/nav` folder in `/static/images` folder and put images in it (example: /static/images/nav). Follow the naming convention below to add needed images.

* Default state icon: `xxx_default.png`
* Hover state icon: `xxx_hover.png`
* Selected state icon: `xxx_selected.png`

`xxx` is the name field defined in `menusConfig` list of `backend/config/main.php` file.

## Add extension introduction

### Add introduction related images

Create a `/introduction` folder in `/static/images` folder and put images in it (example: /static/images/introduction). Follow the naming convention below to add needed images.

* **Folder convention:** Introduction images should be placed under `introduction` folder
* **Image naming convention:** Extension logo is named as `icon_default.png` and its hover effect image is named as `icon_hover.png`; description list item dot icon is named as `paragraphbreak_default.png` and its hover effect image is named as `paragraphbreak_hover.png`. The icon before `Features` title is named as `icon_functional.png`.
* **Other images:** Place them under `introduction` folder, name them freely and refer them in your `introduction.html` with path `{protocol}://{domain}/images/{moduleName}/introduction/xxx.png`.

### Configuration for extension list page

Create a `introduction.json` file under `/static` folder following the configuration below. I18n keys in introduction.json are put under 118n folders as usual.

**Basic configuration in introduction.json:**

Configuration below is used for descriptions, icon, and the name of extension in the extension list page.

```json
  {
    "name": "microsite",
    "title": "management_extension_microsite_title",
    "illustrations": ["management_extension_microsite_illustration1", "management_extension_microsite_illustration2"]

    ...
  }
```

![illustrations](images/how-to-develop-plugin/illustrations.png)

* **illustrations:** is arrray, each element is i18n key which corresponds to each paragraph break dot.
* **title:** is a i18n key and displaying title in available modules list.
* **name:** is the module name

### Configuration for detailed extension page

Standard structure of extension detailed page
* Module introduction is on the top of page
* Features at the bottom of page

**Tip:** If your module has not only two standard parts described above, you should add the specific partial under the `/static` folder named as `introduction.html` and add needed keys in `introduction.json` file. The keys show below are standard structure, extra keys for `introduction.html` can be defined freely.

**Detailed page configuration in introduction.json:**

Configuration below is used for the descriptions, icon, and name of a extension in the detailed extension page.

```json
{
  ...

  "link": {
    "enterFunction": "/microsite/webpage"
  },
  "havePartial": false,
  "introductions": ["management_extension_microsite_introduction1"],
  "functions": [
    {
      "title": "management_extension_microsite_function1",
      "image": ["/images/microsite/introduction/combinationofrichcontrols.png"]
    },
    {
      "title": "management_extension_microsite_function2",
      "image": ["/images/microsite/introduction/thetitlecomponentstyles.png", "/images/microsite/introduction/articlecomponentstyles.png", "/images/microsite/introduction/textcomponentstyles.png"]
    },
    {
      "title": "management_extension_microsite_function3",
      "image": ["/images/microsite/introduction/coverpageone.png", "/images/microsite/introduction/coverpagetwo.png", "/images/microsite/introduction/coverpagethree.png"]
    }
  ]

  ...
}
```

![title](images/how-to-develop-plugin/title-part.png)

![partial](images/how-to-develop-plugin/partial.png)

![illustrations](images/how-to-develop-plugin/functions.png)

* **link:** 'enterFunction' Entry URL for the extension
* **havePartial:** Whether you have specific partial
* **introductions:** It is an array, each element is an i18n key mapping to introduction list item（module introductions on top of the detailed page）.
* **functions:** It is an array, each element is an object. 'title' is the title of feature item. 'image' is array which includes pictures of each feature. Images names can be defined freely, but they should be placed in the module introduction folder.

**Extra keys for detailed page configuration in introduction.json:**

Configuration below is a sample of extra keys in the detailed extension page.

```json
{
  ...

  "advantages": ["management_extension_helpdesk_use1", "management_extension_helpdesk_use2"],

  ...
}
```

The keys above can be refered directly on `extension.detail` object in `introduction.html`

```html
<ul class="advantage-wrapper clearfix">
  <li class="pull-left" ng-repeat="advantage in extension.detail.advantages track by $index">
    <img ng-src="/images/{{extension.detail.name}}/introduction/paragraphbreak_default.png">
    <span>{{advantage | translate}}</span>
  </li>
</ul>
```

The `extension.detail.active` will be `true` if the extension is activated, otherwise will be `false`.

For example, the content in div will display in partial when the extension is activated.

```html
<div class="advantage-wrapper clearfix" ng-if="extension.detail.active">
	<!-- your partial contents -->
</div>
```
## Add directives, filters, services for module

We use [angular-couch-potato](https://github.com/laurelnaiad/angular-couch-potato) to lazy load module, so you need to follow its routines. Let's take a simple directive called "extTest" as an example:

* Add `/directives` folder if it is not created in `/static` folder.
* Add a file called "extTest.coffee" in the directives folder, and write related code as shown below:

```coffee
define ['wm/app'], (app) ->

  app.registerDirective 'extTest', [
    () ->
      return (
          restrict: "A"
          replace: true
          template: '<p>Vincent</p>'
        )
  ]
```

**Note:** Add **wm/app** as AMD module dependency, register the directive on the app using function **registerDirective**.

* Declare the dependency in the page controller using the directive

```coffee
define [
  'wm/app'
  'wm/config'
  'module/content/directives/extTest'
], (app, config) ->
  app.registerController 'wm.ctrl.xxx.xxx', [

  ...

```

**Note:** The pattern for the dependency is **"module/[your module name]/[directives|services|filters]/[your file name]"**

* Use the directive freely in the partial which the above controller is related to.

```html
<div class="container">
  <div ext-test></div>
</div>
```

## Build your portal module

Actually the build step is implemented silently，you just need to run

```sh
grunt build
```

New index.scss file will be added to the loader.scss file, you can commit it or not (it will be generated again when building)

## Webapp guide (mobile pages)

All the mobile pages are placed in the `/webapp` folder. Add your module pages in modules folder and follow yii2.0 best practice to construct your module. To get more information about yii2.0 module, you can refer [here](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html). Folder structure looks like this:

```sh
webapp
  ├── assets
  ├── config
  ├── controllers
  ├── Module.php
  ├── static
  └── views
```

**Note:** There is a special folder called `/static`, all your static resources should be placed in this folder, and you can construct it as you like. After execute **grunt webapp**, all the resource in the folder will be compiled and copied to web root:

* Scss and css files will be placed in "webapp/web/build/{module}/css" folder
* Coffee and js files will be placed in "webapp/web/build/{module}/js" folder
* png, jpg and gif files will be placed in "webapp/web/build/{module}/images" folder
* eot, svg, ttf and woff files will be placed in "webapp/web/build/{module}/images" folder

**Note:** You can access them with "http://{domain}/webapp/build/{module}/[css|js|images|fonts]/file"

### Routing rule for payment mobile page
We have configured a common routing rule for payment, If you have the function of payment, You must follow this routing rule:
```sh
'webapp/common/pay/<module:[\w\/-]+>' => '<module>/<module>/pay'
```

If the payment mobile page name is "index", folder structure looks like this:

```sh
webapp
  ├── static
  │     ├── coffee
  │     │      └── payIndex.coffee // pay{pageName}.coffee
  │     └── scss
  │           └── pay
  │                 └── index // folder is page name
  │                       └── index.scss
  └── views
        └── {module}
              └── pay
                    └── index.php // {pageName}.php
```

You must add action into "modules/{module}/webapp/controllers/{module}Controller.php" file, the action looks like [this](http://git.augmentum.com.cn/scrm/omnisocials-module-demo/blob/master/webapp/controllers/DemoController.php):

```sh
public function actionPay($page = 'index')
{
    return $this->renderPage($page, false);
}
```


# Backend guide

All the backend modules are located in the folder `/backend`. To get more information about yii2.0 module, you can refer [here](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html)

## Folder structure

```sh
backend
  ├── config
  │      └── main.php
  ├── controllers
  │      └── BaseController.php
  ├── job
  ├── events
  ├── models
  ├── message
  │      └── en_us
  │      └── zh_cn
  ├── Install.php
  ├── Module.php
```

**Notice:** All the menus rendered in portal pages (on top nav and left nav) are configured in this file

* **main.php** The configuration information of the modules, including below
  * **name** The module name
  * **order** When the module is put in the extra menu, this is the order in the list
  * **isInTopNav** whether its menu is on the top navigation
  * **stateUrl** The url is the entry link of module on the top navigation
  * **menusConfig** The menu the module will be put, and the menus the module has. The module menu item has four properties: order/title/name/state, order is used when menus sort; title is i18n key, it will be translated in frontend, name is the menu item name, the menu item icon will be related with this property; state is the frontend state of the router)
* **job** The folder is used to put the jobs related to this module. You can refer to the seed project for the basic job file structure
* **events** The folder is used to contains the classes which can receive third party and system internal events.
* **models** This folder is used to put models belong to this module.
* **message** This folder is used to put i18n files belong to this module.
* **BaseController.php** The other controller of the module will extend this base controller, the common method can defined in this controller, and you can also define the check auth rules in this controller.
* **Install.php** This is the installation class file, it extends backend\components\BaseInstall. When the module is activated successfully, the run method of Install class will be invoked, system will log the processing of install, so you should invoke the parent run method in your run method. You can do your things in the run method, as create collections, insert datas and so on. Below is the code example.

```sh
    public function run($accountId)
    {
        parent::run($accountId);
    }
```

* **Module.php** This is the module class file. If you want to do something when the module init, you can put the code in the init method.

## Build tools

When you create a new module which is public, you need to run the console command to add the module to the account `availableExtMods` field, and then the module will be displayed in the extension list view.

```sh
cd <<repo.path>>/src
./yii module/scan
```

## Expose module API

We provide a API proxy for all the module API with cookie validation. All the API implemented in your own module can be called with omnisocials official access token way. For example, you create an API in `/backend/controllers` folder called `MemeberController` and add a action called `actionIndex` for member module. This API can be called with cookie after login our portal system freely. At the same time, This API can be called with our public API with access_token. The mapping relation is show below:

**{domain}/modules/moduleName/controllerName/actionName -> /api/moduleName/controllerName/actionName (Yii)**

```sh
http://0.0.0.0:9091/modules/member/member/index?access_token=xxxxx
>> mappping to
http://0.0.0.0:8080/api/member/member/index (with cookie)
```

There are two environment:

1. Stage: https://sandbox-api.quncrm.com/ -> https://staging.quncrm.com/ (Yii)
2. Production:  https://api.quncrm.com ->  https://www.quncrm.com (Yii)

**Tip:** All the validation is transparent for module API, account id can be got freely in module controller with `$this-getAccountId()`, you can not use other method (like `Token::getAccountId()`) to get account Id.

## Translating module messages

```sh
├── config
│      └── main.php
├── controllers
│      └── BaseController.php
├── messages
│      ├── zh_cn
│          ├── validation.php
│          └── form.php
│      ├── en_us
│          ├── validation.php
│          └── form.php
├── Install.php
├── Module.php
```

If you want to translate the messages for a module and avoid using a single translation file for all the messages, you can do it like the following:

Module.php

```php
<?php

namespace backend\modules\member;

use Yii;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'backend\modules\member\controllers';

    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        Yii::$app->i18n->translations['modules/member/*'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@backend/modules/member/messages',
            'fileMap' => [
                'modules/member/validation' => 'validation.php',
                'modules/member/form' => 'form.php',
                ...
            ],
        ];
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('modules/member/' . $category, $message, $params, $language);
    }

}
```

In the example above we are using wildcard for matching and then filtering each category per needed file. Instead of using fileMap, you can simply use the convention of the category mapping to the same named file. Now you can use Module::t('validation', 'your custom validation message') or Module::t('form', 'some form label') directly.

## Use module's components

If the init() method contains a lot of code initializing the module's properties, you may also save them in terms of a configuration and load it with the following code in init():

```php
public function init()
{
    parent::init();
    // initialize the module with the configuration loaded from config.php
    \Yii::configure($this, require(__DIR__ . '/config/config.php'));
}
```
If you have the different config files in dev,stage or prod environments, we provide the YII_ENV constant. So you can do like below:
```php
public function init()
{
    parent::init();
    // initialize the module with the configuration loaded from config.php
    switch (YII_ENV) {
        case 'dev':
            $config = require(__DIR__ . '/config/config-dev.php');
            break;
        case 'stage':
            $config = require(__DIR__ . '/config/config-stage.php');
            break;
        case 'prod':
            $config = require(__DIR__ . '/config/config-prod.php');
            break;
    }
    Yii::configure($this, $config);
}
```

where the configuration file config.php may contain the following content, similar to that in an application configuration.

```php
<?php

/**
 * The Import Resume Source Constant
 */
define('CONSTANT_RECRUITING_IMPORT_RESUME_DAJIE', 'dajie');
define('CONSTANT_RECRUITING_IMPORT_RESUME_LIEPIN', 'liepin');
define('CONSTANT_RECRUITING_IMPORT_RESUME_LAGOU', 'lagou');
define('CONSTANT_RECRUITING_IMPORT_RESUME_51JOB', '51job');

return [
    'components' => [
        // list of component configurations
        'curl' => [
            'class' => '\backend\modules\member\components\Curl',
            'options' => [
                CURLOPT_MAXREDIRS => 1,
            ],
        ],
    ],
    'params' => [
        // list of parameters
    ],
];
```

Use like the followings:

```php
\Yii::$app->getModule('member')->curl->buildUrl($article->url, ['date' => $date]);
```


## Resque job

Jobs have setUp and tearDown methods. If a setUp method is defined, it will be called before the perform method is run. The tearDown method, if defined, will be called after the job finishes.

```php
namespace backend\modules\member\job;

use backend\components\resque\SchedulerJob;

class MyJob extends SchedulerJob
{
    public function setUp()
    {
        parent::setUp();
        // ... Set up environment for your job
    }

    public function perform()
    {
        // .. Run job
    }

    public function tearDown()
    {
        parent::tearDown();
        // ... Remove environment for this job
    }
}

```

Create job

```php
public function create($className, $args = [], $executeTime = null, $interval = null)
```

```
Parameters
$className: string job name, must have namespace
$args: array job args
$executeTime: int(UNIX timestam) job execute time
$interval: int interval time, seconds
```

Create a job run at now, job class must extends "backend\components\resque\BaseJob"

```php
Yii::$app->job->create('backend\modules\member\job\StatsMemberDaily', $args);
```

Create a job run at 2015-08-24 13:46:18, job class must extends "backend\components\resque\SchedulerJob"

```php
Yii::$app->job->create('backend\modules\member\job\StatsMemberDaily', $args, strtotime('2015-08-24 13:46:18'));
```

Create a job run as cronjob, run at 1:00 every day, job class must extends "backend\components\resque\SchedulerJob", cronjob can not repetitive create

```php
Yii::$app->job->create('backend\modules\member\job\StatsMemberDaily', $args, strtotime('2015-08-23 1:00:00'), 24*60*60);
```
### Schedule job

Each job should be defined in its own module, and include a 'setScheduleJob' method in 'Module.php'.

params:'class','interval','executeAt' must be defined.if your job need other params,you can pass your params in param 'args'.otherwise you can not to define args
```php
  public static function setScheduleJob()
    {
        return [
            [
                'class' => 'backend\modules\member\job\MessageMemberExpired',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00',
                'args' => [],
            ],
            [
                'class' => 'backend\modules\member\job\Birthday',
                'interval' => TimeUtil::SECONDS_OF_DAY,
                'executeAt' => 'Y-m-d 01:00:00',
            ]
        ];
    }
```
## Handle events

All the classes which can receive third party and system internal events are placed under `backend\events` folder.

### Weconnect events

For example, the weconnect team will push the wechat user message to our system when user send message, So you can create `WeconnectEvent.php` in this foleder, and the `handle` method in this file, and do your things in the handle method. The `WeconnectEvent` class must inherit from `BaseEvent`, and run the `parent::handle($data)` method in its `handle` method.

```php
<?php
namespace backend\modules\helpdesk\events;

use backend\components\BaseEvent;

class WeconnectEvent extends BaseEvent
{
    public function handle($data)
    {
        parent::handle($data);
        //Your code below
    }
}
```

Then you need to subscribe event type and data type for channel. `msg` and `event` are the event types. Below is the data types belong to the related event type:

* **msg:** 'text', 'image', 'voice', 'video', 'shortvideo', 'location', 'link'
* **event:** 'click', 'view', 'scan', 'subscribe', 'unsubscribe', 'mention', 'location', 'enter'

Detailed information about webhook event data structure can be found [here](http://developer.quncrm.com/webhook/). All the message and event constants are defined in `WebhookEvent` class.

When you want to subscribe, declare needed namespace  `use backend\models\WebhookEvent;`, then subscribe `msg` or `event` type with the code below:

```php
Yii::$app->webhookEvent->subscribeMsg('helpdesk', '561db1d72736e745058b4568', WebhookEvent::DATA_TYPE_MSG_TEXT, strtotime("2015-10-14 12:12:34"), strtotime("2015-10-16 12:12:34"));
```

or event type

```php
Yii::$app->webhookEvent->subscribeEvent('helpdesk', '561db1d72736e745058b4568', WebhookEvent::DATA_TYPE_EVENT_VIEW, strtotime("2015-10-14 12:12:34"), strtotime("2015-10-16 12:12:34"));
```

**Note:** If the `endAt` parameter is not specified, you should unsubscribe it manually.

When you want to unsubscribe a specific channel event of a module just need the code below:

```php
$ok = Yii::$app->webhookEvent->unsubscribeMsg('helpdesk', '561db1d72736e745058b4568', WebhookEvent::DATA_TYPE_EVENT_VIEW);
```

or event type

```php
$ok = Yii::$app->webhookEvent->unsubscribeEvent('helpdesk', '561db1d72736e745058b4568', WebhookEvent::DATA_TYPE_EVENT_VIEW);
```

When you needs more accurate control for time, pass `startAt` parameter, so that only some of the channel events for the module are unsubscribed.

```php
$ok = Yii::$app->webhookEvent->unsubscribeMsg('helpdesk', '561db1d72736e745058b4568', WebhookEvent::DATA_TYPE_EVENT_VIEW, strtotime("2015-10-14 12:12:34"));
```
or event type

```php
$ok = Yii::$app->webhookEvent->unsubscribeEvent('helpdesk', '561db1d72736e745058b4568', WebhookEvent::DATA_TYPE_EVENT_VIEW, strtotime("2015-10-14 12:12:34"));
```

### Portal events

For example, our portal system will execute your handler when tag is renamed, So you can create `PortalEvent.php` in this foleder with `handle` method, and do your things in the handle method. The `WeconnectEvent` class must inherit from `BaseEvent`, and run the `parent::handle($data)` method in its `handle` method.

```php
<?php
namespace backend\modules\helpdesk\events;

use backend\components\BaseEvent;

class PortalEvent extends BaseEvent
{
    public function handle($data)
    {
        parent::handle($data);
        //Your code below
    }
}
```

You don't need to subsribe as did for weconnect event, the received data format can be refered below (`account_id` field of passed data is the mongo ID which you can use it directly for mongo query):

#### Tag deleted events

```php
[
    "type"=> "tag_deleted",
    "account_id"=> new MongoId("54a1461eb8137480048b4567"),
    "name"=> "test"
]
```

#### Tag renamed events

```php
[
    "type"=> "tag_renamed",
    "account_id"=> new MongoId("54a1461eb8137480048b4567"),
    "old_name"=> "haha",
    "new_name"=> "hahad"
]
```

# Channel menu guide

When you want to add a item in menu extension, please follow this guide.

Add a channelMenu.php file in `/backend/config` folder.

The content of channelMenu.php file looks like this:

```php
<?php
return [
    'name' => 'member',
    'title' => 'channel_menu_member',
    'introductions' => ['channel_menu_member_tip1', 'channel_menu_member_tip2', 'channel_menu_member_tip3', 'channel_menu_member_tip4'],
    'keycode' => 'USER_CENTER', // default is 'MEMBER'
    'type' => 'VIEW', // 'VIEW' 'CLICK'
    'msgType' => 'URL', // 'URL' / 'TEXT' or 'NEWS'
    'content' => DOMAIN . 'api/mobile/member?appId={{appId}}&channelId={{channelId}}',
    'dataCallback' => ['\backend\modules\member\util\Conf', 'getContentInfo'],
    'isEnabled' => true,
];
```

  * **name** The module name.
  * **title** The i18n key displayed on menu extension title, the key must be added in locate-en_us.json and locate-zh_cn.json files.
  * **introductions** The module introduction keys displayed on menu extension item, the keys must be added in locate-en_us.json and locate-zh_cn.json files.
  * **keycode** The unique module keycode, the default value is module name in upper case.
  * **type** The menu item type, 'VIEW' or 'CLICK'.
  * **msgType** The reply message type of the menu item, 'URL'  'TEXT' or 'NEWS'. If msgType is 'URL', the menu type should be 'VIEW', If msgType is 'TEXT' or 'NEWS', the menu type should be 'CLICK'.
  * **content** The menu reply message, its parameters can be specified as placeholder (eg: `{{appId}}`) , and use dataCallback to return the parameters.
  * **dataCallback** It is a function, it is only useful when you need to add parameters in the content. The function looks like this:

  ```php
    public static function getContentInfo($channelId, $accountId)
    {
        // do what you want to do
        return ['appId' => $appId, 'channelId' => $channelId];
    }
  ```

  * **isEnabled** Two types are supported: boolean, function. If it is a function, the return value of it should be boolean (true or false).


## Wechat Jssdk integration

- See the code in: `src/backend/components/wechat/WechatSdk.php`  for more detail

- For the follow two api, the channelId is **not required**, if not, use the default, see configuration in
`src/common/config/main.php`  , the `CHANNEL_ID` for `wechatSdk`.

- The code **JsSDK is expired**.

### Get the signPackage

```php
$sdk = Yii::$app->wechatSdk;
$sdk->refererUrl = $sdk->refererDomain . substr(Yii::$app->request->getUrl(), 1);
$signPackage = Json::encode($sdk->getSignPackage($channelId));
```

### Get the access token

```php
$sdk = Yii::$app->wechatSdk;
$accessToken = $sdk->getWxAccessToken($channelId);
```
## Ensure mongoDB index
### configure params
Each index should be defined in its own module, and include a 'setCollectionIndex' method in 'Module.php'.

```php
  public static function setCollectionIndex()
    {
        return [
            [
                'class' => '\backend\modules\member\models\Member',
                'indexes' => [
                    [
                        'keys' => ['cardNumber'],
                        'options' => ['unique' => true],
                    ],
                    [
                        'keys' => ['accountId', 'isDeleted', 'createdAt'],
                        'options' => [],
                    ],
                    [
                        'keys' => ['cardId', 'isDeleted'],
                        'options' => []
                    ]
                ]
            ],
            [
                'class' => '\backend\modules\member\models\ScoreHistory',
                'indexes' => [
                    [
                        'keys' => ['memberId' => 1, 'createdAt' => -1],
                        'options' => [],
                    ]
                ]
            ]
        ];
    }
```

### Use it in command
Use like the following:
```
./yii management/index
```

