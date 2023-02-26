<?php

namespace Pecee\UI\Taglib;

class TaglibJs extends Taglib
{

    protected array $containers = [];

    protected static string $JS_WRAPPER_TAG = '';
    protected static string $JS_EXPRESSION_START = '/js{/';
    protected static string $JS_WIDGET_EXPRESSION = '/\\$self(.*?)}/';

    protected string $namespace = '$p';
    protected int $indexCount = 0;

    protected function getJsWrapperMatchRegEx(): string
    {
        return '%<' . static::$JS_WRAPPER_TAG . '>(.*?)</' . static::$JS_WRAPPER_TAG . '>%';
    }

    /**
     * Enables trigger tags within the template, for example:
     * js-click="d.select(item.id);"
     * js-[event]="callback"
     *
     * d is a variable that will always contain the current widget.
     *
     * @param string $string
     * @return string
     */
    protected function parseJsTriggers(string $string): string
    {
        // Replace js bindings
        return preg_replace_callback('/js-(\w+)="?((?:.(?!"?\s+\S+=|\s*\/?[>"]))+.)"?/is', function ($matches) {

            $event = $matches[1];
            $callback = $matches[2];

            return sprintf('on%1$s="return trigger_</%2$s>"; let g=w.utils.generateGuid(); window["trigger_" + g]=function() { %3$s }; o += g + "();<%2$s>"</%2$s>"; o+="<%2$s>',
                $event,
                static::$JS_WRAPPER_TAG,
                $callback
            );
        }, $string);
    }

    protected function makeJsString(string $string): string
    {
        $string = $this->parseJsTriggers($string);
        return preg_replace('/[\n\r\t]*|\s\s/', '', trim($string));
    }

    protected function handleInline(string $string): string
    {
        $string = str_replace(["\\'", '\\"'], array('\'', '"'), $string);
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

    protected function replaceJsExpressions(string $string): string
    {
        $fixedExpressions = [];
        $expressionMatches = [];
        // Change all widget expressions
        $string = preg_replace(static::$JS_WIDGET_EXPRESSION, $this->namespace . '.getWidget(\'"+g+"\')$1', $string);
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
                    'js' => substr($string, $searchOffset, $end - $searchOffset),
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

    protected function tagTemplate(\stdClass $attrs): void
    {
        $this->requireAttributes($attrs, ['id']);

        $output = sprintf('$.%1$s = new %4$s.template(); $.%1$s.view = function(_d,g,w){ let d=_d; var self=this; var o="<%3$s>%2$s</%3$s>"; return o;};',
            $attrs->id,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG,
            $this->namespace
        );

        $matches = [];

        preg_match_all($this->getJsWrapperMatchRegEx(), $output, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $m) {
                $output = str_replace('<' . static::$JS_WRAPPER_TAG . '>' . $m . '</' . static::$JS_WRAPPER_TAG . '>', addslashes($m), $output);
            }
        }

        $this->containers[$attrs->id] = $this->replaceJsExpressions($output);

        if (app()->getDebugEnabled() === true) {
            $this->containers[$attrs->id] = str_replace('o+=', "\no+=", $this->containers[$attrs->id]);
            $this->containers[$attrs->id] = preg_replace('/";(}else\{|for|if]switch)/i', "\";\n$1", $this->containers[$attrs->id]);
        }
    }

    protected function tagIf(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";if(%1$s){o+="<%3$s>%2$s</%3$s>"; } o+="<%3$s>', $this->makeJsString($attrs->test), $this->getBody(), static::$JS_WRAPPER_TAG);
    }

    protected function tagElse(): string
    {
        return sprintf('</%2$s>";}else{o+="<%2$s>%s', $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagElseIf(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";}else if(%1$s){o+="<%3$s>%2$s', $attrs->test, $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagWhile(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";while(%1$s){o+="<%3$s>%2$s</%3$s>";}o+="<%3$s>', $attrs->test, $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagBind(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['name']);

        $output = sprintf('<%1$s>' . $this->makeJsString($this->getBody()) . '</%1$s>', static::$JS_WRAPPER_TAG);

        preg_match_all($this->getJsWrapperMatchRegEx(), $output, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $m) {
                $output = str_replace('<' . static::$JS_WRAPPER_TAG . '>' . $m . '</' . static::$JS_WRAPPER_TAG . '>', addslashes($m), $output);
            }
        }

        $output = $this->replaceJsExpressions($output);

        if (app()->getDebugEnabled() === true) {
            $output = str_replace('o+=', "\no+=", $output);
            $output = preg_replace('/";(}else\{|for|if]switch)/i', "\";\n$1", $output);
        }

        $data = $attrs->data ?? 'null';
        $el = $attrs->el ?? 'div';

        return sprintf('</%5$s>"; var guid=%6$s; var key="%1$s"; self.bindings[key]={}; self.bindings[key].guid=guid; self.bindings[key].callback=function(d){ var id = this.guid; var o = "%4$s"; $("#" + id).html(o); }; self.bindings[key].data = %3$s; o += "<%2$s id=\""+ guid +"\"></%2$s>"; o+="<%5$s>',
            $attrs->name,
            $el,
            $data,
            $output,
            static::$JS_WRAPPER_TAG,
            uniqid()
        );
    }

    protected function tagEach(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['in']);
        $row = (!isset($attrs->as)) ? 'row' : $attrs->as;
        $index = (!isset($attrs->index)) ? 'i' : $attrs->index;

        if (isset($attrs->index) === false && $this->indexCount > 0) {
            $index .= '_' . $this->indexCount;
        }

        $this->indexCount++;

        return sprintf('</%4$s>"; for(let %5$s=0;%5$s<%1$s.length;%5$s++){let %2$s=%1$s[%5$s]; o+="<%4$s>%3$s</%4$s>"; } o+="<%4$s>',
            $attrs->in,
            $row,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG,
            $index
        );
    }

    protected function tagFor(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['limit', 'start', 'i']);

        return sprintf('</%5$s>";for(let %1$s=%2$s;%1$s<%3$s;%1$s++){o+="<%5$s>%4$s</%5$s>";}o+="<%5$s>',
            $attrs->it,
            $attrs->start,
            $attrs->limit,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG
        );
    }

    /**
     * Makes function - makes it possible to render elements multiple times within the template.
     *
     * @param \stdClass $attrs
     * @return string
     * @throws \ErrorException
     */
    protected function tagFunction(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['name', 'parameters']);
        return sprintf('</%4$s>"; function %1$s(%2$s){var o="<%4$s>%3$s</%4$s>"; return o; } o+="<%4$s>',
            $attrs->name,
            $attrs->parameters,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG
        );
    }

    protected function tagBreak(): string
    {
        return sprintf('</%1$s>"; break; o+="<%1$s>', static::$JS_WRAPPER_TAG);
    }

    protected function tagCollect(): string
    {
        $output = array_merge(['<script>'], $this->containers, ['</script>']);

        return join((app()->getDebugEnabled() === true) ? chr(10) : '', $output);
    }

}