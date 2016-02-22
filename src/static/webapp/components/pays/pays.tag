<pays>
  <ul class="c-pays">
    <li class="c-pays__item { way.hide ? 'c-pays__item--hide': '' }" each={ way in items } >
      <section class="c-pays__item__icon c-pays__item__icon--nobg">
        <img class="c-pays__item__icon__img c-pays__item__icon__img--nobg" src={ way.icon }>
      </section>
      <section class="c-pays__item__info">
        <div class="c-pays__item__info__title">{ way.title }</div>
        <div class="c-pays__item__info__supplement">{ way.supplement }</div>
      </section>
      <section class="c-pays__item__radio">
        <radio check={ parent.radioValue === way.radio.value } group={ way.radio.group } name={ way.radio.name } valued={ way.radio.value } disable={ way.radio.disabled === undefined || !way.radio.disabled ? false : true }></radio>
      </section>
    </li>
  </ul>

  <script>
    var self = this;
    this.items = this.items || this.opts.items;
    if (this.items) {
      for (var i = 0, length = this.items.length; i < length; i++) {
        var item = this.items[i];
        if (!item.radio.disabled && !item.hide) {
          this.radioValue = this.radioValue || item.radio.value;
          break;
        }
      };
    }

    this.checkHandle = (value) => {
      self.radioValue = value;
      for (var i = self.items.length - 1; i >= 0; i--) {
        var item = self.items[i];
        item.check = item.radio.value === value;
      };

      var radioTags = self.tags.radio;
      for (var i = radioTags.length - 1; i >= 0; i--) {
        var radioTag = radioTags[i];
        radioTag.check = radioTag.valued === value;
        radioTag.opts.check = radioTag.valued === value;
        radioTag.update();
      };
    }

  </script>
</pays>
