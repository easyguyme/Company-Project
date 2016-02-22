## wmSlider
The `wmSlider` directive allows you to select value range by mouse or keyboard.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-slider
    ng-model=""
    [max=""]
    [min=""]
    [step=""]
    [range=""]
    [sliderId=""]
    [ticks=""]
    [ticks-labels=""]
    [disable=""]
    [has-handler-num=""]
    [formatter=""]
    [onSlideStart=""]
    [onSlideStop=""]
    [onSlide=""]
    [onChange=""]>
</ANY>
```
as element
```
<wm-slider
    ng-model=""
    [max=""]
    [min=""]
    [step=""]
    [range=""]
    [sliderId=""]
    [ticks=""]
    [ticks-labels=""]
    [disable=""]
    [has-handler-num=""]
    [formatter=""]
    [onSlideStart=""]
    [onSlideStop=""]
    [onSlide=""]
    [onChange=""]>
</wm-slider>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                    | `string`     | |Assignable angular expression to data-bind to.
max (*optional*)           | `number`     | Number.MAX_VALUE | Maximum possible value, default value `Number.MAX_VALUE`.
min (*optional*)           | `number`     | 0     | Minimum possible value, default value is 0.
step (*optional*)          | `number`     | 1     | This slider increase value when you use mouse or keyboard
range (*optional*)         | `boolean`    | false | Make range slider. Optional if initial value is an array. If initial value is scalar, max will be used for second value.
sliderId (*optional*)      | `string`     | ''    | Set the id of the slider element when it's created
ticks (*optional*)         | `expression` | []    | Used to define the values of ticks. Tick marks are indicators to denote special values in the range. This option overwrites min and max options.
ticksLabels (*optional*)   | `expression` | []    | Defines the labels below the tick marks. Accepts HTML input.
disable (*optional*)       | `boolean`    | false | Set the slider disabled
hasHandlerNum (*optional*) | `boolean`    | false | Whether display the number below the handle
formatter (*optional*)     | `expression` | returns the plain value | formatter callback. Return the value wanted to be displayed in the tooltip.
onSlideStart (*optional*)  | `expression` | | Angular expression to be executed when dragging starts.
onSliderStop (*optional*)  | `expression` | | Angular expression to be executed when the dragging stops or has been clicked on.
onSlide (*optional*)       | `expression` | | Angular expression to be executed when the slider is dragged.
onChange (*optional*)      | `expression` | | Angular expression to be executed when the ngModel value has changed.

---

## Example
html
```
<div wm-slider ng-model="sliderOptions.usedScores"
  min="sliderOptions.min"
  max="sliderOptions.max"
  step="sliderOptions.step"
  range="sliderOptions.range"
  ticks="sliderOptions.ticks"
  has-handler-num="true"
  on-change="sliderOptions.changeSlide()"
  ticks-labels="sliderOptions.ticksLabels"></div>
```

coffee
```
sliderOptions =
  min: 0
  max: 1600
  step: 1
  range: true
  usedScores: [0, 1600]
  ticks: [0, 200, 400, 600, 800, 1000, 1200, 1400, 1600]
  ticksLabels: ['0', '200', '400', '600', '800', '1000', '1200', '1400', 'above']
  changeSlide: ->
    console.log 'change slide'
```