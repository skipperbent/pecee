<?php

namespace Pecee\UI\Taglib;

use Pecee\UI\Phtml\Phtml;

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
        return preg_replace_callback('/js-(\w+)="?((?:.(?!"\{\}?\s+\S+=|\s*\/?[>"]))+.)"?/is', function ($matches) {

            $event = $matches[1];
            $callback = $matches[2];

            return sprintf('on%1$s="return </%2$s>"; o+= w.t(this.id, function(e) { %3$s }) + "<%2$s>"',
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

        $output = sprintf('$.%1$s = new %4$s.template(); $.%1$s.view = function(_d,g,w){ let d=_d; var o="<%3$s>%2$s</%3$s>"; return o;};',
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

    /**
     * Render template based on id
     *
     * @param \stdClass $attrs
     * @return string
     * @throws \ErrorException
     */
    protected function tagTemplateRender(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['id']);

        $data = $attrs->data ?? 'd';
        $guid = $attrs->guid ?? 'g';
        $widget = $attrs->widget ?? 'w';

        $output = "o += $.{$attrs->id}.view($data, $guid, $widget);";
        return sprintf('</%1$s>"; %2$s o+="<%1$s>', static::$JS_WRAPPER_TAG, $output);
    }

    protected function tagSwitch(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);
        return sprintf('</%3$s>";switch(%1$s){ %2$s } o+="<%3$s>', $this->makeJsString($attrs->test), $this->getBody(), static::$JS_WRAPPER_TAG);
    }

    protected function tagCase(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['test']);

        $break = $attrs->break ?? 'true';
        $break = (strtolower($break) === 'true') ? 'break;' : '';
        $test = (stripos($attrs->test, ',') !== false) ? explode(',', $attrs->test) : [$attrs->test];
        $output = '';

        foreach ($test as $t) {
            $t = trim($t);
            $output .= (($t === 'default') ? 'default' : 'case ' . $t) . ': ';
        }

        return sprintf(' %1$s { o+="<%3$s>%2$s</%3$s>"; %4$s }', $this->makeJsString($output), $this->getBody(), static::$JS_WRAPPER_TAG, $break);
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


    /**
     * @param \stdClass $attrs
     * @return string
     * @deprecated Use tagView instead
     */
    protected function tagBind(\stdClass $attrs): string
    {
        return $this->tagView($attrs);
    }

    /**
     * Triggers js callback without returning html.
     * @param \stdClass $attrs
     * @return string
     * @throws \ErrorException
     */
    protected function tagTrigger(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['callback']);

        return sprintf('</%1$s>"; %2$s o+="<%1$s>',
            static::$JS_WRAPPER_TAG,
            $attrs->callback,
        );
    }

    /**
     * Render inner-view that can be triggered by using widget.trigger()
     *
     * @param \stdClass $attrs
     * @return string
     * @throws \ErrorException
     */
    protected function tagView(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['name']);

        $tag = static::$JS_WRAPPER_TAG;
        $output = "<$tag>{$this->makeJsString($this->getBody())}</$tag>";

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
        $elStartTag = $attrs->el ?? 'div';
        $elEndTag = $elStartTag;
        $as = $attrs->as ?? 'd';

        if (isset($attrs->style)) {
            $elStartTag .= ' style=\"' . $attrs->style . '\"';
        }

        if (isset($attrs->class)) {
            $elStartTag .= ' class=\"' . $attrs->class . '\"';
        }

        $hidden = (isset($attrs->hidden) && strtolower($attrs->hidden) === 'true') ? 'true' : 'false';
        $morph = (isset($attrs->morph) && strtolower($attrs->morph) === 'false') ? 'false' : 'true';
        $hiddenHtml = ($hidden === 'true') ? '' : 'o += "<%2$s id=\"" + w.guid + "_%1$s\"></%3$s>";';
        $morphHtml = ($morph === 'false') ? '$("#" + this.id).html(o);' : '$("#" + this.id).morphdom($("#" + this.id).clone(true).html(o));';

        return sprintf('</%6$s>"; w.template.binding({id: w.guid + "_%1$s", callback: function(%7$s){ var o="%5$s"; ' . $morphHtml . ' return o; }, data: %4$s, hidden: %8$s}); ' . $hiddenHtml . ' o+="<%6$s>',
            $attrs->name,
            $elStartTag,
            $elEndTag,
            $data,
            $output,
            static::$JS_WRAPPER_TAG,
            $as,
            $hidden,
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
        $this->containers = [];
        return join((app()->getDebugEnabled() === true) ? chr(10) : '', $output);
    }

    /**
     * Includes file in page.
     *
     * @param \stdClass $attrs
     * @return string
     * @throws \ErrorException
     * @throws \Pecee\UI\Phtml\PhtmlException
     */
    protected function tagInclude(\stdClass $attrs): string
    {
        $this->requireAttributes($attrs, ['file']);

        $content = file_get_contents('views/snippets/' . $attrs->file, FILE_USE_INCLUDE_PATH);
        if ($content !== false) {
            return (new Phtml())->read($content)->toPHP();
        }

        return '';
    }

    /**
     * Alias for include
     *
     * @param \stdClass $attrs
     * @return string
     * @throws \ErrorException
     * @throws \Pecee\UI\Phtml\PhtmlException
     */
    protected function tagSnippet(\stdClass $attrs): string
    {
        return $this->tagInclude($attrs);
    }

}