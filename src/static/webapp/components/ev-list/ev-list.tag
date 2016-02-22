<ev-list>
  <ul class="c-ev-list">
    <li class="c-ev-list__item" each={ items } style="margin-bottom: { isTotal && parent.items.length > 1 ? '.31rem': '0' }">
      <div class="c-ev-list__item__name">{ name }</div>
      <div class="c-ev-list__item__commstars">
        <span each={ star, index in stars } class="c-ev-list__item__commstars__item c-ev-list__item__commstars__item--{ !!star.checked ? 'checked' : 'unchecked' }" onclick={ parent.pickCommstar } data-index={ star.index } style="background-image: url({ star.checked ? parent.pickedicon : parent.unpickedicon});"></span>
      </div>
    </li>
  </ul>

  <script>
    var self = this, _restoreCommstars, _init;

    const DEFAULT_STAR_SCORE = 5;
    const DEFAULT_STAR_PICKED_ICON_PATH = '/images/mobile/components/score_selected.png';
    const DEFAULT_STAR_UNPICKED_ICON_PATH = '/images/mobile/components/score_normal.png';
    const DEFAULT_STAR_PICKED_COLOR = '#ac9456';
    const DEFAULT_STAR_UNPICKED_COLOR = '#bfbfbf';

    _restoreCommstars = () => {
      if (self.items && self.items.length) {
        for (let i = 0, length = self.items.length; i < length; i++) {
          var item = self.items[i];
          item.score = item.score || DEFAULT_STAR_SCORE;
          item.pickedicon = item.pickedicon || self.pickedicon;
          item.unpickedicon = item.unpickedicon || self.unpickedicon;
          item.pickedScore = item.pickedScore || item.score;

          var stars = new Array(item.score);

          for (var j = stars.length - 1; j >= 0; j--) {
            stars[j] = ((parIndex, curIndex) => {
              return {checked: item.pickedScore > curIndex, curIndex: curIndex, parIndex: parIndex};
            })(i, j);
          };

          item.stars = stars;
        };

        self.update();
      }
    }

    _init = () => {
      self.items = self.items || self.opts.items;
      self.pickedicon = self.pickedicon || self.opts.pickedicon || DEFAULT_STAR_PICKED_ICON_PATH;
      self.unpickedicon = self.unpickedicon || self.unpickedicon || DEFAULT_STAR_UNPICKED_ICON_PATH;
      self.pickedcolor = self.pickedcolor || self.opts.pickedcolor || DEFAULT_STAR_PICKED_COLOR;
      self.unpickedcolor = self.unpickedcolor || self.opts.unpickedcolor || DEFAULT_STAR_UNPICKED_COLOR;
      self.disable = self.disable || self.opts.disable

      _restoreCommstars();
    }

    this.pickCommstar = (e) => {
      if (!!self.disable) {
        return;
      }

      var star = e.item.star;

      self.items[star.parIndex].pickedScore = star.curIndex + 1

      for (var i = 0, length = self.items[star.parIndex].stars.length; i < length; i++) {
        self.items[star.parIndex].stars[i].checked = false;
      };

      for (var i = 0; i <= star.curIndex; i++) {
        self.items[star.parIndex].stars[i].checked = true;
      };
    }

    this.restoreCommstars = () => {
      _restoreCommstars();
    }

    _init();

  </script>
</ev-list>
