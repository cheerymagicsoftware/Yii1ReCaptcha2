**Google reCAPTCHA V2 widget for Yii 1**
================================
Installation
------------
* [Get API key from Google](https://www.google.com/recaptcha/admin#createsite).
* Copy Source Folder to Extensions   
* Set Public Key (siteKey) and Private Key (secret) in `params` or `components` 
* Add SReCaptchaValidator in your model:

```php
public $reCaptcha;
//...

public function rules()
{
  return [
      // ...
      // secret is required
      ['reCaptcha', 'application.extensions.reCaptcha2.SReCaptchaValidator', 'secret' => Yii::app()->params['reCaptcha2PrivateKey'],'message' => 'The verification code is incorrect.']
      // ...
  ];
}
 ```

Usage
------------
* If you want render ReCaptcha use simple code in your view file:

```php
Yii::import('application.extensions.reCaptcha2.SReCaptcha');
$this->widget('SReCaptcha',
  [
        'name' => 'reCaptcha', //is requred
        'siteKey' => Yii::app()->params['reCaptcha2PublicKey'], //is requred
        'widgetOptions' => ['class' => 'col-lg-3'],
        'theme' => SReCaptcha::THEME_DARK,
        'size' => SReCaptcha::SIZE_COMPACT,
        'jsCallback' => 'console.log("reCaptcha is ready!");console.log(response);',
        'jsExpiredCallback' => 'console.log("reCaptcha is expired!");'
  ]
);
```

* If you want render ReCaptcha in active form:

```php
$form->widget('application.extensions.reCaptcha2.SReCaptcha',
    [
        'name' => 'reCaptcha', //is requred
        'siteKey' => Yii::app()->params['reCaptcha2PublicKey'], //is requred
        'model' => $model,
        //'attribute' => 'reCaptcha' //if we use model name equal attribute or customize attribute
    ]
); ?>
```

* If you use several forms or ajax and need multi render ReCaptchas:

```html
<div id="reCaptchaLogin"></div>
<div id="reCaptchaReg"></div>
```
```php
$this->widget('application.extensions.reCaptcha2.SReCaptcha',
    [
        'siteKey' => Yii::app()->params['reCaptcha2PublicKey'],
        'render' => [
            'reCaptchaLogin' => [
                'callback' => 'console.log("#reCaptchaLogin is ready!");',
                'theme' => 'dark',
                'expired-callback' => 'console.log("#reCaptchaLogin is expired!");'
            ],
            'reCaptchaReg' => [
                'size' => 'compact',
                'callback' => 'console.log("#reCaptchaReg is ready!");console.log(response);',
            ]
        ],
    ]
);
```

More information:

*[https://developers.google.com/recaptcha/docs/display](https://developers.google.com/recaptcha/docs/display).

*[https://developers.google.com/recaptcha/docs/verify](https://developers.google.com/recaptcha/docs/verify)