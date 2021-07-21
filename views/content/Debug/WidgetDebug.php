<? /* @var $this \Pecee\Widget\Debug\WidgetDebug */ ?>

<div class="pecee-debug">
    <h1>Debug information</h1>
    <a href="#" class="close" onclick="this.parentNode.parentNode.removeChild(this.parentNode);">CLOSE</a>
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
            <tr onclick="pecee_show_debug('debug_<?= $i ?>')">
                <td>
                    <?= $log['time'] ?>
                </td>
                <td>
                    <?= $log['text'] ?>
                    <div id="debug_<?= $i ?>" class="debug-info" style="">
                        <pre><?= print_r($log['debug'],true) ?></pre>
                    </div>
                </td>
                <td>
                    <? if(isset($log['debug'][count($log['debug'])-1]['class'])) : ?>
                        <?= $log['debug'][count($log['debug'])-1]['class'] ?>
                    <? endif; ?>
                </td>
                <td>
                    <?= $log['debug'][count($log['debug'])-1]['method'] ?>
                </td>
                <td>
                    <?= $log['debug'][count($log['debug'])-1]['file'] ?>
                </td>
                <td align="center">
                    <?= $log['debug'][count($log['debug']) - 1]['line'] ?? '-' ?>
                </td>
            </tr>
        <? endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .pecee-debug {
        position: fixed;
        overflow: auto;
    }

    .pecee-debug h1 {
        font-family: Arial;
        font-size: 18px;
        margin: 10px 0;
        border-bottom: 1px solid #CCC;
        padding-bottom: 5px;
        font-weight: bold;
    }

    .pecee-debug a.close {
        position: absolute;
        top: 20px;
        right: 20px;
        font-weight: bold;
        font-size: 14px;
    }

    .pecee-debug table {
        width: 100%;
        font-size: 12px;
        font-family: Arial;
    }

    .pecee-debug table thead {
        background-color: #EEE;
    }

    .pecee-debug table th {
        padding: 5px;
    }

    .pecee-debug table tbody {
        background-color: #FFF;
    }

    .pecee-debug table tr {
        border-bottom: 1px solid #CCC;
        cursor: pointer;
        height: 10px;
    }

    .pecee-debug table td {
        vertical-align: top;
        padding: 5px;
    }

    .pecee-debug table td .debug-info {
        display: none;
        background-color: #EEE;
        padding: 10px;
        margin-top: 10px;
    }
</style>
<script>
    function pecee_show_debug(id) {
        var el = document.getElementById(id);
        document.getElementById(id).style.display = (el.style.display == 'block') ? 'none' : 'block';
    }
</script>