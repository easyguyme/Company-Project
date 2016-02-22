<nav-tab>
  <ul class="c-nav-tab clearfix">
    <li each={ nav, index in opts.navs } class="c-nav-tab__item { active: nav.active }" riot-style="width: { navItemWidth }" onclick={ clickNav }>{ nav.name }</li>
  </ul>

  var self = this

  if (opts.navs) {
    self.navItemWidth = Math.floor(100 / opts.navs.length) -1 + '%'
  }

  self.clickNav = (e) => {
    for(let index = 0, len = opts.navs.length; index < len; index ++) {
      opts.navs[index].active = index === e.item.index
    }
    opts.clickNav(e.item.index)
  }
</nav-tab>
