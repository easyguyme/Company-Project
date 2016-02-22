# Setup System

## Guide For Frontend Development

**Add livereload script(DO NOT COMMIT) in HTML head tag in 'src/frontend/views/layouts/main.php' file so that when you update code, the browser will automatically refresh.**

```php
<?php echo '<script src="http://localhost:35729/livereload.js"></script>'; ?>
```

**Run grunt command for livereload your code in browser**

```sh
grunt # grunt command includes 'grunt build' and 'grunt dev' commands and it can watch your code changing.
```

### Add HTML partial in the specified module

Use bootstrap useful class and HTML tag to finish your static page in the user.html file

**Tip:**
* Split page into several components and reuse UI components written before in our project as much as possible. If there is a new component to be added, fulfill it with SASS and put it under components folder. Component's class name should be common and it's clear for others to understand.
* Use bootstrap SASS mixins, utils, components as often as possiable.
* The common visual value, such as color, background color, font size should be defined in the global variables.
* Use nested HTML tag as less as possible.
* Use more meaningful tag to finish your HTML, header, section, nav and so on are better than div tag.

Take breadcrumb component as example:
```html
<ol class="breadcrumb">
  <li>
    <a href="/product/product" translate="product_title"></a>
    <span translate="view_product"></span>
  </li>
</ol>
```

```sass
$breadcrumb-color: #eee;
.breadcrumb {
  color: $breadcrumb-color;
  font-size: 16px;
  line-height: initial;

  a {
    color: $breadcrumb-color;
  }

  >li+li:before {
    padding: 0 8px;
  }
}
```
**Note:** You can use html structure of UI component in your page template and do not need to write extra style.

### Add SASS file in the style folder

**Tip:**
* Use bootstrap mixins if possible, if it does not fit your needs, create it in your scss file in module styles folder. The page's style should be wrapped with 'moduleName-pageName' or other class belonging to you so that your style will not conflict with others.

**Advanced Tip:**
* Common mixins should be defined in the specific mixins folder, import it in index.scss if it is new one.
* Boostrap theme related variables defined in the theme file (path: src/static/portal/scss/bootstrap/bootstrap-theme.scss)
* Customized global variables defined in the specific variable file
* Customized global components defined in the components folder

### Add images in the module image folder

Just put the images used in the images folder of your module. Run `grunt cbuild`, newly added images can be accessed with path `{protocol}://{domain}/images/{moduleName}/xxx.png`, such as `http:wm.com/images/management/xxx.png`.

**Notice: Reuse the existing images in the folder, don't add redundant ones.**
Access them in the SCSS file with 'url(images)'

### Add angular controller for the page

**We only suppor one controller for one page now**
There are some conventions here.

Add page state in the config.coffee of the module, take **/management/user** as an example
```sh
states: [
  'management-user'
]
```
Add the controller in controller folder, file name should be defined as userCtrl which map to the state name 'user'.
You can create subfolder in the controllers folder, state name map to the path.
Take the userCtrl below as an example:

```sh
modules/
└── management
    ├── config.json
    ├── controllers
    │    ├── userCtrl.coffee
    │    └── view
    │         └── userCtrl.coffee
    ├── index.scss
    ├── json
    │    └── user.json
    ├── partials
    │    ├──  user.html
    │    └── view
    │         └── user.html
    └── styles
       └── user.scss
```
state should be defined as
```sh
states: [
  'management-user-list',
  'management-view-user-{id}' # URL path is '/management/view/user/12'
]
```

As for controller defined in the UserCtrl.coffee, name should follow the pattern `wm.ctrl.moduleName.controllerName`. Still take /management/user as example, the template file structure below:

```coffee
define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.user', [
    'restService'
    (restService) ->
      vm = this
      restService.get config.resources.user, (data) ->
        vm.name = data.name
  ]

```
Commonly, the restService is injected in the controller to send RESTful request for the page.
**Notice:** As we support lazyloading for controllers, use **registerController** to define your controller.
Directives and services for the page can be define in the same file, but if it is a common one, define it in core folder (path: /src/static/portal/modules/core)

