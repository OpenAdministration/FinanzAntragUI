<?php

namespace framework;

use Exception;
use JetBrains\PhpStorm\ArrayShape;
use TypeError;

class NewValidator
{
    private const VALIDATOR_RETURN_ARRAY = [0 => 'bool', 1 => 'mixed'];

    /**
     * validator tracks if last test was successful or not
     * boolean
     */
    protected bool $isError = false;

    /**
     * filter may sanitize inputs
     * mixed
     */
    protected mixed $sanitizedValue;

    /*
     * all error msgs, keys represent
     */
    private array $errors = [];

    /**
     * add an Error
     *
     * @param  string  $msg  error message
     * @param  string  $validator  validator name
     * @param  array  $keys  in array
     * @param  mixed  $value  value which should be validated (debug purpose only, not sanitized)
     */
    private function addError(string $msg, string $validator, array $keys, mixed $value): void
    {
        $this->isError = true;
        $this->errors[] = [$msg, $validator, implode(':', $keys), DEV ? $value : null];
    }

    /**
     * @return bool $isError
     */
    public function isError(): bool
    {
        return $this->isError;
    }

    /**
     * @return array $errors [$msg, $validator, $fieldName, DEV ? $value: null]
     */
    #[ArrayShape([[0 => 'string', 1 => 'string', 2 => 'string', 3 => 'mixed']])]
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * filter may sanitize input values are stored here
     * Post validators will create sanitized array
     *
     * @param  array  $keys  - will be applied afterwards
     * @return mixed $filtered value
     */
    public function getSanitizedArray(array $keys = []): mixed
    {
        $ret = $this->sanitizedValue;
        foreach ($keys as $key) {
            $ret = $ret[$key];
        }

        return $ret;
    }

