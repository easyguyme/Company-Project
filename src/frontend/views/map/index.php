<div class="modal-content store-map-components" data-domain="<?= DOMAIN?>">
    <header class="modal-header components-header">
        <button type="button" class="close popup-close graphic-btn-close cp"></button>
        <h4 id="componentsTitle" class="modal-title components-header-title">搜索定位</h4>
    </header>
    <section class="modal-body components-content">
        <div class="locate-wrapper">
            <div class="form-group map-location-wrapper row clear-container-margin">

                <!-- province select-->
                <section class="col-md-4 col-sm-4 col-xs-4">
                    <div class="select fs12 location-wrapper-item location-wrapper-province">
                        <div class="select-btn gray3 clearfix">
                            <div class="select-left">省份/直辖市</div>
                            <div class="select-right">
                                <span class="custom-caret"></span>
                            </div>
                        </div>
                        <ul id="provinceItems" class="select-dropdown location-select-items hide" data-type='province'></ul>
                    </div>
                </section>

                <!-- city select-->
                <section class="col-md-4 col-sm-4 col-xs-4">
                    <div class="select fs12 location-wrapper-item location-wrapper-city">
                        <div class="select-btn gray3 clearfix select-disabled">
                            <div class="select-left">市</div>
                            <div class="select-right">
                                <span class="custom-caret"></span>
                            </div>
                        </div>
                        <ul id="cityItems" class="select-dropdown location-select-items hide" data-type='city'></ul>
                    </div>
                </section>

                <!-- county select-->
                <section class="col-md-4 col-sm-4 col-xs-4">
                    <div class="select fs12 location-wrapper-item location-wrapper-county">
                        <div class="select-btn gray3 clearfix select-disabled">
                            <div class="select-left">县</div>
                            <div class="select-right">
                                <span class="custom-caret"></span>
                            </div>
                        </div>
                        <ul id="countyItems" class="select-dropdown location-select-items hide" data-type='county'></ul>
                    </div>
                </section>
            </div>
            <div class="form-group locate-detail-wrapper">
                <input type="text" class="form-control detail-wrapper-text" />
                <button id="searchStores" class="btn btn-success btn-map-operate">搜索定位</button>
            </div>
            <span id="locationFormTip" class="form-tip display-form-tip">请勿重复填写省市区地区</span>
        </div>

        <label id="locateLabel" class="fields-item-label">定位</label>
        <div class="result-wrapper">
            <div class="result-wrapper-header">
                <button id="btnMarkStore" class="btn btn-default btn-map-operate btn-highlighted-store">标注新门店</button>
            </div>
            <div class="result-wrapper-content clearfix">
                <div class="stores-container pull-left">
                    <div id="foundStoresContent" class="stores-container-header">共找到0家门店</div>
                    <div id="storesContainer" class="stores-container-content"></div>
                </div>
                <div id="mapContainer" class="map-container pull-right"></div>
            </div>
        </div>
        <div class="center-text operate-wrapper">
            <button id="determineStores" class="btn btn-success btn-map-operate">确定</button>
        </div>
    </section>
</div>
