<lr-list>
  <ul class="c-lr-list">
    <li class="c-lr-list__item" each={ items } onclick={ parent.clickFuc }>
      <div class="clearfix">
        <span class="c-lr-list__item__left" style="{ left.style ? left.style : '' }" if={ left }>{ left.content ? left.content : '' }</span>
        <label class="c-lr-list__item__label" style="{ label.style ? label.style : '' }" if={ label }>{ label.content ? label.content : '' }</label>
        <span class="c-lr-list__item__right" style="{ right.style ? right.style : ''}" if={ right }>{ right.content ? right.content : '' }</span>
      </div>
      <div if={ tip } class="c-lr-list__item__illustration">{ tip }</div>
    </li>
  </ul>
  <script>
    var self = this, _init, _packageItems, _isFunction;

    const C_FUNCTION = 'function';
    const LEFT_COLOR = '#6c6c6c';
    const RIGHT_COLOR = '#1e1e1e';
    const ICON_UNIT = 'rem';
    const ICON_PADDING = 0.31;

    _init = () => {
      self.items = self.items || self.opts.items;
      self.clickHandler = self.clickHandler || self.opts.clickHandler || self.clickhandler || self.opts.clickhandler;

      _packageItems();
    }

    _isFunction = (fuc) => {
      return fuc && typeof fuc === C_FUNCTION;
    }

    _packageItems = () => {
      if (self.items && self.items.length) {
        for (var i = 0, length = self.items.length; i < length; i++) {
          var item = self.items[i];
          if (item.left) {
            var leftStyle, paddingLeft;

            leftStyle = '';
            paddingLeft = 0;
            item.left.color = item.left.color || LEFT_COLOR;

            if (item.left.icon && item.left.icon.url) {
              if (!item.left.icon.width) {
                item.left.icon.width = item.left.icon.height = 0.23;
              } else if (!item.left.icon.height) {
                item.left.icon.height = item.left.icon.width
              }
              item.left.unit = item.left.unit || ICON_UNIT;
              item.left.icon.padding = item.left.icon.padding || ICON_PADDING;

              leftStyle += 'background-image: url(' + item.left.icon.url + ');background-size: '
                + item.left.icon.width + item.left.unit + ' '
                + item.left.icon.height + item.left.unit + ';';

              if (!item.left.content) {
                leftStyle += 'width: ' + item.left.icon.width + item.left.unit
                  + ';height: ' + item.left.icon.height + item.left.unit + ';';
              }
              paddingLeft = item.left.icon.width + item.left.icon.padding;
              leftStyle += 'padding-left: ' + paddingLeft + item.left.unit + ';';
            }
            leftStyle += 'color: ' + item.left.color + ';';
            item.left.style = leftStyle;
          }


          if (item.right) {
            var rightStyle, paddingRight;

            rightStyle = '';
            paddingRight = 0;
            item.right.color = item.right.color || RIGHT_COLOR;

            if (item.right.icon && item.right.icon.url) {
              if (!item.right.icon.width) {
                item.right.icon.width = item.right.icon.height = 0.23;
              } else if (!item.right.icon.height) {
                item.right.icon.height = item.right.icon.width
              }

              item.right.unit = item.right.unit || ICON_UNIT;
              item.right.icon.padding = item.right.icon.padding || ICON_PADDING;

              rightStyle += 'background-image: url(' + item.right.icon.url + ');background-size: '
                + item.right.icon.width + item.right.unit + ' '
                + item.right.icon.height + item.right.unit + ';';

              if (!item.right.content) {
                rightStyle += 'width: ' + item.right.icon.width + item.right.unit
                  + ';height: ' + item.right.icon.height + item.right.unit + ';';
              }
              paddingRight = item.right.icon.width + item.right.icon.padding;
              rightStyle += 'padding-right: ' + paddingRight + item.right.unit + ';';
            }

            rightStyle += 'color: ' + item.right.color + ';';
            item.right.style = rightStyle;
          }

          if (item.label) {
            if (!item.label.height) {
              item.label.height = 0.41;
            }

            if (!item.label.color) {
              item.label.color = '#fff';
            }

            if (!item.label.bgcolor) {
              item.label.bgcolor = '#ac9456';
            }

            labelStyle = 'background-color: ' + item.label.bgcolor + '; color: ' + item.label.color;
            item.label.style = labelStyle;
          }

          item.clickHandler = item.clickHandler || self.clickHandler;
        };

        self.update();
      }
    }

    this.clickFuc = (e) => {
      var item = e.item;

      if (item && item.clickHandler && _isFunction(item.clickHandler)) {
        item.clickHandler(e);
      }
    }

    _init();

    this.on('updated', () => {
      _packageItems();
    });

  </script>
</lr-list>
