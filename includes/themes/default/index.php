<?php
require_once BASE_PATH . '/system/core/init.php';
require_once 'header.php';
?>

<!-- UnderTop Widgets -->
<?php if (!empty($widgetsByPosition['undertop'])): ?>
    <?php foreach ($widgetsByPosition['undertop'] as $widget): ?>                
        <?= $widget ?>
    <?php endforeach; ?>
<?php endif; ?>


<main class="flex-fill">
    <div class="container">
        <div class="row">

            <!-- Left Widgets -->
            <?php if (!empty($widgetsByPosition['left'])): ?>
                <div class="col-md-3">
                    <?php foreach ($widgetsByPosition['left'] as $widget) echo $widget; ?>
                </div>
            <?php endif; ?>

            <!-- Main Column -->
            <div class="col">

                <!-- MainTop Widgets -->
                <?php if (!empty($widgetsByPosition['maintop'])): ?>
                    <div class="row">
                        <?php foreach ($widgetsByPosition['maintop'] as $widget) echo $widget; ?>
                    </div>
                <?php endif; ?>

                <?= get_mainContent(); ?>

                <!-- MainBottom Widgets -->
                <?php if (!empty($widgetsByPosition['mainbottom'])): ?>
                    <div class="row">
                        <?php foreach ($widgetsByPosition['mainbottom'] as $widget) echo $widget; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right Widgets -->
            <?php if (!empty($widgetsByPosition['right'])): ?>
                <div class="col-md-3">
                    <?php foreach ($widgetsByPosition['right'] as $widget) echo $widget; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<!-- Bottom Widgets -->
<?php if (!empty($widgetsByPosition['bottom'])): ?>
    <div class="row">
        <?php foreach ($widgetsByPosition['bottom'] as $widget) echo $widget; ?>
    </div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
