# Guideline

Describe all the guidelines for mobile development.

## Tech stack

* Build Tool: [grunt](http://www.gruntjs.net/), [grunt riot](https://github.com/ariesjia/grunt-riot)
* Component Framework: [riot](http://riotjs.com/), [riot control](https://github.com/jimsparkman/RiotControl)
* CSS Preprocessor: [SCSS](http://sass-lang.com/)
* Javasript Preprocessor(Only in components): [Babel](https://babeljs.io/)

## Folder Structure

```bash
webapp
  ├── components             # Shared componnets
  │   ├── header             # Component folder
  │   │   ├── header.scss    # Component style file
  │   │   └── header.tag     # Component template and logic
  │   ├── mixins.scss        # Shared mixins
  │   └── theme.scss         # All the color related definition defined here
  ├── reservation            # Module name    
  │   └── list               # Page folder
  │       ├── index.scss     # Page specific styles
  │       └── index.coffee   # Entry coffee file for page
  └── README.md
```

## Component Development

### SCSS convention

We use [BEM](http://csswizardry.com/2013/01/mindbemding-getting-your-head-round-bem-syntax/) to name components classes, every component should has a class wrapper with `c-component` as prefix, and be placed under `mobile\components` foler. Example:

```scss
.c-header {
    /* Base stuff */
    &__child {
        /* Sub-element of block */
    }
    &--modifier {
        /* Variation of block */
    }
}
```

### Tag development

All the javascript code should be wrapped with script tag, sample tag below:

```text
<header class="c-header">

  <!-- Template here-->
  <div class="c-header__return" onclick={ back }></div>
  <span>{ opts.title }</span>

  <script>
    /*JS(ES6) code here*/
  </script>

</header>
```

## Page Development

### File structure

Create your page folder in module folder, sample structure below:

```sh
reservation/
  ├── app.scss
  └── list
       ├── index.coffee # Used to mount components
       └── index.scss   # Used to store page related styles
```

Add a `app.scss` as the module style entry file, all the page styles are imported in the file. Sample `app.scss` file below:

```scss
//Base files
@import '../components/mixins';
@import '../components/theme';

//Components below
@import '../components/header/header';

//Pages below
@import 'list/index';

```

Add a page importing directive shown as `Pages below`, add needed component importing directive shown as `Components below`.

**Note:** Page related styles defined in `index.scss` file of page folder should be wrapped with `pageName-page` class as namespace.

### Use component

If you need add components on the page, you should add imports in the `app.scss` file. Override the component styles or define page specific styles in `index.scss` file.As all the styles for the module will be merge as one `app.css` file. All the page specific style rules should be wrapped with page name class, exmpale below:

```scss
.list-page {
  .title {
    color: green;
  }
}
```

**Note:** We use [flexible](https://github.com/amfe/lib-flexible) to adjust different page viewports, please follow its routine to transform `px` to `rem`.

Add the tags and script injection that you need in `src/frontend/views/mobile/{moduleName}/{pageName}.php` file, example below:

```php
<header></header>
<script src="/build/webapp/components/header/header.js"></script>
```

Mount the components and send AJAX to update its value in `src/static/webapp/{moduleName}/{pageName}/index.coffee` file, example below:

```coffee
riot.mount('header', {title: 'test'})
```

### Access URL

All the mobile pages using riot components follow the URL pattern

```sh
protocol://{domain}/mobile/{moduleName}/{pageName}
```

Add debug parameter will be helpful for injecting needed javascript files, example:

```sh
protocol://{domain}/mobile/{moduleName}/{pageName}?debug=1
```
