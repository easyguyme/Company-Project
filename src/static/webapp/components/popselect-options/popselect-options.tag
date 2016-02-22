<popselect-options>
  <section class="c-popselect-options" show={ show }>
    <div class="c-popselect-options__mask" onclick={ cancel } ontouchmove={ touchmove }></div>
    <div class="c-popselect-options__container" onclick={ clickContainerFuc }>
      <div class="c-popselect-options__container__header">
        <span class="c-popselect-options__container__header__cancel" onclick={ cancel }>取消</span>
        <span class="c-popselect-options__container__header__title">{ title }</span>
        <span class="c-popselect-options__container__header__sure" onclick={ submit }>确定</span>
      </div>
      <ul class="c-popselect-options__container__options">
        <li each={ option in options } class="c-popselect-options__container__options__item { option.checked ? 'c-popselect-options__container__options__item--checked' : ''}" onclick={ parent.pickHandler }>{ option.text }</li>
      </ul>
    </div>
  </section>

  <script>
    var self = this, _updatePickedOption, _init, _isFunction, _isArray;
    const C_FUNCTION = 'function';
    const C_ARRAY = 'array';

    _isFunction = (fuc) => {
      return fuc && typeof fuc === C_FUNCTION;
    }

    _isArray = (item) => {
      return Object.prototype.toString.call(item).slice(8, -1).toLowerCase() === C_ARRAY;
    }

    _init = () => {
      self.options = self.options || self.opts.options;
      self.title = self.title || self.opts.title;
      self.show = self.show || self.opts.show;
      self.picked = self.picked || self.opts.picked;
      self.submitHandler = self.submitHandler || self.opts.submitHandler;

      _updatePickedOption();
    }

    _updatePickedOption = () => {
      var index = 0;
      if (self.options) {
        for (var i = 0, length = self.options.length; i < length; i++) {
          var option = self.options[i];
          option.checked = self.picked === option.text;
          if (option.checked) {
            index = i;
          }
        };

        self.update();
      }

      // scroll to checked item
      var $options = $('.c-popselect-options__container__options');

      if (self.options && _isArray(self.options)) {
        var optionsLength = self.options.length;
        var singleOptionHeight = 0;

        if ($options && $options.length) {
          if (_isFunction($options.height)) {
            singleOptionHeight = $options.height() / 5;
          } else {
            singleOptionHeight = $options.css('height').replace('px', '') / 5
          }

          switch (index) {
            case 0:
            case 1:
              $options.scrollTop(0);
              break;
            case optionsLength - 2:
            case optionsLength - 1:
              $options.scrollTop((optionsLength - 5) * singleOptionHeight);
              break;
            default:
              $options.scrollTop((index - 2) * singleOptionHeight);
          }
        }
      } else if ($options && $options.length) {
        $options.scrollTop(0);
      }
    }

    this.updatePickedOption = () => {
      _updatePickedOption();
    }

    this.submit = () => {
      if(!!self.picked) {
        self.show = false;
        self.submitHandler(self.picked);
      }
    }

    this.cancel = () => {
      self.show = false;
    }

    this.pickHandler = (e) => {
      var checkedOption = e.item.option;
      self.picked = checkedOption.text;

      for (var i = 0, length = self.options.length; i < length; i++) {
        var option = self.options[i];
        option.checked = checkedOption.text === option.text;
      }
    }

    this.clickContainerFuc = (e) => {
      e.stopPropagation();
      e.preventDefault();
    }

    this.touchmove = (e) => {
      e.stopPropagation();
      e.preventDefault();
    }

    _init();

  </script>
</popselect-options>
