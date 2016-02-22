<img-list>
  <ul class="c-img-list c-img-list--type{ type } clearfix">
    <li class="c-img-list__item" each={ item in list }>
      <a href={ item.link }>
        <img-panel img={ item.img } is-horizonal={ isHorizonal }>
          <div class="c-img-list__name">{ item.name }</div>
          <div class="c-img-list__price text-overflow">{ item.price }</div>
        </img-panel>
      </a>
    </li>
  </ul>
  <div show={ isLoading } class="loading"></div>

  let self = this
  self.list = opts.list || []
  self.isLoading = opts.isLoading || false

</img-list>
