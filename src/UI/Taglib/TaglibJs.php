<?php

namespace Pecee\UI\Taglib;

class TaglibJs extends Taglib
{

    protected string $namespace = '$p';
    protected array $templates = [];

    protected static string $JS_WRAPPER_TAG = '';
    protected static string $JS_EXPRESSION_START = '/js{/';
    protected static string $JS_WIDGET_EXPRESSION = '/\\$self(.*?)}/';

    protected function makeJsString(string $string): string
    {
        return preg_replace('/[\n\r\t]*|\s\s/', '', trim($string));
    }

    protected function handleInline(string $string): string
    {
        $string = str_replace(["\\'", '\\"'], ['\'', '"'], $string);
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

    protected function tagTemplate(object $attrs)
    {
        $this->requireAttributes($attrs, ['id']);

        $output = sprintf('$.%1$s = new %2$s.template(); $.%1$s.view = function(d,g){var self=this; var o="<%4$s>%3$s</%4$s>"; return o;};',
            $attrs->id,
            $this->namespace,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG
        );

        $matches = [];

        preg_match_all('%<' . static::$JS_WRAPPER_TAG . '>(.*?)</' . static::$JS_WRAPPER_TAG . '>%', $output, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $m) {
                $output = str_replace('<' . static::$JS_WRAPPER_TAG . '>' . $m . '</' . static::$JS_WRAPPER_TAG . '>', addslashes($this->renderPhp($m)), $output);
            }
        }

        $this->templates[$attrs->id] = $this->replaceJsExpressions($output);

        if (app()->getDebugEnabled() === true) {
            $this->templates[$attrs->id] = str_replace('o+=', "\no+=", $this->templates[$attrs->id]);
            $this->templates[$attrs->id] = preg_replace('/";(\}else\{|for|if]switch)/i', "\";\n$1", $this->templates[$attrs->id]);
        }
    }

    protected function tagIf(object $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";if(%1$s){o+="<%3$s>%2$s</%3$s>"; } o+="<%3$s>', $this->makeJsString($attrs->test), $this->getBody(), static::$JS_WRAPPER_TAG);
    }

    protected function tagElse(): string
    {
        return sprintf('</%2$s>";}else{o+="<%2$s>%s', $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagElseIf(object $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";}else if(%1$s){o+="<%3$s>%2$s', $attrs->test, $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagWhile(object $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";while(%1$s){o+="<%3$s>%2$s</%3$s>";}o+="<%3$s>', $attrs->test, $this->makeJsString($this->getBody()), static::$JS_WRAPPER_TAG);
    }

    protected function tagBind(object $attrs): string
    {
        $this->requireAttributes($attrs, ['name']);

        $output = sprintf('<%1$s>' . $this->makeJsString($this->getBody()) . '</%1$s>', static::$JS_WRAPPER_TAG);

        preg_match_all('%<' . static::$JS_WRAPPER_TAG . '>(.*?)</' . static::$JS_WRAPPER_TAG . '>%', $output, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $m) {
                $output = str_replace('<' . static::$JS_WRAPPER_TAG . '>' . $m . '</' . static::$JS_WRAPPER_TAG . '>', addslashes($this->renderPhp($m)), $output);
            }
        }

        $output = $this->replaceJsExpressions($output);

        if (app()->getDebugEnabled() === true) {
            $output = str_replace('o+=', "\no+=", $output);
            $output = preg_replace('/";(\}else\{|for|if]switch)/i', "\";\n$1", $output);
        }

        $data = $attrs->data ?? 'null';
        $el = $attrs->el ?? 'div';

        return sprintf('</%6$s>"; var guid = %1$s.utils.generateGuid(); var key="%2$s"; self.bindings[key]={}; self.bindings[key].guid = guid;  self.bindings[key].callback=function(d){ var id = this.guid; var o = "%5$s"; $("#" + id).html(o); self.widget.trigger("render") }; self.bindings[key].data = %4$s; o += "<%3$s id=\""+ guid +"\"></%3$s>"; o+="<%6$s>',
            $this->namespace,
            $attrs->name,
            $el,
            $data,
            $output,
            static::$JS_WRAPPER_TAG
        );
    }

    protected function tagEach(object $attrs): string
    {
        $this->requireAttributes($attrs, ['in']);
        $key = $attrs->key ?? 'key';
        $row = $attrs->as ?? 'row';

        return sprintf('</%5$s>"; for(var %1$s in %2$s){ var %3$s = %2$s[%1$s]; o+="<%5$s>%4$s</%5$s>"; } o+="<%5$s>',
            $key,
            $attrs->in,
            $row,
            $this->makeJsString($this->getBody()),
            static::$JS_WRAPPER_TAG,
        );
    }

    protected function tagFor(object $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        return sprintf('</%3$s>";for(%1$s){o+="<%3$s>%2$s</%3$s>";}o+="<%3$s>',
            $attrs->test,
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
        $output = array_merge(['<script>'], $this->templates, ['</script>']);

        return join((app()->getDebugEnabled() === true) ? chr(10) : '', $output);
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

}