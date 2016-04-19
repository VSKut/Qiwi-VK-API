<?php
namespace VSKut\Qiwi_VK_API;

/**
 * Class QiwiVK
 * @package VSKut\Qiwi_VK_API
 */
class QiwiVK
{

    private $phone;
    private $password;

    public $oauthToken;
    public $accessToken;

    public $transactions_array;
    public $access = false;

    public $error = false;
    public $error_info = [
        'code' => 0,
        'message' => '',
    ];

    /**
     * Устанавливаем телефон и пароль
     *
     * @param string $phone
     * @param string $password
     */
    public function __construct($phone, $password) {
        $this->phone = $phone;
        $this->password = $password;
    }


    /**
     * Устанавливаем токен авторизации
     *
     * @param string $token
     */
    public function setOauthToken($token)
    {
        $this->oauthToken = $token;
    }


    /**
     * Устанавливаем токен доступа
     *
     * @param string $token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }


    /**
     * Получаем токен авторизации от сервера Qiwi
     *
     * @return string
     */
    public function getOauthToken()
    {
        $request = $this->getXmlRequest([
            'request-type' => 'oauth-token',
            'phone' => $this->phone,
            'password' => $this->password,
        ]);
        $response = $this->curl($request);
        $array = $this->parseXml($response);
        $errors = $this->checkErrors($array);

        if ($errors === false && !empty($array['RESPONSE']['CODE'])) {
            $this->oauthToken = $array['RESPONSE']['CODE'];
            return $this->oauthToken;
        }

        return false;
    }



    /**
     * Получаем токен доступа от сервера Qiwi
     *
     * @param string $sms_code
     * @return bool
     */
    public function getAccessToken($sms_code)
    {
        $request = $this->getXmlRequest([
            'request-type' => 'oauth-token',
            'phone' => $this->phone,
            'code' => $this->oauthToken,
            'vcode' => $sms_code,
        ]);
        $response = $this->curl($request);
        $array = $this->parseXml($response);
        $errors = $this->checkErrors($array);

        if ($errors === false && !empty($array['RESPONSE']['ACCESS-TOKEN'])) {
            $this->accessToken = $array['RESPONSE']['ACCESS-TOKEN'];
            return $this->accessToken;
        }

        return false;
    }



    /**
     * Проверяем токен доступа на сервере Qiwi
     *
     * @return bool
     */
    public function checkAccessToken()
    {
        $request = $this->getXmlRequest([
            'request-type' => 'get-payments-report',
            'terminal-id' => $this->phone,
            'extra' => ['name="token"' => $this->accessToken],
            'full' => 1,
            'period' => 'week',
        ]);

        $response = $this->curl($request);
        $array = $this->parseXml($response);
        $errors = $this->checkErrors($array);


        if ($errors === false && !empty($array['RESPONSE']['P-LIST']['P'])) {
            $this->transactions_array = $array['RESPONSE']['P-LIST']['P'];
            $this->access = true;
            return true;
        } else {
            $this->access = false;
            return false;
        }
    }


    /**
     * Возвращает список транзакций
     *
     * @return mixed
     */
    public function getTransactions()
    {
        return $this->transactions_array;
    }


    /**
     * Проверяет транзакцию на наличие
     *
     * @param float $price
     * @param string $comment
     * @return array
     */
    public function checkTransaction($price, $comment)
    {
        foreach($this->transactions_array as $operation)
        {
            // TODO: Проверка типа валюты
            if (
                !empty($operation['S']) &&
                !empty($operation['E_MESSAGE']) &&
                !empty($operation['CMNT']) &&
                !empty($operation['D']) &&
                !empty($operation['FROM_C']) &&
                !empty($operation['TO_C']) &&
                $operation['S'] == $price &&
                $operation['E_MESSAGE'] == "Ок" &&
                $operation['CMNT'] == $comment &&
                $operation['FROM_C'] == '643' &&
                $operation['TO_C'] == '643' &&
                $operation['D'] == "+"
            )
            {
                return $operation;
            }
        }
        return false;
    }


    /**
     * Формируем Request в XML
     *
     * @param array $array
     * @return string
     */
    private function getXmlRequest($array)
    {
        $request_data = '';
        foreach ($array as $key => $value) {
            if ($key == 'extra') {
                foreach ($value as $extra_key => $extra_value) {
                    $request_data = $request_data."<extra ".$extra_key.">".$extra_value."</extra>";
                }
            } else {
                $request_data = $request_data."<".$key.">".$value."</".$key.">";
            }
        }
        return "<request>".$request_data."<client-id>vkontakte</client-id><extra name=\"client-software\">VK_WEB v5.30.17</extra></request>";
    }


