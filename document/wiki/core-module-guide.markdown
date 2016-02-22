## Guide for Core Module Development

### Add HTML partial in the specified module

Take the **/management/user** as an example:
```sh
touch src/static/portal/modules/management/partials/user.html
```
Use bootstrap useful class and HTML tag to finish your static page in the user.html file

### Add SASS file in the style folder

Take the **/management/user** as an example:
```sh
touch src/static/portal/modules/management/styles/user.scss
```
Add import in the index.scss file
```sh
@import 'styles/user.scss'
```

**Notice:**
If the module is the latest, add import sentence in the app.scss
```sh
vi src/static/portal/scss/app.scss
```
Add below line, format `modules/moduleName/index.scss`
```sh
@import "modules/management/index.scss";
```
Add your SASS in the user.scss

**Tip:**
* Use bootstrap mixins if possible, if it does not fit your needs, create it in your scss file in module styles folder. The page's style should be wrapped with `moduleName-pageName` class so that your style will not conflict with others.
**Advanced Tip:**
* Common mixins should be defined in the core/mixins (path: src/static/portal/modules/core/styles/mixins), import it in index.scss (path: src/static/portal/modules/core/index.scss) if it is new one.
* Boostrap theme related variables defined in the theme file (path: src/static/portal/scss/bootstrap/bootstrap-theme.scss)
* Customized global variables defined in the variable file (path: src/static/portal/scss/_variables.scss)
* Customized global components defined in the components file (path: src/static/portal/modules/core/styles/_components.scss)

### Add images in the module image folder

Just put the images used in the images folder of your module(path:/src/static/images/management). Run `grunt cbuild`, newly added images can be accessed with path `{protocol}://{domain}/images/{moduleName}/xxx.png`, such as `http:wm.com/images/management/xxx.png`.

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
Add the controller in controller folder (path: modules/management/controllers), file name should be defined as userCtrl which map to the state name 'user'.
You can create subfolder in the controllers folder, state name map to the path.
Take the userCtrl below as an example:

```sh
modules/
└── management
    ├── config.json
    ├── controllers
    │    ├── userCtrl.coffee
    │    └── view
    │      └── userCtrl.coffee
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

As for controller defined in the UserCtrl.coffee, name should follow the pattern 'wm.ctrl.moduleName.controllerName'. Still take /management/user as example, the template file structure below:

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
