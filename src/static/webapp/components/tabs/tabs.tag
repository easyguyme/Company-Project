<tabs>
  <nav class="c-tabs">
    <ul class="c-tabs__navs">
      <li class="c-tabs__navs__item { checked ? 'c-tabs__navs__item--checked' : ''}" each={ items } onclick={ parent.checkTab } style="width: {100 / items.length}%;">{ text }</li>
    </ul>
  </nav>

  <script>
    var self = this, _init, _packageWidget, _isFunction;

    const C_FUNCTION = 'function';

    _init = () => {
      self.items = self.items || self.opts.items;
      self.pickedHandler = self.pickedHandler || self.opts.pickedHandler;

      _packageWidget();
    }

    _isFunction = (fuc) => {
      return fuc && typeof fuc === C_FUNCTION;
    }

    _packageWidget = () => {
      var hasChecked = false;

      if (self.items && self.items.length) {
        for (var i = 0, length = self.items.length; i < length; i++) {
          var item = self.items[i];
          if (item.checked) {
            hasChecked = true;
          }
          item.index = ((index) => {
            return index;
          })(i)
        };

        if (!hasChecked) {
          self.items[0].checked = true;
        }

        self.update();
      }
    }

    this.checkTab = (e) => {
      var item = e.item;

      if (item) {
        for (var i = 0, length = self.items.length; i < length; i++) {
          var tab = self.items[i];
          tab.checked = false;
        };

        item.checked = true;
        self.items[item.index].checked = true;

        if (self.pickedHandler && _isFunction(self.pickedHandler)) {
          self.pickedHandler(item);
        }
      }
    }

    this.on('updated', () => {
      _packageWidget();
    });

    _init();

  </script>
</tabs>
