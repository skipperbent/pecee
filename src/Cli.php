<?php
namespace Pecee;

class Cli
{

    const COLOR_GREY = 'grey';
    const COLOR_RED = 'red';
    const COLOR_GREEN = 'green';
    const COLOR_YELLOW = 'yellow';
    const COLOR_BLUE = 'blue';
    const COLOR_MAGENTA = 'magenta';
    const COLOR_CYAN = 'cyan';
    const COLOR_WHITE = 'white';

    public static $COLORS = [self::COLOR_GREY, self::COLOR_RED, self::COLOR_GREEN, self::COLOR_YELLOW, self::COLOR_BLUE, self::COLOR_MAGENTA, self::COLOR_CYAN, self::COLOR_WHITE];

    const BG_GREY = 'bg_grey';
    const BG_RED = 'bg_red';
    const BG_GREEN = 'bg_green';
    const BG_YELLOW = 'bg_yellow';
    const BG_BLUE = 'bg_blue';
    const BG_MAGENTA = 'bg_magenta';
    const BG_CYAN = 'bg_cyan';
    const BG_WHITE = 'bg_white';

    public static $BACKGROUNDS = [self::BG_GREY, self::BG_RED, self::BG_GREEN, self::BG_YELLOW, self::BG_BLUE, self::BG_MAGENTA, self::BG_CYAN, self::BG_WHITE];

    const STYLE_BOLD = 'bold';
    const STYLE_DARK = 'dark';
    const STYLE_UNDERLINE = 'underline';
    const STYLE_BLINK = 'blink';
    const STYLE_REVERSE = 'reverse';
    const STYLE_CONCEALED = 'concealed';

    public static $STYLES = [self::STYLE_BOLD, self::STYLE_DARK, self::STYLE_UNDERLINE, self::STYLE_BLINK, self::STYLE_REVERSE, self::STYLE_CONCEALED];

    const STR_RESET = "\033[0m";
    const STR_FORMAT = "\033[%dm%s";
    const STR_SOUND = "\x07";
    const STR_CLEARLINE = "\r";
    const STR_CURSOR_FIRST = "\033[0;0H";

    /**
     * Returns text formatted with color, background color and/or attributes.
     * @param array $args
     * @return string
     */
    protected function createColor(array $args)
    {
        $options = $this->getOptions();
        $text = array_shift($args);

        foreach ($args as $_arg) {
            foreach ((array)$_arg as $arg) {
                if (isset($options[$arg])) {
                    $text = sprintf(self::STR_FORMAT, $options[$arg], $text);
                } else {
                    $this->writeLine("Invalid argument: " . $arg);
                    $this->write(self::STR_SOUND);
                    exit(1);
                }
            }
        }

        return $text . self::STR_RESET;
    }

    /**
     * Echos text formatted with color, background color and/or attributes.
     * Adds a new line at the end.
     * @param string|null $text
     * @param array|null $args
     */
    public function writeLine($text = null, $args = null)
    {
        $args = func_get_args();
        $args[0] = (is_null($text)) ? chr(10) : $text . chr(10);
        echo call_user_func_array([$this, 'write'], [$args]);
    }

    /**
     * Echos text formatted with color, background color and/or attributes.
     * @param string|null $text
     * @param array|null $args
     */
    public function write($text, $args = null)
    {
        $args = func_get_args();
        echo call_user_func_array([$this, 'createColor'], [$args]);
    }

    public function get($text, $args = null)
    {
        $args = func_get_args();

        return call_user_func_array([$this, 'createColor'], [$args]);
    }

    public function getCols()
    {
        return exec('tput cols');
    }

    public function getLines()
    {
        return exec('tput lines');
    }

    public function clear()
    {
        print chr(27) . "[2J" . chr(27) . "[;H";
    }

    /**
     * Helper function that builds an array of all the available text colors,
     * background colors and text attributes and their corresponding terminal codes.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = array_merge(
        // Foreground colors.
            array_combine(
                [self::COLOR_GREY, self::COLOR_RED, self::COLOR_GREEN, self::COLOR_YELLOW, self::COLOR_BLUE,
                    self::COLOR_MAGENTA, self::COLOR_CYAN, self::COLOR_WHITE],
                range(30, 37)
            ),
            // Background colors.
            array_combine(
                [self::BG_GREY, self::BG_RED, self::BG_GREEN, self::BG_YELLOW, self::BG_BLUE,
                    self::BG_MAGENTA, self::BG_CYAN, self::BG_WHITE],
                range(40, 47)
            ),
            // Text style attributes. 3 and 6 is not used.
            array_combine(
                [self::STYLE_BOLD, self::STYLE_DARK, '', self::STYLE_UNDERLINE, self::STYLE_BLINK, '',
                    self::STYLE_REVERSE, self::STYLE_CONCEALED],
                range(1, 8)
            )
        );
        unset($options['']);

        return $options;
    }

}