    /**
     * Curl запрос
     *
     * @param string $request
     * @return string mixed
     */
    private function curl($request)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://w.qiwi.com/xml/xmlutf.jsp');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }





    /**
     * Получаем информацию по ошибке
     *
     * @return array|bool
     */
    public function getError()
    {
        if ($this->error) {
            return [
                'status' => $this->error,
                'info' => $this->error_info,
            ];
        } else {
            return false;
        }

    }



    /**
     * Проверка наличия ошибок в ответе
     *
     * @param array $array
     * @return bool
     */
    private function checkErrors($array)
    {
        if (!empty($array['RESPONSE']['RESULT-CODE']['FATAL']) && $array['RESPONSE']['RESULT-CODE']['FATAL'] == 'true')
        {
            if (!empty($array['RESPONSE']['RESULT-CODE']['MESSAGE'])) {
                $message = $array['RESPONSE']['RESULT-CODE']['MESSAGE'];
            } else {
                $message = null;
            }

            $this->error = true;
            $this->error_info = [
                'code' => $array['RESPONSE']['RESULT-CODE']['content'],
                'message' => $message,
            ];
            return true;
        }
        return false;
    }




    /**
     * Преобразуем XML в Array
     *
     * @param string $xml
     * @return array mixed
     */
    function parseXml($xml)
    {
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $xml, $vals);
        xml_parser_free($xml_parser);
        $_tmp='';
        $multi_key = [];
        $level = [];
        $xml_array = [];
        foreach ($vals as $xml_elem) {
            $x_tag=$xml_elem['tag'];
            $x_level=$xml_elem['level'];
            $x_type=$xml_elem['type'];
            if ($x_level!=1 && $x_type == 'close') {
                if (isset($multi_key[$x_tag][$x_level]))
                    $multi_key[$x_tag][$x_level]=1;
                else
                    $multi_key[$x_tag][$x_level]=0;
            }
            if ($x_level!=1 && $x_type == 'complete') {
                if ($_tmp==$x_tag)
                    $multi_key[$x_tag][$x_level]=1;
                $_tmp=$x_tag;
            }
        }
        foreach ($vals as $xml_elem) {
            $x_tag=$xml_elem['tag'];
            $x_level=$xml_elem['level'];
            $x_type=$xml_elem['type'];
            if ($x_type == 'open')
                $level[$x_level] = $x_tag;
            $start_level = 1;
            $php_stmt = '$xml_array';
            if ($x_type=='close' && $x_level!=1)
                $multi_key[$x_tag][$x_level]++;
            while ($start_level < $x_level) {
                $php_stmt .= '[$level['.$start_level.']]';
                if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level])
                    $php_stmt .= '['.($multi_key[$level[$start_level]][$start_level]-1).']';
                $start_level++;
            }
            $add='';
            if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type=='open' || $x_type=='complete')) {
                if (!isset($multi_key2[$x_tag][$x_level]))
                    $multi_key2[$x_tag][$x_level]=0;
                else
                    $multi_key2[$x_tag][$x_level]++;
                $add='['.$multi_key2[$x_tag][$x_level].']';
            }
            if (isset($xml_elem['value']) && trim($xml_elem['value'])!='' && !array_key_exists('attributes', $xml_elem)) {
                if ($x_type == 'open')
                    $php_stmt_main=$php_stmt.'[$x_type]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
                else
                    $php_stmt_main=$php_stmt.'[$x_tag]'.$add.' = $xml_elem[\'value\'];';
                eval($php_stmt_main);
            }
            if (array_key_exists('attributes', $xml_elem)) {
                if (isset($xml_elem['value'])) {
                    $php_stmt_main=$php_stmt.'[$x_tag]'.$add.'[\'content\'] = $xml_elem[\'value\'];';
                    eval($php_stmt_main);
                }
                foreach ($xml_elem['attributes'] as $key=>$value) {
                    $php_stmt_att=$php_stmt.'[$x_tag]'.$add.'[$key] = $value;';
                    eval($php_stmt_att);
                }
            }
        }
        return $xml_array;
    }



}