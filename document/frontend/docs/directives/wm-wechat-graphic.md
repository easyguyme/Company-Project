## wmWechatGraphic
The `wmWechatGraphic` directive allows you to use single-graphic and multi-graphic of these two forms to display the graphic.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-wechat-graphic
    graphic=""
    [display-options=""]
    [linkable=""]
    [default=""]
    [selected-index=""]
    [is-edit=""]
    [article-select=""]
    [article-delete=""]
    [graphic-delete=""]
    [graphic-edit=""]>
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
graphic                      | `expression` | []    | Set the data of graphic to data-bind to.
displayOptions (*optional*)  | `boolean`    | false | Set the graphic whether display the options button include edit and delete.
linkable (*optional*)        | `boolean`    | false | Set the article whether can link to the detail page.
default (*optional*)         | `expression` | []    | Set the default data if can not get the data of graphic that user set.
selectedIndex (*optional*)   | `number`     |       | Set the index of article when clicking the edit button in the edit page.
isEdit (*optional*)          | `boolean`    | false | Set the status of graphic whether be edited.
articleSelect (*optional*)   | `expression` |       | Angular expression to be executed when the edit button is clicked to edit the article in the edit page.
articleDelete (*optional*)   | `expression` |       | Angular expression to be executed when the delete button is clicked to delete the article in the edit page.
graphicDelete (*optional*)   | `expression` |       | Angular expression to be executed when the delete button is clicked to delete the graphic.
graphicEdit (*optional*)     | `expression` |       | Angular expression to be executed when the edit button is clicked to edit the graphic.

---

## Example
html
```
<div wm-wechat-graphic graphic="graphics.graphic"
  display-options="true"
  graphic-delete="graphics.deleteGraphic"
  graphic-edit="graphics.editGraphic"></div>
```

coffee
```
graphics =
  graphic:
    articles:
      $$hashKey: "026"
      content: "<p>11</p>"
      contentUrl: "http://vincenthou.qiniudn.com/8b44d08b-f979-2595-5a19-afa1111baf6d.html"
      description: "11"
      picUrl: "http://wm.com/images/content/default.png"
      sourceUrl: ""
      title: "111"
    createdAt: "2015-09-07"
    id: "55ecff5b475df4773d8b4568"
    usedCount: 0

  deleteGraphic: (id) ->
    console.log 'delete graphic'
  editGraphic: (id) ->
    console.log 'edit graphic'
```
