<?php

class SReCaptchaValidator extends CValidator
{
    const GRABBER_PHP = 1;
    const GRABBER_CURL = 2;
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const CAPTCHA_RESPONSE_FIELD = 'g-recaptcha-response';

    /** @var boolean Whether to skip this validator if the input is empty. */
    public $skipOnEmpty = false;

    /** @var string The shared key between your site and ReCAPTCHA. */
    public $secret;

    /**
     * @var int Choose your grabber for getting JSON,
     * self::GRABBER_PHP = file_get_contents, self::GRABBER_CURL = CURL
     */
    public $grabberType = self::GRABBER_PHP;

    /** @var string */
    public $uncheckedMessage;

    /**
     * @param CModel $object
     * @param string $attribute
     * @throws CException
     */
    protected function validateAttribute($object, $attribute)
    {
        if (empty($this->secret)) {
            if (isset(Yii::app()->reCaptcha)) {
                /** @var SReCaptcha $reCaptcha */
                $reCaptcha = Yii::app()->reCaptcha;
                if (!empty($reCaptcha->secret)) {
                    $this->secret = $reCaptcha->secret;
                } else {
                    throw new CException('Required `secret` param isn\'t set.');
                }
            } else {
                throw new CException('Required `secret` param isn\'t set.');
            }
        }

        if (empty($value)) {
            if (!($value = Yii::app()->request->getPost(self::CAPTCHA_RESPONSE_FIELD))) {
                $message = $this->message !== null ? $this->message : Yii::t('yii', 'The verification code is incorrect.');
                $this->addError($object, $attribute, $message);
            }
        }

        $request = self::SITE_VERIFY_URL . '?' . http_build_query([
                'secret' => $this->secret,
                'response' => $value,
                'remoteip' => Yii::app()->request->userHostAddress,
            ]);

        $response = $this->getResponse($request);

        if (!isset($response['success']) || !$response['success']) {
            $message = $this->message !== null ? $this->message : Yii::t('yii', 'The verification code is incorrect.');
            $this->addError($object, $attribute, $message);
        }
    }

    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    protected function getResponse($request)
    {
        if ($this->grabberType === self::GRABBER_PHP) {
            $response = @file_get_contents($request);
            if ($response === false) {
                throw new Exception('Unable connection to the captcha server.');
            }
        } else {
            $options = [
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => '',
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_MAXREDIRS => 10,
            ];
            $ch = curl_init($request);
            curl_setopt_array($ch, $options);
            $content = curl_exec($ch);
            $errno = curl_errno($ch);
            $errmsg = curl_error($ch);
            $header = curl_getinfo($ch);
            curl_close($ch);
            $header['errno'] = $errno;
            $header['errmsg'] = $errmsg;
            $response = $content;
            if ($header['errno'] !== 0) {
                throw new Exception(
                    'Unable connection to the captcha server. Curl error #' . $header['errno'] . ' ' . $header['errmsg']
                );
            }
        }
        return CJSON::decode($response, true);
    }
}