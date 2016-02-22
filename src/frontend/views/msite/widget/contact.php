<?php
    $address = '';
    ( isset($location["province"]) && !empty($location["province"]) ) && $address .= $location["province"];
    ( isset($location["city"]) && !empty($location["city"]) ) && $address .= ' ' . $location["city"];
    ( isset($location["county"]) && !empty($location["county"]) ) && $address .= ' ' . $location["county"];
    ( isset($detailLoc) && !empty($detailLoc) ) && $address .= ' ' . $detailLoc;
    $address = $address === '' ? '无' : $address;
?>
<div class="m-contact">
    <div class="m-contact-item clearfix">
        <span class="m-item-title">名称</span>
        <span class="m-item-content">
            <?php echo (isset($name) && (!empty($name) || $name == '0') ? htmlspecialchars($name, ENT_QUOTES) : '无'); ?>
        </span>
    </div>
    <div class="m-contact-item clearfix">
        <span class="m-item-title">电话号码</span>
        <span class="m-item-content">
            <?php echo (isset($tel) && (!empty($tel) || $tel == '0') ? htmlspecialchars($tel, ENT_QUOTES) : '无'); ?>
        </span>
    </div>
    <div class="m-contact-item clearfix">
        <span class="m-item-title">常用邮箱</span>
        <span class="m-item-content">
            <?php echo (isset($email) && (!empty($email) || $email == '0') ? htmlspecialchars($email, ENT_QUOTES) : '无'); ?>
        </span>
    </div>
    <div class="m-contact-item clearfix">
        <span class="m-item-title">QQ号码</span>
        <span class="m-item-content">
            <?php echo (isset($qq) && (!empty($qq) || $qq == '0') ? htmlspecialchars($qq, ENT_QUOTES) : '无'); ?>
        </span>
    </div>
    <div class="m-contact-item clearfix">
        <span class="m-item-title">通讯地址</span>
        <span class="m-item-content">
            <?php echo htmlspecialchars($address, ENT_QUOTES); ?>
        </span>
    </div>
    <!-- <div class="m-contact-add"> 添加到通讯录 </div> -->
    <div class="m-contact-blank"></div>
</div>
