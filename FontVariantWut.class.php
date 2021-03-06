<?php
namespace FontVariantWut;

/*
    Browser font-variant support tester by Chris Lewis https://chrislewis.codes/
    Brilliant test font by David Jonathan Ross https://djr.com/
    
    Licensed under the MIT License. Code available on GitHub:
    https://github.com/chrissam42/font-variant-wut
*/

class FontVariantWut {
    public $allfeatures;
    public $reportsFile = "reports.csv";
    private $whichfont = "tags";

    public $tests = array(
        "font-variant-alternates" => array(
            'values' => array('normal', 'historical-forms', 'stylistic(test)', 'styleset(test)', 'character-variant(test)', 'swash(t1st)', 'swash(t2st)', 'ornaments(test)', 'annotation(test)'),
            'features' => '.alt|ss01|cv01|swsh|cswh|hist|ornm|rclt',
        ),
        "font-variant-caps" => array(
            'values' => array('normal', 'small-caps', 'all-small-caps', 'petite-caps', 'all-petite-caps', 'unicase', 'titling-caps'),
            'features' => 'c2pc|c2sc|pcap|smcp|unic|titl|case',
        ),
        "font-variant-ligatures" => array(
            'values' => array('none', 'common-ligatures', 'discretionary-ligatures', 'historical-ligatures', 'contextual'),
            'features' => '.lig|liga',
        ),
        "font-variant-numeric" => array(
            'values' => array('normal', 'lining-nums', 'oldstyle-nums', 'proportional-nums', 'tabular-nums', 'diagonal-fractions', 'stacked-fractions', 'ordinal', 'slashed-zero'),
            'features' => '.num|frac|afrc|zero|ordn',
        ),
        "font-variant-position" => array(
            'values' => array('normal', 'sub', 'super'),
            'features' => 'subs|sups',
        ),
    );
    
    public function __construct() {
        preg_match_all('/\bfeature\s+(\w{4})\s*\{/', file_get_contents(dirname(__FILE__) . '/fonts/OTTestFont-Regular.ufo/features.fea'), $m, PREG_PATTERN_ORDER);
        $this->allfeatures = $m[1];
        sort($this->allfeatures);
    }

    public function atFontFace($base64=false) {
        $url = "fonts/{$this->whichfont}.woff";
        if ($base64) {
            $url = "data:application/font-woff;base64," . base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/' . $url));
        }
        return <<<EOF
        @font-face {
            src: url("$url");
            font-family: "Font Variant Test";
            font-weight: normal;
            font-style: normal;
        }
        
        @font-feature-values Font Variant Test { 
            @stylistic { test: 1 } 
            @styleset { test: 1 } 
            @character-variant { test: 1 } 
            @swash { t1st: 1; t2st: 2 } 
            @ornaments { test: 1 } 
            @annotation { test: 1 } 
        }
EOF;
    }

    public function useBlockFont() {
        $this->whichfont = "block";
    }
    
    public function useTagFont() {
        $this->whichfont = "tags";
    }

    public function ruleColor($rule, $format='hex') {
        $hex = substr(md5($rule), 0, 6);
        switch ($format) {
            case 'rgb':
                return array(
                    hexdec(substr($hex, 0, 2)),
                    hexdec(substr($hex, 2, 2)),
                    hexdec(substr($hex, 4, 2)),
                );
            default:
                return "#$hex";
        }
    }

    public function featuresForRule($rule) {
        $test = $this->tests[$rule]['features'];
        if (is_array($test)) {
            return $test;
        } else {
            $result = array();
            foreach ($this->allfeatures as $f) {
                if (preg_match('/' . $test . '/', $f)) {
                    $result[] = $f;
                }
            }
            return $result;
        }
    }

    public function testString($rule) {
        $result = $this->featuresForRule($rule);
        if ($this->whichfont === "block") {
            return implode("", $result);
        } else {
            return "<span>" . implode("</span><span>", $result) . "</span>";
        }
    }
    
    public function browser() {
        $ua = $_SERVER['HTTP_USER_AGENT'];
        
        $b = "Unknown";
        $p = "Unknown";
        $v = "";
        if (preg_match('/MSIE (\d+(?:\.\d+)?)/', $ua, $m)) {
            $p = "Windows";
            $b = "Internet Explorer";
            $v = $m[1];
        } else if (preg_match(':Trident/\d+:', $ua) and preg_match('/v:(\d+(?:\.\d+))/', $ua, $m)) {
            $p = "Windows";
            $b = "Internet Explorer";
            $v = $m[1];
        } else {
            foreach (explode('|', 'FBAV|Weibo|CriOS|Edge|Firefox|Thunderbird|Chrome|Chromium|Safari|OPR|Opera') as $try) {
                if (preg_match('~' . $try . '/(\d+(?:\.\d+))~', $ua, $m)) {
                    $b = $try;
                    $v = $m[1];
                    switch ($b) {
                        case 'OPR': $b = 'Opera'; break;
                        case 'CriOS': $p = "iOS"; $b = 'Chrome'; break;
                        case 'FBAV': $b = 'Facebook'; break;
                    }
                    break;
                }
            }
        }

        if ($b === 'Safari' and preg_match(':Version/([\d\.]+):', $ua, $m)) {
            $v = $m[1];
        }
        
        if ($p === "Unknown") {
            if (preg_match('/Windows Phone/', $ua)) {
                $p = 'Windows Phone';
            } else if (preg_match('/Android/', $ua)) {
                $p = 'Android';
            } else if (preg_match('/CrOS/', $ua)) {
                $p = 'Chrome OS';
            } else if (preg_match('/Windows/', $ua)) {
                $p = 'Windows';
            } else if (preg_match('/iPad|iPhone OS/', $ua)) {
                $p = 'iOS';
            } else if (preg_match('/Mac OS X/', $ua)) {
                $p = 'Mac';
            } else if (preg_match('/Linux/', $ua)) {
                $p = 'Linux';
            };
        }
        
        if ($p === 'Android' and $b === 'Safari') {
            $b = 'Browser';
        };
        
        return array(
            'platform' => $p, 
            'browser' => $b, 
            'version' => $v,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'],
        );
    }
}
