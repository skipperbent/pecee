<? /* @var $this \Pecee\Widget\Debug\WidgetDebug */ ?>

<h1 style="font-family:Arial;font-size:18px;margin:10px 0;border-bottom:1px solid #CCC;padding-bottom:5px;">Debug information</h1>
<table cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;font-family:Arial;">
    <thead style="background-color:#EEE;">
    <tr>
        <th align="left" style="padding:5px;">
            Execution time
        </th>
        <th align="left" style="padding:5px;">
            Message
        </th>
        <th align="left" style="padding:5px;">
            Class
        </th>
        <th align="left" style="padding:5px;">
            Method
        </th>
        <th align="left" style="padding:5px;">
            File
        </th>
        <th align="center" style="padding:5px;">
            Line
        </th>
    </tr>
    </thead>
    <tbody style="background-color:#FFF;">
    <? foreach($this->stack as $i => $log) : ?>
        <tr style="border-bottom:1px solid #CCC;cursor:pointer;height:10px;" onclick="show_debug('debug_<?= $i; ?>')">
            <td style="vertical-align: top;padding:5px;">
                <?= $log['time']; ?>
            </td>
            <td style="vertical-align: top;padding:5px;">
                <?= $log['text']; ?>
                <div id="debug_<?= $i; ?>" style="display: none;background-color:#EEE;padding:10px;margin-top:10px;">
                    <pre><?= print_r($log['debug'],true); ?></pre>
                </div>
            </td>
            <td style="vertical-align:top; padding:5px;">
                <?= $log['debug'][count($log['debug'])-1]['class']; ?>
            </td>
            <td style="vertical-align:top; padding:5px;">
                <?= $log['debug'][count($log['debug'])-1]['method']; ?>
            </td>
            <td style="vertical-align:top; padding:5px;">
                <?= $log['file']; ?>
            </td>
            <td style="vertical-align:top; padding:5px;" align="center">
                <?= $log['debug'][count($log['debug'])-1]['line']; ?>
            </td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
<script>
    function show_debug(id) {
        var el = document.getElementById(id);
        document.getElementById(id).style.display = (el.style.display == 'block') ? 'none' : 'block';
    }
</script>
