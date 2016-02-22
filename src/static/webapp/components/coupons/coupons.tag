<coupons>
  <ul class="c-coupons" if={ items && items.length }>
    <li class="c-coupons__item { disable ? 'c-coupons__item--disabled' : '' }" each={ items } onclick={ parent.pickCoupon }>
      <section class="c-coupons__item__price { disable ? 'c-coupons__item__price--disabled' : '' }">
        <div class="c-coupons__item__price__unit">{ unit }</div>
        <div class="c-coupons__item__price__number">{ price }</div>
      </section>
      <section class="c-coupons__item__content">
        <h1 class="c-coupons__item__content__title { disable ? 'c-coupons__item__content__title--disabled' : '' }">{ title }</h1>
        <ul class="c-coupons__item__content__conditions { disable ? 'c-coupons__item__content__conditions--disabled' : '' }">
          <li class="c-coupons__item__content__conditions__item" if={ condition.sums }>
            <div class="c-coupons__item__content__conditions__item__dot { disable ? 'c-coupons__item__content__conditions__item__dot--disabled' : '' }">·</div>
            <div class="c-coupons__item__content__conditions__item__text">{ condition.sums }</div>
          </li>
          <li class="c-coupons__item__content__conditions__item" if={ condition.date }>
            <div class="c-coupons__item__content__conditions__item__dot { disable ? 'c-coupons__item__content__conditions__item__dot--disabled' : '' }">·</div>
            <div class="c-coupons__item__content__conditions__item__text">{ condition.date }</div>
            </li>
        </ul>
      </section>
      <section class="c-coupons__item__status { checked ? 'c-coupons__item__status--checked' : 'c-coupons__item__status--unchecked'}" if={ !disable }></section>
      <section class="c-coupons__item__label" if={ disable } style="background-image: url(/images/mobile/components/{ status }.png)"></section>
    </li>
  </ul>

  <section class="c-coupons c-coupons--none" if={ items && !items.length }>
    <img class="c-coupons--none__icon" src={ nodata.icon }/>
    <div class="c-coupons--none__text">{ nodata.text }</div>
  </section>

  <script>
    var self = this, _init, _packageItems, _isFunction;

    const DEFAULT_UNIT = '￥';
    const C_FUNCTION = 'function';
    const DEFAULT_NODATA_ICON = '/images/mobile/components/voucher.png'
    const DEFAULT_NODATA_TEXT = '您没有可用的代金券~'

    _init = () => {
      self.items = self.items || self.opts.items;
      self.pickedHandler = self.pickedHandler || self.opts.pickedHandler;
      self.nodata = self.nodata || self.opts.nodata || {};

      if (!self.nodata.icon) {
        self.nodata.icon = DEFAULT_NODATA_ICON;
      }

      if (!self.nodata.text) {
        self.nodata.text = DEFAULT_NODATA_TEXT;
      }

      _packageItems();
    }

    _isFunction = (fuc) => {
      return fuc && typeof fuc === C_FUNCTION;
    }

    _packageItems = () => {
      if (self.items && self.items.length) {
        for (var i = 0, length = self.items.length; i < length; i++) {
          var item = self.items[i];
          item.unit = item.unit || DEFAULT_UNIT;
          item.index = ((index) => {
            return index;
          })(i)
        };

        self.update();
      }
    }

    this.on('updated', () => {
      _packageItems();
    });

    this.pickCoupon = (e) => {
      var coupon = e.item, index = 0;

      if (coupon.disable) {
        return;
      }

      if (!coupon.checked) {
        if(coupon && typeof coupon.index != 'undefined' && coupon.index != null) {
          index = coupon.index;
        }

        for (var i = 0, length = self.items.length; i < length; i++) {
          var item = self.items[i];
          item.checked = false;
        };

        if (self.items.length > index) {
          self.items[index].checked = true;
        }

        if (_isFunction(self.pickedHandler)) {
          self.pickedHandler(coupon);
        }
      } else {
        for (var i = 0, length = self.items.length; i < length; i++) {
          var item = self.items[i];
          item.checked = false;
        };

        if (_isFunction(self.pickedHandler)) {
          self.pickedHandler();
        }
      }
    }

    _init();
  </script>
</coupons>
