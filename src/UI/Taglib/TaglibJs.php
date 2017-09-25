<?php
namespace Pecee\UI\Taglib;

class TaglibJs extends Taglib
{

    protected $containers = [];

    protected static $JS_WRAPPER_TAG = '';
    protected static $JS_EXPRESSION_START = '/js{/';
    protected static $JS_WIDGET_EXPRESSION = '/\\$self(.*?)}/';

    protected function makeJsString($string)
    {
        return preg_replace('/[\n\r\t]*|\s\s/', '', trim($string));
    }

    protected function handleInline($string)
    {
        $string = str_replace('\\"', '"', str_replace("\\'", '\'', $string));
        $parts = preg_split('/[;\n]{1,2}/', $string);
        if (count($parts) <= 1) {
            return "($string)";
        }

        $result = '';
        for ($i = 0, $max = count($parts); $i < $max; $i++) {
            $result .= ($i == (count($parts) - 1)) ? 'return ' . $parts[$i] . ';' : $parts[$i] . ';';
        }

        return sprintf('(function(){%s})()', $result);
    }

    protected function replaceJsExpressions($string)
    {
        $fixedExpressions = [];
        $expressionMatches = [];
        // Change all widget expressions
        $string = preg_replace(static::$JS_WIDGET_EXPRESSION, '$p.getWidget(\'"+g+"\')$1', $string);
        preg_match_all(static::$JS_EXPRESSION_START, $string, $expressionMatches, PREG_OFFSET_CAPTURE);

        $expressions = [];
        $mOffset = 0;

        foreach ($expressionMatches[0] as $match) {

            $mText = $match[0];
            $offset = $match[1];
            $searchOffset = $offset + strlen($mText);
            $curlies = 1;
            $end = 0;
            for ($i = $searchOffset, $max = strlen($string); $i < $max; $i++) {
                switch ($string[$i]) {
                    case '{':
                        $curlies++;
                        break;
                    case '}':
                        $curlies--;
                        break;
                }
                if ($curlies == 0) {
                    $end = $i;
                    break;
                }
            }
            if ($end >= $mOffset) {
                $expressions[] = [
                    'raw' => substr($string, $offset, $end - $offset + 1),
                    'js'  => substr($string, $searchOffset, $end - $searchOffset),
                ];
            }

        }

        if (count($expressions) > 0) {
            /* Let's ensure that our js-expression don't get addslashed */
            foreach ($expressions as $expr) {
                $fixedExpressions[] = '"+' . $this->handleInline($expr['js']) . '+"';
            }

            /* Now we replace the expression tags, with the fixed js expression */
            for ($i = 0, $max = count($expressions); $i < $max; $i++) {
                $string = str_replace($expressions[$i]['raw'], $fixedExpressions[$i], $string);
            }
        }

        return $string;
    }

    protected function tagContainer($attrs)
    {
        $this->requireAttributes($attrs, ['id']);

        $output = sprintf('$.%1$s = new $p.template(); $.%1$s.view = function(d,g){var self=this; var o="<%3$s>%2$s</%3$s>"; return o;};',
            $attrs->id,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG
        );

        $matches = [];

        preg_match_all('%<' . static::$JS_WRAPPER_TAG . '>(.*?)</' . static::$JS_WRAPPER_TAG . '>%', $output, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $m) {
                $output = str_replace('<' . static::$JS_WRAPPER_TAG . '>' . $m . '</' . static::$JS_WRAPPER_TAG . '>', addslashes($m), $output);
            }
        }

        $this->containers[$attrs->id] = $this->replaceJsExpressions($output);

        if (app()->getDebugEnabled() === true) {
            $this->containers[$attrs->id] = str_replace('o+=', "\no+=", $this->containers[$attrs->id]);
            $this->containers[$attrs->id] = preg_replace('/";(\}else\{|for|if]switch)/i', "\";\n$1", $this->containers[$attrs->id]);
        }
    }

    protected function tagIf($attrs)
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";if(%1$s){o+="<%3$s>%2$s</%3$s>"; } o+="<%3$s>', $this->makeJsString($attrs->test), $this->getBody(), static::$JS_WRAPPER_TAG);
    }

    protected function tagElse()
    {
        return sprintf('</%2$s>";}else{o+="<%2$s>%s', $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagElseIf($attrs)
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";}else if(%1$s){o+="<%3$s>%2$s', $attrs->test, $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagWhile($attrs)
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";while(%1$s){o+="<%3$s>%2$s</%3$s>";}o+="<%3$s>', $attrs->test, $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagBind($attrs)
    {
        $this->requireAttributes($attrs, ['name']);

        $output = sprintf('<%1$s>' . $this->makeJsString($this->getBody()) . '</%1$s>', static::$JS_WRAPPER_TAG);

        preg_match_all('%<' . static::$JS_WRAPPER_TAG . '>(.*?)</' . static::$JS_WRAPPER_TAG . '>%', $output, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $m) {
                $output = str_replace('<' . static::$JS_WRAPPER_TAG . '>' . $m . '</' . static::$JS_WRAPPER_TAG . '>', addslashes($m), $output);
            }
        }

        $output = $this->replaceJsExpressions($output);

        if (app()->getDebugEnabled() === true) {
            $output = str_replace('o+=', "\no+=", $output);
            $output = preg_replace('/";(\}else\{|for|if]switch)/i', "\";\n$1", $output);
        }

        $data = isset($attrs->data) ? $attrs->data : 'null';
        $el = isset($attrs->el) ? $attrs->el : 'div';

        return sprintf('</%5$s>"; var guid = $p.utils.generateGuid(); var key="%1$s"; self.bindings[key]={}; self.bindings[key].guid = guid;  self.bindings[key].callback=function(d){ var id = this.guid; var o = "%4$s"; $("#" + id).html(o); }; self.bindings[key].data = %3$s; o += "<%2$s id=\""+ guid +"\"></%2$s>"; o+="<%5$s>',
            $attrs->name,
            $el,
            $data,
            $output,
            static::$JS_WRAPPER_TAG
        );
    }

    protected function tagEach($attrs)
    {
        $this->requireAttributes($attrs, ['in']);
        $row = (!isset($attrs->as)) ? 'row' : $attrs->as;
        $index = (!isset($attrs->index)) ? 'i' : $attrs->index;

        return sprintf('</%4$s>"; for(var %5$s=0;%5$s<%1$s.length;%5$s++){var %2$s=%1$s[%5$s]; o+="<%4$s>%3$s</%4$s>"; } o+="<%4$s>',
            $attrs->in,
            $row,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG,
            $index
        );
    }

    protected function tagFor($attrs)
    {
        $this->requireAttributes($attrs, ['limit', 'start', 'it']);

        return sprintf('</%5$s>";for(var %1$s=%2$s;%1$s<%3$s;%1$s++){o+="<%5$s>%4$s</%5$s>";}o+="<%5$s>',
            $attrs->it,
            $attrs->start,
            $attrs->limit,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG
        );
    }

    protected function tagBreak()
    {
        return sprintf('</%1$s>"; break; o+="<%1$s>', static::$JS_WRAPPER_TAG);
    }

    protected function tagCollect()
    {
        $output = array_merge(['<script>'], $this->containers, ['</script>',]);

        return join((app()->getDebugEnabled() === true) ? chr(10) : '', $output);
    }

}