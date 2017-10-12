<?php

class SReCaptcha extends CInputWidget
{
    const JS_API_URL = 'https://www.google.com/recaptcha/api.js';
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';
    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';
    const SIZE_NORMAL = 'normal';
    const SIZE_COMPACT = 'compact';

    /** @var string Your sitekey. */
    public $siteKey;

    /** @var string Your secret. */
    public $secret;

    /** @var string The color theme of the widget. [[THEME_LIGHT]] (default) or [[THEME_DARK]] */
    public $theme;

    /** @var string The type of CAPTCHA to serve. [[TYPE_IMAGE]] (default) or [[TYPE_AUDIO]] */
    public $type;

    /** @var string The size of the widget. [[SIZE_NORMAL]] (default) or [[SIZE_COMPACT]] */
    public $size;

    /** @var int The tabindex of the widget */
    public $tabindex;

    /** @var string Your JS callback function that's executed when the user submits a successful CAPTCHA response. */
    public $jsCallback;

    /**
     * @var string Your JS callback function that's executed when the recaptcha response expires and the user
     * needs to solve a new CAPTCHA.
     */
    public $jsExpiredCallback;

    /** @var array Additional html widget options, such as `class`. */
    public $widgetOptions = [];

    /** @var string Language */
    public $lang = 'en';

    /** @var array Multi usage for ajax forms. If empty array that mean single form usage */
    public $render = [];


    /**
     * Render ReCaptcha 2 or @throws CException
     */
    public function run()
    {
        if (empty($this->siteKey)) {
            if (isset(Yii::app()->reCaptcha)) {
                /** @var SReCaptcha $reCaptcha */
                $reCaptcha = Yii::app()->reCaptcha;
                if (!empty($reCaptcha->siteKey)) {
                    $this->siteKey = $reCaptcha->siteKey;
                } else {
                    throw new CException('Required `siteKey` param isn\'t set.');
                }
            } else {
                throw new CException('Required `siteKey` param isn\'t set.');
            }
        }

        if (empty($this->render)) {
            if (!isset($this->name))
                throw new CException('Required `name` param isn\'t set.');
        }

        $this->customFieldPrepare();

        /* @var $cs EClientScript */

        $cs = Yii::app()->clientScript;
        if (empty($this->render)) {
            $cs->registerScriptFile(self::JS_API_URL . '?hl=' . $this->getLanguageSuffix(), CClientScript::POS_HEAD);
        } else {
            $query = ['onload' => 'onloadCallback', 'render' => 'explicit', 'hl' => $this->getLanguageSuffix()];
            $cs->registerScriptFile(
                self::JS_API_URL . '?' . http_build_query($query),
                CClientScript::POS_HEAD,
                ['async' => 'async', 'defer' => 'defer']
            );
        }

        if (empty($this->render)) {

            $divOptions = [
                'class' => 'g-recaptcha',
                'data-sitekey' => $this->siteKey
            ];

            if (!empty($this->jsCallback)) {
                $divOptions['data-callback'] = $this->jsCallback;
            }
            if (!empty($this->jsExpiredCallback)) {
                $divOptions['data-expired-callback'] = $this->jsExpiredCallback;
            }
            if (!empty($this->theme)) {
                $divOptions['data-theme'] = $this->theme;
            }
            if (!empty($this->type)) {
                $divOptions['data-type'] = $this->type;
            }
            if (!empty($this->size)) {
                $divOptions['data-size'] = $this->size;
            }
            if (!empty($this->tabindex)) {
                $divOptions['data-tabindex'] = $this->tabindex;
            }
            if (isset($this->widgetOptions['class'])) {
                $divOptions['class'] = "{$divOptions['class']} {$this->widgetOptions['class']}";
            }

            $divOptions = $divOptions + $this->widgetOptions;
            echo CHtml::tag('div', $divOptions, " ");
            if (isset($this->model) && !isset($this->attribute))
                $this->attribute = $this->name;

            if ($this->hasModel()) {
                echo CHtml::error($this->model, $this->attribute);
            }
        }
    }

    /**
     * @return bool|string
     */
    protected function getLanguageSuffix()
    {
        $currentAppLanguage = $this->lang;
        $langsExceptions = ['zh-CN', 'zh-TW', 'zh-TW'];
        if (strpos($currentAppLanguage, '-') === false) {
            return $currentAppLanguage;
        }
        if (in_array($currentAppLanguage, $langsExceptions)) {
            return $currentAppLanguage;
        } else {
            return substr($currentAppLanguage, 0, strpos($currentAppLanguage, '-'));
        }
    }

    /**
     * Render JS scripts for
     */
    protected function customFieldPrepare()
    {

        /* @var $cs EClientScript */
        $cs = Yii::app()->clientScript;

        if (empty($this->render)) {
            if ($this->hasModel()) {
                $inputName = CHtml::activeName($this->model, $this->attribute);
                $inputId = CHtml::activeId($this->model, $this->attribute);
            } else {
                $inputName = $this->name;
                $inputId = 'recaptcha-' . $this->name;
            }
            if (empty($this->jsCallback)) {
                $jsCode = "var recaptchaCallback = function(response){jQuery('#{$inputId}').val(response);};";
            } else {
                $jsCode = "var recaptchaCallback = function(response){jQuery('#{$inputId}').val(response); {$this->jsCallback}(response);};";
            }
            $this->jsCallback = 'recaptchaCallback';
            if (empty($this->jsExpiredCallback)) {
                $jsExpCode = "var recaptchaExpiredCallback = function(){jQuery('#{$inputId}').val('');};";
            } else {
                $jsExpCode = "var recaptchaExpiredCallback = function(){jQuery('#{$inputId}').val(''); {$this->jsExpiredCallback}};";
            }
            $this->jsExpiredCallback = 'recaptchaExpiredCallback';


            $cs->registerScript(get_class($this) . '_options', $jsCode, CClientScript::POS_BEGIN);
            $cs->registerScript(get_class($this) . '_options_2', $jsExpCode, CClientScript::POS_BEGIN);

            echo CHtml::hiddenField($inputName, null, ['id' => $inputId]);

        } else {
            $vars = "";
            $widgets = "";
            $callbacks = "";
            $expCallbacks = "";
            $i = 1;
            foreach ($this->render as $variable => $params) {
                $vars .= "var widget$i;";
                $params['sitekey'] = $this->siteKey;
                if (isset($params['callback'])) {
                    $callbacks .= 'var verifyCallback' . $i . ' = function(response){' . $params["callback"] . '};';
                    $params['callback'] = 'verifyCallback' . $i;
                }
                if (isset($params['expired-callback'])) {
                    $expCallbacks .= 'var expCallback' . $i . ' = function(response){' . $params["expired-callback"] . '};';
                    $params['expired-callback'] = 'expCallback' . $i;
                }

                $options = CJavaScript::encode($params, true);
                $widgets .= "widget$i = grecaptcha.render('$variable', $options);";
                $i++;
            }

            $script = <<<JS
            
                $callbacks
                $expCallbacks
                $vars
                var onloadCallback = function() {
                    $widgets                    
                }
JS;
            $cs->registerScript(get_class($this) . '_explicit', $script, CClientScript::POS_BEGIN);
        }
    }
}