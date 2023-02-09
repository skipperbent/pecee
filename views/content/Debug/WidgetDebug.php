<?php /* @var $this \Pecee\Widget\Debug\WidgetDebug */ ?>

<?= $this->printJs('debug'); ?>
<?= $this->printCss('debug'); ?>

<div class="pecee-debug">
    <h1>Debug information</h1>
    <table>
        <thead>
        <tr>
            <th>
                Execution time
            </th>
            <th>
                Message
            </th>
            <th>
                Class
            </th>
            <th>
                Method
            </th>
            <th>
                File
            </th>
            <th style="text-align:center;">
                Line
            </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($this->stack as $i => $log) : ?>
            <tr onclick="pecee_show_debug('debug_<?= $i; ?>')">
                <td>
                    <?= $log['time']; ?>
                </td>
                <td>
                    <?= $log['text']; ?>
                    <div id="debug_<?= $i; ?>" class="debug-info" style="">
                        <pre><?= print_r($log['debug'],true); ?></pre>
                    </div>
                </td>
                <td>
                    <?php if(isset($log['debug'][count($log['debug'])-1]['class'])) : ?>
                        <?= $log['debug'][count($log['debug'])-1]['class']; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $log['debug'][count($log['debug'])-1]['method']; ?>
                </td>
                <td>
                    <?= $log['debug'][count($log['debug'])-1]['file']; ?>
                </td>
                <td style="text-align:center;">
                    <?= !isset($log['debug'][count($log['debug'])-1]['line']) ? '-' : $log['debug'][count($log['debug'])-1]['line']; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>