<select-specifications>
  <section class="c-select-specifications" if={ isShow }>
    <section class="c-select-specifications__modal" onclick={ closeFunc }></section>
    <section class="c-select-specifications__content">
      <div class="c-select-specifications__content__imgwrapper">
        <span class="c-select-specifications__content__imgwrapper__close" onclick={ closeFunc }></span>
        <div class="c-select-specifications__content__imgwrapper__img" style="background-image:url({ img })"></div>
        <div class="c-select-specifications__content__imgwrapper__introduction">
          <div class="c-select-specifications__content__imgwrapper__introduction__price">{ unit }{ price }</div>
          <div class="c-select-specifications__content__imgwrapper__introduction__name">{ tip }</div>
        </div>
      </div>

      <ul class="c-select-specifications__content__list">
        <li class="c-select-specifications__content__list__item" each={ item in specifications}>
          <span class="c-select-specifications__content__list__item__title">{ item.name }</span>
          <ul class="c-select-specifications__content__list__item__main clearfix">
            <li class="c-select-specifications__content__list__item__main__item {property.active ? 'active' : ''} {property.disable ? 'disable' : ''}" each={ property in item.properties } onclick={ selectHandler }>{ property.name }</li>
          </ul>
        </li>
      </ul>

      <div class="c-select-specifications__content__btn">
        <btn text={ btnText } type="solid" click-handler={ ensureFunc }></btn>
      </div>
    </section>
  </section>

    var self = this,
        factors = [],
        originTip = '',
        originPrice = '',
        firstUpdate = true,
        noSelectParentIdx = -1,
        _init,
        _formateReturnData,
        _formateSpecifications,
        _getPrice,
        _getSelectTip,
        _getAllSelectTip,
        _getNoSelectTip,
        _isSelectAll,
        _disableProperty,
        _disableOneLineProp,
        _disablePropertyWithOneSpec,
        _enableAllProperty,
        _enablePropertyByParentIdx,
        _hilightSelect,
        _getProperty,
        _getPropLenArr,
        _getFactors;

    const FUNCTION = 'function'

    self.specifications = opts.specifications || self.specifications || []
    self.isShow = opts.isShow || self.isShow  || false
    self.tip = opts.tip || self.tip
    self.price = opts.price || self.price
    self.closeHandler = opts.closeHandler || self.closeHandler
    self.ensureHandler = opts.ensureHandler || self.ensureHandler

    self.closeFunc = () => {
      var data = {}
      self.isShow = false
      if (self.closeHandler && typeof self.closeHandler == FUNCTION) {
        data = {
          tip: self.tip,
          price: self.price
        }
        if (_isSelectAll(self.specifications)) {
          data.specs = _formateReturnData(self.specifications).specs
        }
        self.closeHandler(data)
      }
    }

    self.selectHandler = (e) => {
      var property = e.item.property

      if (!property.disable) {
        property.active = !property.active
        if (property.active) {
          self.specifications[property.parent].selectProIndex = property.index
        } else {
          self.specifications[property.parent].selectProIndex = undefined
        }

        _hilightSelect(e)

        if (self.specifications.length > 1) {
          _disableProperty(self.specifications, factors, self.status, property.parent)
        }

      }
    }

    self.ensureFunc = () => {
      if (_isSelectAll(self.specifications)) {
        if (self.ensureHandler && typeof self.ensureHandler == FUNCTION) {
          self.ensureHandler(_formateReturnData(self.specifications))
        }
      }
    }

    self.on('updated', function() {
      _init()
    })

    $('.service-detail-page').on('touchmove', '.c-select-specifications__modal', function(e) {
      e.preventDefault()
    })

    // cache first tip and price, so that we can recover them when user select no spec,
    // calculate factors and formate specifications for mapping price and status
    // if only has one spec, we should map properties to status
    _init = () => {
      if (self.specifications && self.specifications.length > 0) {

        if (firstUpdate) {
          originTip = self.tip
          originPrice = self.price
          firstUpdate = false
          factors = _getFactors(self.specifications)
          self.specifications = _formateSpecifications(self.specifications)
        }

        self.price = _getPrice(self.specifications, factors, self.values)
        self.tip = _getSelectTip(self.specifications)

        if (self.specifications.length == 1) {
          _disablePropertyWithOneSpec(self.specifications, self.status)
        }

        self.update()
      }
    }

    // Only has one spec
    _disablePropertyWithOneSpec = (specifications, status) => {
      var properties = specifications[0].properties
      for (let i = 0, len = properties.length; i < len; i++) {
        properties[i].disable = !status[i]
      }
    }

    // -1: no select item >1
    // -2: all select
    // >-1: no select item =1
    _disableProperty = (specifications, factors, status, parentIndex) => {
      var data = _getNoSelectData(specifications, factors)

      if (data.index == -1) {
        _enableAllProperty(specifications, false)
      }

      if (data.index > -1) {
        noSelectParentIdx = data.index

        if (parentIndex != noSelectParentIdx) {
          _enablePropertyByParentIdx(specifications, noSelectParentIdx)
        } else {
          _enableAllProperty(specifications, false)
        }
      }

      if (data.index == -2 && parentIndex != noSelectParentIdx) {
        _enableAllProperty(specifications, true)
        _disableProperty(specifications, factors, status, parentIndex)
      }

      if (data.index > -1 ) {
        _disableOneLineProp(data, specifications)
      }
    }

    _disableOneLineProp = (data, specifications) => {
      for (let i = 0, len = specifications[data.index].properties.length; i < len; i++) {
        if (!self.status[data.sum + i * factors[data.index]]) {
          specifications[data.index].properties[i].disable = true
        }
      }
    }

    _enableAllProperty = (specifications, isAllselect) => {
      var properties,
          specLen = specifications.length

      for (let i = 0; i < specLen; i++) {
        properties = specifications[i].properties
        if (isAllselect && (i == specLen - 1)) {
          specifications[i].selectProIndex = undefined
        }
        for (let j = 0, size = properties.length; j < size; j++) {
          if (isAllselect && (i == specLen - 1)) {
            properties[j].active = false
          }
          properties[j].disable = false
        }
      }
    }

    _enablePropertyByParentIdx = (specifications, index) => {
      var properties = specifications[index].properties

      for (let i = 0, len = properties.length; i < len; i++) {
        properties[i].disable = false
      }
    }

    _formateReturnData = (specifications) => {
      var specs = [],
          selectIndex
      for (let i = 0, len = specifications.length; i< len; i++) {
        selectIndex = specifications[i].selectProIndex
        specs.push({
          name: specifications[i].name,
          value: specifications[i].properties[selectIndex].name
        })
      }
      return {
        specs: specs,
        price: self.price
      }
    }

    _getPrice = (specifications, factors, values) => {
      if (_isSelectAll(specifications)) {
        var calculateIndex = 0,
            selectIndex
        for (let i = 0, len = specifications.length; i < len; i++) {
          selectIndex = specifications[i].selectProIndex
          calculateIndex += specifications[i].properties[selectIndex].index * factors[i]
        }
        return values[calculateIndex]
      }
      return originPrice
    }

    _getSelectTip = (specifications) => {
      var tip = ''
      if (_isSelectAll(specifications)) {
        tip = _getAllSelectTip(specifications)
      } else {
        tip = _getNoSelectTip(specifications)
      }
      return tip
    }

    _getAllSelectTip = (specifications) => {
      var tips = [],
          selectIndex

      for (let i = 0, len = specifications.length; i < len; i++) {
        selectIndex = specifications[i].selectProIndex
        tips.push(specifications[i].properties[selectIndex].name)
      }
      return tips.join('，')
    }

    _getNoSelectTip = (specifications) => {
      var tips = []
      for (let i = 0, len = specifications.length; i < len; i++) {
        if (specifications[i].selectProIndex == undefined) {
          tips.push(specifications[i].name)
        }
      }
      if (tips.length == specifications.length) {
        return originTip
      }
      return '请选择 ' + tips.join('，')
    }

    _isSelectAll = (specifications) => {
      for (let i = 0, len = specifications.length; i < len; i++) {
        if (specifications[i].selectProIndex == undefined) {
          return false
        }
      }
      return true
    }

    _getNoSelectData = (specifications, factors) => {
      var len = specifications.length,
          noSelectCount = 0,
          index = -2,
          sum = 0

      for (let i = 0; i < len; i++) {
        if (specifications[i].selectProIndex == undefined) {
          noSelectCount++
          index = i
        } else {
          sum += factors[i] * _getProperty(specifications, i).index
        }
      }
      if (noSelectCount > 1) {
        index = -1
      }

      return {
        index: index,
        sum: sum
      }
    }

    _getProperty = (specifications, index) => {
      return specifications[index].properties[specifications[index].selectProIndex]
    }


    _hilightSelect = (e) => {
      var selectItem = e.item.property,
          property

      for (let i = 0, len = self.specifications[selectItem.parent].properties.length; i < len; i++) {
        property = self.specifications[selectItem.parent].properties[i]
        if (property.index != selectItem.index) {
          property.active = false
        }
      }
    }

    _formateSpecifications = (specifications) => {
      var specification = {},
          property = {}
      for (let i = 0, len = specifications.length; i < len; i++ ) {
        specification = specifications[i]
        for (let j = 0, len = specification.properties.length; j < len; j++) {
          property = specification.properties[j]
          property.index = j
          property.parent = i
        }
      }
      return specifications
    }

    _getPropLenArr = (specifications) => {
      var propLenArr = [],
          specification = {}
      for (let i = 0, len = specifications.length; i < len; i++ ) {
        specification = specifications[i]
        propLenArr.push(specification.properties.length)
      }
      return propLenArr
    }

    _getFactors = (specifications) => {
      var propLenArr = _getPropLenArr(specifications),
          factors = []
      for (let i = 0, len = propLenArr.length; i < len; i++) {
        var tempValue = 1;
        for (let j = i + 1; j < len; j++) {
          tempValue *= propLenArr[j]
        }
        factors.push(tempValue)
      }
      return factors
    }

</select-specifications>

