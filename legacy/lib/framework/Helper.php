<?php

/**
 * @author michael
 */

namespace framework;

class Helper
{
    private function __construct()
    {
        // TODO - Insert your code here
    }

    /**
     * do post request
     * uses file_get_cntents
     *  - don't work if  'php_value allow_url_fopen' or 'php_value allow_url_include' is disabled
     *
     * @param  string  $auth
     * @param  bool  $auth_encode
     */
    public static function do_post_request(string $url, array $data, $auth = null, $auth_encode = false): array
    {
        $result = [
            'success' => false,
            'code' => (-1),
            'data' => '',
        ];
        // post to pdf builder ===================================
        // use 'http' even if request is done to https://...
        $options = [
            'http' => [
                'ignore_errors' => true,
                'header' => [
                    'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
                ],
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        if ($auth) {
            $options['http']['header'][] = 'Authorization: Basic '.(($auth_encode) ? base64_encode($auth) : $auth);
        }
        $context = stream_context_create($options);
        // run post
        $postresult = file_get_contents($url, false, $context);

        // handle result
        $http_response_header = $http_response_header ?? null;
        if (is_array($http_response_header)) {
            $parts = explode(' ', $http_response_header[0]);
            if (count($parts) > 1) { // HTTP/1.0 <code> <text>
                $result['code'] = (int) $parts[1];
            } // Get code
        }
        // error ?
        if ($result['code'] === 200 && $postresult) {
            $result['data'] = json_decode($postresult, true);
            if ($result['data'] === null) {
                $result['data'] = $postresult;
            }
            $result['success'] = true;
        } elseif ($postresult) {
            $result['data'] = strip_tags($postresult);
        }

        return $result;
    }

    /**
     * do post request
     * uses curl
     *
     * @param  array  $data
     * @param  string  $auth
     * @param  bool  $auth_encode
     */
    public static function do_post_request2(string $url, $data = null, $auth = null, $auth_encode = false): array
    {
        $result = [
            'success' => false,
            'code' => (-1),
            'data' => '',
        ];

        // connection
        $ch = curl_init();

        $header = [
            'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
        ];
        if ($auth) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, (($auth_encode) ? $auth : base64_decode($auth)));
        }

        // set curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        if ($data) {
            $tmp_data = http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $tmp_data);
        }

        // run post
        $postresult = curl_exec($ch);

        // handle result
        $result['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close connection
        curl_close($ch);

        if ($result['code'] === 200 && $postresult) {
            $result['data'] = json_decode($postresult, true);
            if ($result['data'] === null) {
                $result['data'] = $postresult;
            }
            $result['success'] = true;
        } elseif ($postresult) {
            $result['data'] = strip_tags($postresult);
        }

        return $result;
    }

    public static function make_links_clickable($text)
    {
        return preg_replace('!((http(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;/=]+)!iu', '<a target="_blank" href="$1"><i class="fa fa-fw fa-chain"></i>&nbsp;$1</a>', $text);
    }

    public static function hasMultipleKeys(array $keys, array $array): bool
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $array)) {
                return false;
            }
        }

        return true;
    }
}
