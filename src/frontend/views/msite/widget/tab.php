<ul class="m-tab m-list">
    <?php foreach ($tabs as $idx => $tab) { ?>
        <li class="m-tab-item <?= ($idx == 0) ? 'active' : ''; ?>">
            <h2 class="m-tab-title m-tab-title-bgcolor <?= ($idx == 0) ? 'm-border-color m-color' : '';?>"><?= (empty($tab['name']) && $tab['name'] != '0') ? 'Tab' . ($idx + 1) : $tab['name']; ?></h2>
            <div class="m-tab-body">
                <?php
                    if(!empty($tab['cpts'])) {
                        foreach ($tab['cpts'] as $component) {
                            if(isset($component['name']) && !empty($component['jsonConfig'])) {
                                echo $this->renderAjax($component['name'], $component['jsonConfig'], true);
                            }
                        }
                    }
                ?>
            </div>
        </li>
    <?php } ?>
</ul>
