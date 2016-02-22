<div class="m-cover3-container">
    <div class="m-cover3-nav">
        <?php
            $length = count($navs);
            $navItemClass = '';
            $lengthArray = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth'];
            $navIcons = ['fashionnews', 'touristattractions', 'gourmetcooking', 'weatherexpress', 'prettybaby', 'appreciationofarchitecture'];
            $navItemClass = "nav-" . $lengthArray[($length-1)] . "-situation";
            for ($i = 0; $i < $length; $i++)
            {
                $pic = $navs[$i]["pic"];
                $picStyleAttr = '';
                if (!empty($pic)) {
                    $picStyleAttr = 'style="background-image:url(\'' . $pic . '\');"';
                }

                $iconStyleAttr = '';
                $icon = $navs[$i]["icon"];
                if (!empty($icon)) {
                    $iconStyleAttr = 'style="background-image:url(\'' . $icon . '\');"';
                }
                $name = $navs[$i]["name"];
                $link = isset($navs[$i]['linkUrl']) && !empty($navs[$i]['linkUrl']) ? (strpos($navs[$i]['linkUrl'], 'http') === false ? ('http://'. $navs[$i]['linkUrl']) : $navs[$i]['linkUrl']) : '';
                $linkTarget = isset($navs[$i]['linkUrl']) && !empty($navs[$i]['linkUrl']) && strpos($navs[$i]['linkUrl'], DOMAIN) === false ? '_blank' : '_self';
                echo '<div class="nav-background-box ' . $navItemClass . '" ' . $picStyleAttr . '>
                            <div class="nav-overflow-box">
                                <div class="nav-info-box">
                                    <div class="nav-info-icon nav-icon-'.$navIcons[$i].'" ' . $iconStyleAttr . '></div>
                                    <div class="nav-info-content">' . $name . '</div>
                                </div>
                            </div>
                            <a class="m-nav-bglink" href="' . $link . '" target="' . $linkTarget . '"></a>
                        </div>';
            }
        ?>
    </div>
</div>
