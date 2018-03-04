<? /* @var $this \Pecee\Widget\Debug\WidgetDebug */ ?>

<?= $this->printJs('debug'); ?>
<?= $this->printCss('debug'); ?>

<div class="pecee-debug">
    <h1>Debug information</h1>
    <table cellspacing="0" cellpadding="0">
        <thead>
        <tr>
            <th align="left">
                Execution time
            </th>
            <th align="left">
                Message
            </th>
            <th align="left">
                Class
            </th>
            <th align="left">
                Method
            </th>
            <th align="left">
                File
            </th>
            <th align="center">
                Line
            </th>
        </tr>
        </thead>
        <tbody>
        <? foreach($this->stack as $i => $log) : ?>
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
                    <? if(isset($log['debug'][count($log['debug'])-1]['class'])) : ?>
                        <?= $log['debug'][count($log['debug'])-1]['class']; ?>
                    <? endif; ?>
                </td>
                <td>
                    <?= $log['debug'][count($log['debug'])-1]['method']; ?>
                </td>
                <td>
                    <?= $log['debug'][count($log['debug'])-1]['file']; ?>
                </td>
                <td align="center">
                    <?= !isset($log['debug'][count($log['debug'])-1]['line']) ? '-' : $log['debug'][count($log['debug'])-1]['line']; ?>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
    </table>
</div>