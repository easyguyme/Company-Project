# Page Style Improvement

UX have released a story [#4546](http://git.augmentum.com.cn/scrm/aug-marketing/issues/4546) to improve the page style. To accomplish this story, we've finished the common modification, that is refined the style of wmBreadcrumb directive, navigation bar, vertical navigaion bar and the padding of content. Now there is one task left for you:

## Using wmBreadcrumb for title and breadcrumb

If you are already use it, then nothing need to be changed. Otherwise, in order to add title, you should add one line html code into your page.

```HTML
<div wm-breadcrumb="pageName.breadcrumb"></div>
```

Then, add an object into the controller of your page

```coffeescript
$scope.breadcrumb = [
  'some_i18n_key'
]
```

If the page is a secondary level page, then the `breadcrumb` object may looks like:

```coffeescript
$scope.breadcrumb = [
  text: 'some_i18n_key'
  href: '/path/to/page'
,
  'another_i18n_key'
]
```

Maybe there is a button in your `breadcrumb`, you can put it in like:

```HTML
<div wm-breadcrumb="pageName.breadcrumb">
  <button class="button_class" ng-click="pageName.buttonAction()">{{'button_text' | translate}}</button>
</div>
```
