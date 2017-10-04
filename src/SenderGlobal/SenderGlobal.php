<?php

namespace agraciakuvut;

/**
 * Class SenderGlobal
 *
 */
class SenderGlobal
{

    /**
     * Production API URL.
     * @const string
     */
    const BASE_SENDING_GLOBAL_URL = 'http://webapp.senderglobal.com/app/APIS/sincronizacion_bases/sincronizacion_bases.php';

    const CORRECT_CODES = [
        '0'   => 'Acción realizada correctamente sin errores',
        '1'   => 'El usuario está dado de baja',
        '2'   => 'El usuario está dado de baja como bounced',
        '3'   => 'El usuario está dado de baja por queja',
        '100' => 'Acción de alta realizada correctamente como actualización porque ya existía el usuario. ',
        '101' => 'Acción de alta realizada correctamente como actualización pero teniendo en cuenta que el usuario ya existía como baja. Sigue estando de baja',
        '102' => ' Acción de modificación realizada correctamente pero el usuario ya estaba de baja. Sigue estando de baja.',
    ];

    const STATUS_CODES = [
        '0' => 'El usuario está activo',
        '1' => 'El usuario está dado de baja',
        '2' => 'El usuario está dado de baja como bounced',
        '3' => 'El usuario está dado de baja por queja'
    ];


    const ERROR_CODES = [
        '10' => 'Login incorrecto. User_api o pssw_api incorrectos o no existen',
        '11' => 'Base_code no indicado',
        '12' => 'Base_name no indicado',
        '13' => 'Action no indicada',
        '14' => 'Email no indicado.',
        '15' => 'El Base_code introducido no corresponde a ningún cliente',
        '16' => 'El Base_code introducido no corresponde con el usuario y password',
        '17' => 'El parámetro Base_name introducido no corresponde con el usuario',
        '18' => 'Valor de Action no es correcto. Debe ser: altas, modificaciones, bajas, estado o bounces',
        '19' => 'El parámetro email no es un email correcto',
        '20' => 'Acción no ejecutada, error de conexión',
        '21' => 'Alta no ejecutada, ponerse en contacto con Sender Global',
        '31' => 'El registro que se intenta modificar no existe',
        '32' => 'Modificación no ejecutada, ponerse en contacto con Sender Global',
        '41' => 'El registro que se intenta dar de baja no existe',
        '42' => 'Baja no ejecutada, ponerse en contacto con Sender Global',
        '43' => 'El registro que se intenta dar de baja ya está dado de baja',
        '5'  => 'El registro que se intenta ver su estado no existe',
        '51' => 'El registro que se intenta dar como bounce no existe',
        '52' => 'Bounce no ejecutado, ponerse en contacto con Sender Global',
        '53' => 'El registro que se intenta dar como bounce ya está dado de baja u otro estado',
        '61' => 'El usuario no existe en la base de datos',
        '62' => 'Activación no ejecutada, ponerse en contacto con Sender Global',
        '63' => 'El usuario ya estaba activado en la base',
    ];

    /**
     * User for access the service
     * @var String $user
     */
    protected $user;

    /**
     * Password to access the service
     * @var String $password
     */
    protected $password;

    /**
     * Code by which identifies the user and its code base.
     * @var String $baseCode
     */
    protected $baseCode;

    /**
     * Name of the base on which the action is to be carried out.
     * @var String $baseName
     */
    protected $baseName;

    /**
     * Registry key in the database on which the API will be based to perform
     * the action. Although in the customer database the email field is not called
     * exactly "email" should be made the call with the email parameter.
     * @var String $email
     */
    //protected $email;

    /**
     * Action to be taken by the API.
     * @var String $action
     */
    protected $action;

    public function __construct(String $user, String $password, String $baseCode, String $baseName)
    {
        $this->user = $user;
        $this->password = $password;
        $this->baseCode = $baseCode;
        $this->baseName = $baseName;
    }

    /**
     * @return String
     */
    public function getUser(): String
    {
        return $this->user;
    }

    /**
     * @param String $user
     */
    public function setUser(String $user)
    {
        $this->user = $user;
    }

    /**
     * @return String
     */
    public function getPassword(): String
    {
        return $this->password;
    }

    /**
     * @param String $password
     */
    public function setPassword(String $password)
    {
        $this->password = $password;
    }

    public function register(String $email, array $data = [])
    {
        $this->action = 'altas';
        return $this->parseResult($this->makeCall($email, $data));
    }

    public function modify(String $email, array $data = [], string $newEmail = '')
    {
        $this->action = 'modificaciones';
        if (!empty($newEmail)) {
            $data['new_email'] = $newEmail;
        }
        return $this->parseResult($this->makeCall($email, $data));
    }

    public function unregister(String $email)
    {
        $this->action = 'bajas';
        return $this->parseResult($this->makeCall($email));
    }

    public function reactivate(String $email)
    {
        $this->action = 'activar';
        return $this->parseResult($this->makeCall($email));
    }

    public function bounce(String $email)
    {
        $this->action = 'bounces';
        return $this->parseResult($this->makeCall($email));
    }

    public function status(String $email)
    {
        $this->action = 'estado';
        return $this->parseResult($this->makeCall($email));
    }

    protected function parseResult($result)
    {

        if (!is_numeric($result)) {
            if ($jsonResult = json_decode($result)) {
                foreach ($jsonResult as $k => $d) {
                    if (array_key_exists($d->Codigo, self::ERROR_CODES)) {
                        $jsonResult[$k]->error = true;
                        $jsonResult[$k]->msg = self::ERROR_CODES[$d->Codigo];
                    }
                    if (array_key_exists($d->Codigo, self::CORRECT_CODES)) {
                        $jsonResult[$k]->error = false;
                        $jsonResult[$k]->msg = self::CORRECT_CODES[$d->Codigo];
                    }
                }

                return $jsonResult;
            }

            throw new \Exception('Error connection API', -1);
        }

        if (array_key_exists($result, self::ERROR_CODES)) {
            throw new \Exception(self::ERROR_CODES[$result], $result);
        }

        if (array_key_exists($result, self::CORRECT_CODES)) {
            if ($this->action == 'estado') {
                return [
                    'status' => $result,
                    'msg'    => self::STATUS_CODES[$result]
                ];
            }
            return true;
        }

        throw new \Exception('Result code unknow', $result);
    }

    protected function makeCall(String $email, array $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getUrl($email, $data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    protected function getUrl(String $email, array $data)
    {
        $url = self::BASE_SENDING_GLOBAL_URL;
        $url .= '?';
        $url .= 'user_api=' . urlencode($this->getUser());
        $url .= '&pssw_api=' . urlencode($this->getPassword());
        $url .= '&action=' . urlencode($this->action);
        $url .= '&base_code=' . urlencode($this->baseCode);
        $url .= '&base_name=' . urlencode($this->baseName);
        $url .= '&email=' . urlencode($email);

        foreach ($data as $k => $d) {
            $url .= '&' . urlencode($k) . '=' . urlencode($d);
        }

        return $url;
    }

}