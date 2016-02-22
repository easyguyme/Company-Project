# SASS Guide

## Use bootstrap SASS right

### Use bootstrap.css file when developing

Normally, we run 'grunt dev' command, when developing frontend page.
At this time the page load bootstrap.css in the web folder for better grunt watch performance and you need to do nothing for it.

### Update bootstrap global variables if you need.

When you find the bootstrap theme (color, font-size, shadow and so on) is not suiable for our needs, you need to change the default development configuration for modification.

#### Update the AppAsset.php file

```sh
frontend
├── assets
│   └── AppAsset.php
└── web
    ├── bootstrap.css
    ├── bootstrap.css.map
```

#### See the folder structure above, open AppAsset.php file, comment the bootstrap.css import as the example below shows.

```php
public $css = [
        //'bootstrap.css',
        'build/app.css'
    ];
```

```sh
frontend/web/scss
├── app.scss
├── bootstrap.scss
├── bootstrap-theme.scss
└── core
    ├── _components.scss
    ├── _layout.scss
    ├── mixins
    │   └── _font-size.scss
    └── _variables.scss
```

#### See the folder structure above, Open the app.scss file, uncomment the '@import "boostrap"' line

```scss
@import "core/variables";
@import "bootstrap-theme";

// Core variables and mixins
@import "../../vendor/bower/bootstrap-sass-official/assets/stylesheets/bootstrap/variables";
@import "../../vendor/bower/bootstrap-sass-official/assets/stylesheets/bootstrap/mixins";
//@import "bootstrap";

@import "core/mixins/font-size";
@import "core/layout";
@import "core/components";

@import "modules/management/index.scss";
```

**Tip:** See the configuration above, you can use variables and mixins of bootstrap freely no mater whether load bootstrap.css or not.

#### Redefine bootstrap global variables in the boostrap-theme.scss file

Add bootstrap defined variables and reset there value.

```scss
$brand-primary:         #38C4A9;
$brand-success:         #9bcd55;
$brand-info:            #54afee;
$brand-warning:         #f9ab20;
$brand-danger:          #ff7d73;

$nav-link-hover-bg:     darken($banner-color, 10%);
$border-radius-base:    0;

@font-face {
  font-family: 'segoe-ui';
  src: url('../fonts/segoe-ui.eot');
  src: local('segoe-ui'), url('../fonts/segoe-ui.woff') format('woff'), url('../fonts/segoe-ui.ttf') format('truetype');
}
```

#### Grunt compile your configuration

```sh
grunt compile
```

You can see the result, and then **change to the original css import strategy for better development performance** .