    // ==========================================
    /**
     * call selected validator function
     *
     * @return array
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    public function validate(mixed $value, array|string $validator): mixed
    {
        return $this->_validate($value, $validator, []);
    }

    /**
     * call selected validator function
     *
     * @return array
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function _validate(mixed $value, array|string $validator, array $keys): mixed
    {
        $validatorName = (is_array($validator)) ? $validator[0] : $validator;
        $validatorParams = (is_array($validator)) ? array_slice($validator, 1) : [];
        $vMethod = 'V_'.$validatorName;
        if (is_callable([$this, $vMethod]) && method_exists($this, $vMethod)
        ) {
            try {
                return $this->$vMethod($value, $validatorParams, $keys);
            } catch (TypeError $typeError) {
                $this->addError($typeError->getMessage(), $validatorName, $keys, $value);

                return [false, null];
            }
        }
        $this->addError('Unknown Validator', $validatorName, $keys, $value);

        return [$this->isError, null];
    }

    /**
     * $map format:
     *    [
     *        'key' => 'validator',
     *        'key2' => ['validator', 'validator_param' => 'validator_value', 'validator_param', ...],
     *    ]
     * validator may contains parameter 'optional' -> so required can be disabled per parameter
     *
     * @param  mixed  $source_unsafe  values to check and filter
     * @param  array  $map  validation map
     * @param  bool  $allKeysRequired  all keys in validation map are required
     * @return array [error, sanitized array]
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    public function validateArray(mixed $source_unsafe, array $map, bool $allKeysRequired = true): array
    {
        $out = [];
        foreach ($map as $key => $validator) {
            if (! isset($source_unsafe[$key])) {
                if ($allKeysRequired && ! in_array('optional', $validator, true)) {
                    $this->addError("Missing parameter: '$key'", __METHOD__, [$key], $source_unsafe);
                }
            } else {
                [,$sanitized] = $this->_validate($source_unsafe[$key], $validator, [$key]);
                $out[$key] = $sanitized;
            }
        }
        $this->sanitizedValue = $out;

        return [$this->isError(), $out];
    }

    // ====== VALIDATORS ========================
    // functions must start with 'V_validatorname'

    /**
     * integer validator
     *
     * params:
     *  KEY  1-> single value, 2-> key value pair
     *    min    2
     *    max    2
     *  even    1
     *  odd    1
     *  modulo    2
     *  error    2    error message on error case
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_integer(string|int $value, array $params, array $keys): array
    {
        $v = filter_var($value, FILTER_VALIDATE_INT);

        if ($v === false) {
            $msg = $params['error'] ?? 'No Integer';
            $this->addError($msg, 'integer', $keys, $value);

            return [$this->isError, null];
        }
        $v = (int) $v;

        if ($v % 2 !== 0 && in_array('even', $params, true)) {
            $msg = $params['error'] ?? 'Integer have to be even';
            $this->addError($msg, 'integer', $keys, $value);
        }
        if ($v % 2 === 0 && in_array('odd', $params, true)) {
            $msg = $params['error'] ?? 'Integer have to be odd';
            $this->addError($msg, 'integer', $keys, $value);
        }
        if (isset($params['min']) && $v < $params['min']) {
            $msg = $params['error'] ?? "Integer out of range: smaller than {$params['min']}";
            $this->addError($msg, 'integer', $keys, $value);
        }
        if (isset($params['max']) && $v > $params['max']) {
            $msg = $params['error'] ?? "Integer out of range: larger than {$params['max']}";
            $this->addError($msg, 'integer', $keys, $value);
        }
        if (isset($params['modulo']) && $v % $params['modulo'] !== 0) {
            $msg = $params['error'] ?? 'Integer modulo failed';
            $this->addError($msg, 'integer', $keys, $value);
        }

        return [$this->isError, $v];
    }

    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_in(mixed $value, array $params, array $keys): array
    {
        if (! isset($params[0]) || ! is_array($params[0])) {
            $this->addError('Options/Choices not set', 'in', $keys, $value);

            return [false, null];
        }
        $choices = $params[0];
        if (! in_array($value, $choices, true)) {
            $this->addError('Not in choices/options', 'in', $keys, $value);

            return [false, null];
        }

        return [true, $value];
    }

    /**
     * float validator
     *
     * params:
     *  KEY  1-> single value, 2-> key value pair
     *  decimal_seperator    2    [. or ,] default: .
     *    min                2    min value
     *    max                2    max value
     *  step                2    step - be carefull may produce errors (wrong deteced values)
     *  format                2    trim to x decimal places
     *  error                2    error message on error case
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_float(string|float $value, array $params, array $keys): array
    {
        $decimal = $params['decimal_seperator'] ?? '.';
        if (filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => ['decimal' => $decimal]]) === false) {
            $msg = $params['error'] ?? 'No Float';
            $this->addError($msg, 'float', $keys, $value);

            return [$this->isError, null];
        }

        $v = filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => ['decimal' => $decimal]]);
        if (isset($params['min']) && $v < $params['min']) {
            $msg = $params['error'] ?? "Float out of range: smaller than {$params['min']}";
            $this->addError($msg, 'float', $keys, $value);
        }
        if (isset($params['max']) && $v > $params['max']) {
            $msg = $params['error'] ?? "Float out of range: larger than {$params['max']}";
            $this->addError($msg, 'float', $keys, $value);
        }
        if (isset($params['step'])) {
            $mod = $params['step'];
            $cv = $v;
            $ex = '';
            if (($p = strpos($mod, '.')) !== false) {
                $ex = strlen(substr($params['step'], $p + 1));
                $ex = (10 ** $ex);
                $mod *= $ex;
                $cv *= $ex;
            }
            $k = strlen($ex);
            if ((is_numeric($cv) && mb_strpos($value, '.') && mb_strpos($value, '.') + ($k) < mb_strlen($value)) || $cv % $mod !== 0) {
                $msg = $params['error'] ?? 'float invalid step';
                $this->addError($msg, 'float', $keys, $value);
            }
        }
        if (isset($params['format'])) {
            $v = number_format($v, $params['format'], $decimal, '');
        }

        return [$this->isError, $v];
    }

    /**
     * check if integer and larger than 0
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_id(int $value, array $params, array $keys): array
    {
        return $this->V_integer($value, ['min' => 1] + $params, $keys);
    }

    /**
     * text validator
     *
     * params:
     *  KEY  1-> single value, 2-> key value pair
     *    strip                1
     *    trim                1
     *  htmlspecialchars    1
     *  htmlentities        1
     *  minlength 2        minimum string length
     *  maxlength 2        maximum string length - default 127, set -1 for unlimited value
     *  error      2    replace whole error message on error case
     *  empty      1    allow empty value
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_text(string $s, array $params, array $keys): array
    {
        if ($s === '' && in_array('empty', $params, true)) {
            return [$this->isError, ''];
        }
        if (in_array('trim', $params, true)) {
            $s = trim($s);
        }
        if (in_array('strip', $params, true)) {
            $s = strip_tags($s);
        }
        if (in_array('htmlspecialchars', $params, true)) {
            $s = htmlspecialchars($s);
        }
        if (in_array('htmlentities', $params, true)) {
            $s = htmlentities($s);
        }

        if (isset($params['minlength']) && strlen($s) < $params['minlength']) {
            $msg = $params['error'] ?? "The text is too short (Minimum length: {$params['minlength']})";
            $this->addError($msg, 'text', $keys, $s);
        }
        if (isset($params['maxlength']) && $params['maxlength'] !== -1 && strlen($s) > $params['maxlength']) {
            $msg = $params['error'] ?? "The text is too long (Maximum length: {$params['maxlength']})";
            $this->addError($msg, 'text', $keys, $s);
        }

        return [$this->isError, $s];
    }

    /**
     * email validator
     *
     * $param
     *  empty        1    allow empty value
     *  maxlength    2    maximum string length
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_mail(string $value, array $params, array $keys): array
    {
        $email = filter_var($value, FILTER_SANITIZE_EMAIL);
        if ($email === '' && in_array('empty', $params, true)) {
            return [$this->isError, ''];
        }
        if (isset($params['maxlength']) && strlen($email) >= $params['maxlength']) {
            $msg = "E-Mail is too long (Maximum length: {$params['maxlength']})";
            $this->addError($msg, 'mail', $keys, $value);
        }
        $re = '/^([a-zA-Z0-9_.+-])+@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,6})$/';
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || ! preg_match($re, $email)) {
            $msg = $params['error'] ?? 'Email Validation failed';
            $this->addError($msg, 'mail', $keys, $value);

            return [$this->isError, strip_tags($email)];
        }

        return [$this->isError, $email];
    }

    /**
     * phone validator
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_phone(string $value, array $params, array $keys): array
    {
        $phone = trim(strip_tags($value));
        $re = '/[^0-9+-]/'; // match all not in the list
        $phone = preg_replace($re, '', $phone);

        if (strlen($phone) > 40) {
            $msg = $params['error'] ?? 'Email Validation failed';
            $this->addError($msg, 'phone', $keys, $phone);

            return [$this->isError, ''];
        }

        return [$this->isError, $phone];
    }

    /**
     * url validator
     *
     * @param  array  $params
     *                         empty            1    allow empty value
     *                         error            2    replace whole error message on error case
     *                         forceprotocol    1    force http://|https:// in url
     *                         forceslash        1    force trailingslash
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_url(string $value, array $params, array $keys): array
    {
        $url = trim(strip_tags($value));
        if ($url === '' && in_array('empty', $params, true)) {
            return [$this->isError, ''];
        }
        $re = '/^((http[s]?)((:|%3A)\/\/))'.((in_array('forceprotocol', $params, true)) ? '' : '?').'(((\w)+((-|\.)(\w+))*)+(\w){0,6}?(:([0-5]?[\d]{1,4}|6([0-4][\d]{3}|5([0-4][\d]{2}|5([0-2][\d]|3[0-5])))))?\/'.((in_array('forceslash', $params, true)) ? '' : '?').')((\w)+((\.|-)(\w)+)*\/'.((in_array('forceslash', $params, true)) ? '' : '?').')*$/';
        if (! preg_match($re, $url) || strlen($url) >= 128) {
            $msg = $params['error'] ?? 'url validation failed';
            $this->addError($msg, 'url', $keys, $url);

            return [$this->isError, ''];
        }

        return [$this->isError, $url];
    }

    /**
     * ip validator
     * check if string is a valid ip address (supports ipv4 and ipv6)
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_ip(string $value, array $params, array $keys): array
    {
        $ip = trim(strip_tags($value));
        if (self::isValidIP($ip)) {
            return [$this->isError, $ip];
        }
        $msg = $params['error'] ?? 'ip validation failed';
        $this->addError($msg, 'ip', $keys, $ip);

        return [false, ''];
    }

    /**
     * check if string is a valid ip address (supports ipv4 and ipv6)
     * helper function
     *
     * @param  bool  $recursive  if true also allowes IP address with surrounding brackets []
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private static function isValidIP(string $ipadr, bool $recursive = true): bool
    {
        /* @see https://owasp.org/www-community/OWASP_Validation_Regex_Repository */
        if (preg_match('/^(25[0-5]|2[0-4][\d]|[01]?[\d]{1,2})\.(25[0-5]|2[0-4][\d]|[01]?[\d]{1,2})\.(25[0-5]|2[0-4][\d]|[01]?[\d]{1,2})\.(25[0-5]|2[0-4][\d]|[01]?[\d]{1,2})$/', $ipadr)) {
            return true;
        }