**Tip:**
* Use ui-bootstrap directives as often as possible
* Use official filters as often as possible
* Use obvious declaration for injection
* Use vm (view model) for binding your scope model (vm = this), see the example above, use 'controllerName.value' in the HTML partial, 'user.name' for the above example, $scope is not needed to be injected. [Detail reason](http://greengerong.github.io/blog/2013/12/24/angular-controller-as-syntax-vs-scope/)
* Refer [Angular Coding Style](https://github.com/johnpapa/angular-styleguide) **(IMPORTANT)**

### Local testing without backend

Well, when you need to get data with RESTful API, you can mock response data in userCtrl file according to api document.

```coffee
define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.user', [
    ->
      vm = this
      // Mock data accroding to API document structure
      data =
        name: 'vincent'
        email: '1234567@qq.com'

      vm.name = data.name
  ]
```

### Add menu icons

Create a `/nav` folder in `/static/images` folder and put images in it (example: /static/images/nav). Follow the naming convention below to add needed images.

* Default state icon: `xxx_default.png`
* Hover state icon: `xxx_hover.png`
* Selected state icon: `xxx_selected.png`

`xxx` is the name field defined in `menusConfig` list of `backend/config/main.php` file.

```php
<?php
return [
    'name' => 'member',
    'namezh' => '会员',
    'isInTopNav' => true, // whether at the top of the page or not
    'isCore' => false, // whether the core module or not
    'order' => 2, // order of top navs
    'menusConfig' => [
        'member' => [
            [
                'order' => 1, // order of the nav groups
                'title' => 'member_management',// i18n key of nav item
                'name' => 'member', // map to icon name such as 'member_default', 'member_hover', 'member_selected'
                'state' => 'member-member' // frontend state
            ]
        ]
    ]
];
```

### Form validation

wmFormvaildation directive is used to apply easy form validation.
**Common attributes tips:**
* 'form-control' class is required for input tag.
* 'form-group' class is required for wrapping form field group.

#### Required field validation

Template as below

```html
<form ng-submit="submit()">
  <div class="form-group">
    <label class="col-md-2" for="name">Required field</label>
    <div class="col-md-8">
      <input id="name" class="form-control" required/>
    </div>
  </div>
  <input type="submit">
</form>
```

'form-control' class is required here, and every line of form should be wrapped by 'form-group' class as the standard bootstrap required.
For required field validation, only 'required' attribute is needed. 'label' tag is also needed for indicating the required field name.

**If you want to add a star before a label, you can add 'required-field' class. If you don't need a star but need a required input, you can add 'without-star' attribute at the input tag.**

```html
<form ng-submit="submit()">
  <div class="form-group">
    <label class="col-md-2 required-field" for="name">Label field with a star</label>
    <label class="col-md-2" for="name">Required field but no star</label>
    <div class="col-md-8">
      <input id="name" class="form-control" required without-star/>
    </div>
  </div>
  <input type="submit">
</form>
```

#### Email type field validation

Template as below

```html
<form ng-submit="submit()">
  <div class="form-group">
    <label class="col-md-2" for="">Email Field</label>
    <div class="col-md-8">
      <input type="email" class="form-control" wm-email/>
    </div>
  </div>
  <input type="submit">
</form>
```

Like the required field pattern above, 'form-control' and 'form-group' class are needed.
In Addition, the input should be specified with the email type ( **type="email"** ), and add wm-email attribute.

#### URL type field validation

Template as below

```html
<form ng-submit="submit()">
  <div class="form-group">
    <label class="col-md-2" for="">URL Field</label>
    <div class="col-md-8">
      <input type="url" class="form-control" wm-url/>
    </div>
  </div>
  <input type="submit">
</form>
```

Like the required field pattern above, 'form-control' and 'form-group' class are needed.
In Addition, the input should be specified with the url type ( **type="url"** ), and add wm-url attribute.

#### Customized field validation

If all the standard validation does not fit your needs, you can specify your own's

Template as below

```html
<form>
  <div class="form-group">
    <label class="col-md-2" for="">Customized field</label>
    <div class="col-md-8">
      <input id="my-number" class="form-control" wm-validate="user.checkMyNumber" form-tip="{{user.formTip|translate}}"/>
    </div>
  </div>
  <input type="submit"/>
</form>
```

Like the required field pattern above, 'form-control' and 'form-group' class are needed.
Only difference here is that the 'wm-validate' is assigned with validation function defined in scope. If the validation function return empty string, there is no error tip when submitting form data. Return the form tip key if validation failed. In the above case, assign the validation function checkMyNumber, **initial form tip should be assigned to "form-tip" attribute** the function looks like the code below:

```coffee
vm.checkMyNumber = (value)->
  formTip = ''
  if isEmpty(value)
    formTip = 'empty_string_tip'
  else if isNaN(value)
    formTip = 'not_number_tip'
  formTip
```

In addition in your ng-submit callback should check again:

```coffee
vm.submit = ()->
  # Check if the validaion message is empty
  if !vm.checkMyNumber() # Empty message
    restService.post url, params, ()->
       # Update successfully
```


### I18n in modules

#### Convention

*  **Refer to the [i18n guide](http://git.augmentum.com.cn/scrm/aug-marketing/wikis/i18n), and pick the translations in it**
* Use lowercase for i18n keys and use underline to split words, for example 'upgrade_management'.
* Core i18n keys defined in the core module in the modules folder, other i18n files defined in related module folder.
* i18n files in the module folder should use module name as key prefix, for example 'management_user_count'. Core i18n in the modules/core folder does not need prefix, for example 'user' and 'channels'

#### Place your i18n files in modules

Add i18n folder in the module folder if it does not exist, take i18n for management module as an example:

```sh
mkdir src/static/portal/modules/management/i18n
```

Add i18n files in the folder
```sh
cd src/static/portal/modules/management/i18n
touch locate-en_us.json
touch locate-zh_cn.json
```

Add Empty JSON in the files ( **Add {}** )

#### Run grunt dev in the command line

It merges all the i18n files in modules when you editing i18n files in modules, and inform you of unmatched i18n keys in the i18n files.If you can not see the changed i18n in the brower, check the error information in the console.

```sh
>> Please add keys "channels" in modules/site/i18n/locate-en_us.json
>> Please add keys "site" in modules/site/i18n/locate-zh_cn.json
```

#### Add i18n with CLI tools

##### Adding tool
To get rid of mistakes, you can add i18n without opening two i18n files and do dummy copying.
Just run the command below (**Kill the process for grunt dev**)
```sh
 grunt addi18n:channel:test:Test:测试
```
**Pattern: addi18n:moduleName:keyName:EnglishValue:ChineseValue**

Example
```sh
grunt addi18n:content:content_component_delete_success:"Delete component successfully":"删除组件成功"
```

##### Stripping tool
It is used to remove duplicated keys for the i18n files of a module.
```sh
grunt stripi18n:channel
```

##### Diffing tool
It is used to diff the missing keys for the i18n files of a module(**Duplicated keys can not be removed, please use stripping tool instead**).
```sh
grunt diffi18n:channel
```
Error occurs when only one key added for one i18n file
```sh
>> Please add keys for module channel [ 'test' ]
```
### Notifications

+ Inject notificationService into your code and call the 5 functions accordingly.

```CoffeeScript
 mod.factory "yourCtrl", [
    "notificationService"
    (notificationService) ->
         notificationService.success "success_message_i18n_key"
         notificationService.info "info_message_i18n_key"
         notificationService.warning "warning_message_i18n_key"
         notificationService.error "error_message_i18n_key
         notificationService.confirm $event, {title: "delete_title_i18n_key", submitCallback: submitCallback, cancelCallback: cancelCallback, params: params}
```   

+ If the message does not need to be translated, add 'true' for the second parameter:

```CoffeeScript
    notificationService.success "success message", true
```

+ If you want to provide parameters for the message, follow this way:

```CoffeeScript
    values = name: "Devin", email: "devinjin@augmentum.com.cn"
    notificationService.success "Hi! My name is {{name}}, my email is {{email}}.", true, values
```

+ If you want to provide parameters for the message need to translate by language, follow this way:

```CoffeeScript
    values = name: "Devin", email: "devinjin@augmentum.com.cn"
    notificationService.success 'personal_info', false, values
```

```json
{
//locate-en_us
"personal_info": "Hi! My name is {name}, my email is {email}.",
...

//locate-zh_cn
"personal_info": "你好! 我叫{name}, 我的邮箱是{email}.",
}
```

+ The confirm function, $event is required to get the element position which clicked, it's the click $event object of angular. The second parameter is optional. If you provide title parameter, the popup will display it or it will display default value. If you provide submitCallback parameter or cancelCallback parameter, submitCallback will be called when user click "ok" button, cancelCallback will be called when user click "cancel" button. If you provide params parameter, it must be an array, the array will be separately passed to submitCallback and cancelCallback function.

```CoffeeScript
    notificationService.confirm $event,{
      "title": "delete_title_key",
      "submitCallback": deleteSubmitHandler,
      "cancelCallback": deleteCancelHandler,
      "params": [id]
    }
```

### Handle images

Commonly, we upload pictures to qiniu for storing our static images, qiniu provide API (add query string to the image url) for us to handle image processing, detailed reference [here](http://developer.qiniu.com/docs/v6/api/reference/fop/image/).

We support a simple qiniu filter to handle this, the default mode is 1.

* If only no value is passed to the filter like the example below, filter handle it as a square image and default height and width is 30.

**Simple usage:**

```html
<img class="avatar" ng-src="{{user.avatar|qiniu}}"/>
```
This will add '?imageView/1/w/30/h/30' to the url.

* If only one value is passed to the filter like the example below, filter handle it as a square image and default height and width is the value.

**Simple usage:**

```html
<img class="avatar" ng-src="{{user.avatar|qiniu:80}}"/>
```
This will add '?imageView/1/w/80/h/80' to the url.

* If you need to use other mode and specify the width and height for mode 1, use string to do it like the example below.

**Simple usage:**

```html
<img class="avatar" ng-src="{{user.avatar|qiniu:'80,100,2'}}"/>
```
This will add '?imageView/2/w/80/h/100' to the url.

**Note:** Mode 1 will center cut qiniu image and mode 2 will keep complete qiniu image, but both of them will not enlarge the images.

**Tip:** When you don't know the width and height on runtime rendering, use **wm-center-img** instead.

### Reference

* [AngularJS API](http://www.ngnice.com/docs/api)
* [CoffeeScript Guide](http://coffeescript.org/)
* [JS2Coffee Tool Online](http://js2coffee.org/)
* [Get Started With SASS](http://www.ruanyifeng.com/blog/2012/06/sass.html)
* [Bootstrap Resources](http://www.bootcss.com/)
* [Angular Style Guide](https://github.com/johnpapa/angular-styleguide)

# TODO
Write command tool to generate those dummy configuration
