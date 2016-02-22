<?php
    // display navigation max number
    $maxCount = 5;
    $column = count($infos) > $maxCount ? $maxCount : count($infos);
?>
<div class="m-nav">
    <div class='m-nav-column<?php echo $column; ?>' data-column='<?php echo $column; ?>'>
        <ul class='m-list m-nav-ul'>
            <?php
                // deal with all navigation information
                empty($infos) && $infos = array ();
                foreach($infos as $index => $info) {
                    // name of navigation
                    $name = isset($info['name']) && trim($info['name']) !== '' ? htmlspecialchars($info['name']) : '';
                    // url of navigation
                    $url = isset($info['linkUrl']) && trim($info['linkUrl']) !== '' ? $info['linkUrl'] : '';
                    $url = stripos($url, 'http') === false || stripos($url, 'http') != 0 ? ('http://'. $url) : $url;
                    // style of opening new page
                    $target = strpos($url, DOMAIN) === false ? '_blank' : '_self';
                    //if index of navigation is in the first two rows display them else hide them
                    $liHidden = $index < $maxCount ? '' : ' m-nav-hidden';
            ?>
            <li class='m-nav-li m-color<?php echo $liHidden; ?>'>
                <a target='<?php echo $target; ?>' class='m-text-overflow m-color' href='<?php echo $url; ?>'>
                    <?php echo $name; ?>
                </a>
            </li>
          <?php } ?>
        </ul>
    </div>
</div>
