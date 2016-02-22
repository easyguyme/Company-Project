<?php
    $defaultUrl = 'http://api.map.baidu.com/staticimage?center=121.595105,31.198901&width=300&height=260&zoom=14&markers=121.595105,31.198901&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1';
    $address = '';
    (isset($location["province"]) && !empty($location["province"])) && $address = $address.$location["province"];
    (isset($location["city"]) && !empty($location["city"])) && $address = $address.$location["city"];
    (isset($location["county"]) && !empty($location["county"])) && $address = $address.$location["county"];
    (isset($town) && (!empty($town) || $town == '0')) && $address = $address.$town;
    $address == '' && $address = '无'

?>
<div class="m-map-container">
    <div class="m-info-header">
        <div class="m-info-name"><?php echo (isset($name) && (!empty($name) || $name == '0') ? htmlspecialchars($name, ENT_QUOTES): ''); ?></div>
        <div class="m-info-address" style="<?php echo (isset($isDisplayMapIcon) && !empty($isDisplayMapIcon) && $isDisplayMapIcon == 'true'? '' : 'margin-bottom: 0;'); ?>">地址 : <?php echo htmlspecialchars($address, ENT_QUOTES); ?></div>
    </div>
    <div class="m-map-picture m-mediate <?php echo (isset($isDisplayMapIcon) && !empty($isDisplayMapIcon) && $isDisplayMapIcon == 'true'? '' : 'm-hidden'); ?>">
        <img id="mapBgIcon" style="width: 100%;" src="<?php echo (isset($url) && !empty($url) ? $url : $defaultUrl); ?>">
    </div>
</div>