        if ($recursive && strlen($ipadr) > 2 && $ipadr[0] === '[' && $ipadr[strlen($ipadr)] === ']') {
            return self::isValidIP(substr($ipadr, 1, -1), false);
        }

        return false;
    }

    /**
     * domain validator
     *
     * $param
     *  empty    1    allow empty value
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_domain(string $value, array $params, array $keys): array
    {
        $host = trim(strip_tags($value));
        if ($host === '' && in_array('empty', $params, true)) {
            return [false, $host];
        }
        if (self::isValidIP($host)) {
            return [$this->isError, $host];
        }
        if (preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $host) &&
             filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
            return [$this->isError, $host];
        }
        $value_idn = idn_to_ascii($host);
        if (preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $value_idn) &&
            filter_var($value_idn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
            return [$this->isError, $host];
        }
        $msg = $params['error'] ?? 'No valid Hostname';
        $this->addError($msg, 'domain', $keys, $host);

        return [false, ''];
    }

    /**
     * regex validator
     *
     * $param
     *  regex       2    match pattern
     *  errorkey   2    replace 'regex' with errorkey on error case
     *  error       2    replace whole error message on error case
     *  upper       1    string to uppercase
     *  lower       1    string to lower case
     *  replace    2    touple [search, replace] replace string
     *  minlength  2    minimum string length
     *  maxlength  2    maximum string length
     *  noTagStrip 1    disable tag strip before validation
     *  noTrim       1    disable trim whitespaces
     *  trimLeft   2    trim Text on left side, parameter trim characters
     *  trimRight  2    trim Text on right side, parameter trim characters
     *  empty       1    allow empty string if not in regex
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_regex(string $v, array $params, array $keys): array
    {
        if (! in_array('noTagStrip', $params, true)) {
            $v = strip_tags($v);
        }
        if (! in_array('noTrim', $params, true)) {
            $v = trim($v);
        }
        if (isset($params['trimLeft'])) {
            $v = ltrim($v, $params['trimLeft']);
        }
        if (isset($params['trimRight'])) {
            $v = rtrim($v, $params['trimRight']);
        }
        if ($v === '' && in_array('empty', $params, true)) {
            return [$this->isError, $v];
        }
        if (isset($params['replace'])) {
            $v = str_replace($params['replace'][0], $params['replace'][1], $v);
        }
        if (in_array('upper', $params, true)) {
            $v = strtoupper($v);
        }
        if (in_array('lower', $params, true)) {
            $v = strtolower($v);
        }
        if (isset($params['maxlength']) && strlen($v) >= $params['maxlength']) {
            $msg = $params['error'] ?? "String is too long (Maximum length: {$params['maxlength']})";
            $this->addError($msg, 'regex', $keys, $v);

            return [false, ''];
        }
        if (isset($params['minlength']) && strlen($v) < $params['minlength']) {
            $msg = $params['error'] ?? "String is too short (Minimum length: {$params['minlength']})";
            $this->addError($msg, 'regex', $keys, $v);

            return [false, ''];
        }
        $re = $params['pattern'];
        if (! preg_match($re, $v)) {
            $msg = $params['error'] ?? 'Validation failed';
            $this->addError($msg, 'regex', $keys, $v);

            return [false, ''];
        }

        return [$this->isError, $v];
    }

    /**
     * password validator
     *
     * $param
     *  minlength 2        minimum string length
     *  maxlength 2        maximum string length
     *  empty      1    allow empty value
     *  encrypt      1    encrypt password - only available if Crypto class is defined
     *  hash      1    hash password     - only available if Crypto class is defined
     *    error       2    replace whole error message on error case
     *
     * @throws Exception
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_password(string $value, array $params, array $keys): array
    {
        $p = trim(strip_tags($value));
        if ($p === '' && in_array('empty', $params, true)) {
            return [$this->isError, ''];
        }
        if (isset($params['maxlength']) && strlen($p) >= $params['maxlength']) {
            $msg = $params['error'] ?? "The password is too long (Maximum length: {$params['maxlength']})";
            $this->addError($msg, 'password', $keys, $p);

            return [false, ''];
        }
        if (isset($params['minlength']) && strlen($p) < $params['minlength']) {
            $msg = $params['error'] ?? "The password is too short (Minimum length: {$params['minlength']})";
            $this->addError($msg, 'password', $keys, $p);

            return [false, ''];
        }
        if (in_array('hash', $params, true)) {
            // TODO: think about salting me
            $p = CryptoHandler::hashPassword($p);
        } elseif (in_array('encrypt', $params, true)) {
            $p = CryptoHandler::pad_string($p);
            $p = CryptoHandler::encrypt_by_key_pw($p, CryptoHandler::get_key_from_file(SYSBASE.'/secret.php'), $_ENV['APP_SECRET']);
        }

        return [$this->isError, $p];
    }

    /**
     * array validator
     * test if element is array
     *
     * $param
     *  key            2    validate array key to -> validatorelelemnt, requires param->validator to be set
     *  minlength    2    minimum string length
     *  maxlength    2    maximum string length
     *  empty        1    allow empty array
     *  values    2    run this validator on each array element
     *  error        2    overwrite error message
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_array(array $a, array $params, array $keys): array
    {
        if ((! in_array('empty', $params, true) || count($a) > 0) && isset($params['minlength']) && count($a) < $params['minlength']) {
            $msg = $params['error'] ?? ('Array to short: require minimal length of "'.$params['minlength'].'" elements');
            $this->addError($msg, 'array', $keys, $a);

            return [false, ''];
        }
        if (isset($params['maxlength']) && count($a) > $params['maxlength']) {
            $msg = $params['error'] ?? ('Array to long: maximal array length "'.$params['maxlength'].'"');
            $this->addError($msg, 'array', $keys, $a);

            return [false, ''];
        }
        if (! in_array('empty', $params, true) && count($a) === 0) {
            $msg = $params['error'] ?? 'Array to short: empty array is not permitted.';
            $this->addError($msg, 'array', $keys, $a);

            return [false, ''];
        }
        if (! isset($params['values'])) {
            return [$this->isError, $a];
        }

        // todo: implement low(er) level required as well, not only top level in other function

        $out = [];
        foreach ($a as $arrayKey => $entry) {
            // key
            $keyFiltered = $arrayKey;
            if (isset($params['key'])) {
                [,$keyFiltered] = $this->_validate($arrayKey, $params['key'], $keys);
            }
            // value
            [,$sanitizedEntry] = $this->_validate($entry, $params['values'], [...$keys, $arrayKey]);

            $out[$keyFiltered] = $sanitizedEntry;
        }

        return [$this->isError, $out];
    }

    /**
     * array validator
     * test if string is valid iban
     *
     * $param
     *  empty        1    allow empty array
     *  error        2    overwrite error message
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private function V_iban(string $value, array $params, array $keys): array
    {
        $iban = trim(strip_tags(''.$value));
        $iban = strtoupper($iban); // to upper
        $iban = preg_replace('/(\s|\n|\r)/', '', $iban); // remove white spaces
        // empty
        if ($iban === '' && in_array('empty', $params, true)) {
            return [true, ''];
        }
        // check iban
        if (! self::_checkIBAN($iban)) {
            $msg = $params['error'] ?? 'IBAN validation failed';
            $this->addError($msg, 'iban', $keys, $iban);

            return [false, ''];
        }

        return [true, $iban];
    }

    /**
     * check if string is valid iban,
     *
     * @param  string  $iban  to check
     *
     * @see https://en.wikipedia.org/wiki/International_Bank_Account_Number#Validating_the_IBAN
     * @see https://stackoverflow.com/questions/20983339/validate-iban-php
     */
    #[ArrayShape(self::VALIDATOR_RETURN_ARRAY)]
    private static function _checkIBAN(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));
        $countries = ['AL' => 28, 'AD' => 24, 'AT' => 20, 'AZ' => 28, 'BH' => 22, 'BE' => 16, 'BA' => 20, 'BR' => 29, 'BG' => 22, 'CR' => 21, 'HR' => 21, 'CY' => 28, 'CZ' => 24, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'FO' => 18, 'FI' => 18, 'FR' => 27, 'GE' => 22, 'DE' => 22, 'GI' => 23, 'GR' => 27, 'GL' => 18, 'GT' => 28, 'HU' => 28, 'IS' => 26, 'IE' => 22, 'IL' => 23, 'IT' => 27, 'JO' => 30, 'KZ' => 20, 'KW' => 30, 'LV' => 21, 'LB' => 28, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'MK' => 19, 'MT' => 31, 'MR' => 27, 'MU' => 30, 'MC' => 27, 'MD' => 24, 'ME' => 22, 'NL' => 18, 'NO' => 15, 'PK' => 24, 'PS' => 29, 'PL' => 28, 'PT' => 25, 'QA' => 29, 'RO' => 24, 'SM' => 27, 'SA' => 24, 'RS' => 22, 'SK' => 24, 'SI' => 19, 'ES' => 24, 'SE' => 24, 'CH' => 21, 'TN' => 24, 'TR' => 26, 'AE' => 23, 'GB' => 22, 'VG' => 24];

        // 1. check country code exists + iban has valid length
        if (! array_key_exists(substr($iban, 0, 2), $countries)) {
            return false;
        }
        // check if censored iban
        if (preg_match("/^[A-Z]{2}\d{2}[\.]{6}\d{2}$/", $iban)) {
            return true;
        }

        // check length
        if (strlen($iban) !== $countries[substr($iban, 0, 2)]) {
            return false;
        }

        // 2. Rearrange country code and checksum
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        // 3. convert to integer
        $iban_letters = str_split($rearranged);
        $iban_int_only = '';
        foreach ($iban_letters as $char) {
            if (is_numeric($char)) {
                $iban_int_only .= $char;
            } else {
                $ord = ord($char) - 55; // ascii representation - 55, so a => 10, b => 11, ...
                if ($ord >= 10 && $ord <= 35) {
                    $iban_int_only .= $ord;
                } else {
                    return false;
                }
            }
        }

        // 4. calculate mod 97 -> have to be 1
        return bcmod($iban_int_only, '97') === '1';
    }
}
