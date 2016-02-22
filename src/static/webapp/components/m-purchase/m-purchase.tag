<m-purchase>
  <section class="c-purchase">
    <div class="c-purchase-item" each={ item, index in opts.items }>
      <div class="c-purchase-item-title">{ item.title }</div>
      <div class="c-purchase-item-stats">
        <div class="c-purchase-item-stats-icon" riot-style="background-image: { item.icon }; background-color: { item.bgColor[0] }"></div>
        <div class="c-purchase-item-stats-label">
          <div class="stats-label-top" riot-style="background-color: { item.bgColor[0] }; width: { item.width[0] }"></div>
          <div class="stats-label-bottom" riot-style="background-color: { item.bgColor[1] }; width: { item.width[1] }"></div>
        </div>
      </div>
      <div class="c-purchase-item-data">
        <div class="c-purchase-item-data-total" riot-style="color: { item.fontColor[0] }">
          <i>{ item.data[0] }{ item.unit }</i>
        </div>
        <div class="c-purchase-item-data-avg" riot-style="color: { item.fontColor[1] }">
          <i>{item.keyword}&nbsp;{ item.data[1] }{ item.unit }</i>
        </div>
      </div>
    </div>
  </section>
</m-purchase>
