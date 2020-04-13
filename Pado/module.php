<?php /** @noinspection PhpUnused */

declare(strict_types=1);

include __DIR__ . '/../libs/WebHookModule.php';
define('DUMMY_MODULE_ID', '{485D0419-BE97-4548-AA9C-C083EB82E61E}');

class Pado extends WebHookModule
{
    public function __construct($InstanceID)
    {
        parent::__construct($InstanceID, 'pado');
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Cleanup old hook script
        $id = @IPS_GetObjectIDByIdent('Hook', $this->InstanceID);
        if ($id > 0) {
            IPS_DeleteScript($id, true);
        }
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
    {
        //Never delete this line!
        parent::ProcessHookData();

        $this->SendDebug('Data', print_r($_POST, true), 0);

        if ((IPS_GetProperty($this->InstanceID, 'Username') !== '') || (IPS_GetProperty($this->InstanceID, 'Password') !== '')) {
            $this->SendDebug('$_SERVER', print_r($_SERVER, true), 0);

            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                $_SERVER['PHP_AUTH_USER'] = '';
            }
            if (!isset($_SERVER['PHP_AUTH_PW'])) {
                $_SERVER['PHP_AUTH_PW'] = '';
            }

            if (($_SERVER['PHP_AUTH_USER'] !== IPS_GetProperty($this->InstanceID, 'Username'))
                || ($_SERVER['PHP_AUTH_PW'] !== IPS_GetProperty(
                        $this->InstanceID,
                        'Password'
                    ))) {
                header('WWW-Authenticate: Basic Realm="Pado WebHook"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Authorization required';
                $this->SendDebug('Unauthorized', print_r($_POST, true), 0);
                return;
            }
        }

        if (!isset($_POST['device'], $_POST['id'], $_POST['name']) || (!isset($_POST['enter']) && !isset($_POST['exit']))) {
            $this->SendDebug('Malformed', print_r($_POST, true), 0);
            trigger_error('Malformed POST: ' . print_r($_POST, true));
            return;
        }

        $deviceID = $this->CreateInstanceByIdent($this->InstanceID, $this->ReduceGUIDToIdent($_POST['device']), $_POST['device']);
        if (isset($_POST['enter'])) {
            SetValueBoolean(
                $this->CreateVariableByIdent($deviceID, $this->ReduceGUIDToIdent($_POST['id']), $_POST['name'], VARIABLETYPE_BOOLEAN, '~Presence'),
                ($_POST['enter'] !== '0')
            );
        } else {
            SetValueBoolean(
                $this->CreateVariableByIdent($deviceID, $this->ReduceGUIDToIdent($_POST['id']), $_POST['name'], VARIABLETYPE_BOOLEAN, '~Presence'),
                ($_POST['exit'] === '0')
            );
        }
        if (isset($_POST['latitude'])) {
            SetValueFloat($this->CreateVariableByIdent($deviceID, 'Latitude', 'Latitude', VARIABLETYPE_FLOAT), (float)$_POST['latitude']);
        }
        if (isset($_POST['longitude'])) {
            SetValueFloat($this->CreateVariableByIdent($deviceID, 'Longitude', 'Longitude', VARIABLETYPE_FLOAT), (float)$_POST['longitude']);
        }
        if (isset($_POST['date'])) {
            SetValueInteger(
                $this->CreateVariableByIdent($deviceID, 'Timestamp', 'Timestamp', VARIABLETYPE_INTEGER, '~UnixTimestamp'),
                $_POST['date']
            );
        }
        if (isset($_POST['message'])) {
            SetValueString($this->CreateVariableByIdent($deviceID, 'Message', 'Message', VARIABLETYPE_STRING), $_POST['message']);
        }
    }

    private function ReduceGUIDToIdent(string $guid): string
    {
        return str_replace(['{', '(', ' ', '.', '-', ')', '}'], '', $guid);
    }

    private function CreateVariableByIdent(int $id, string $ident, string $name, int $type, string $profile = ''):int
    {
        $vid = @IPS_GetObjectIDByIdent($ident, $id);
        if ($vid === false) {
            $vid = IPS_CreateVariable($type);
            IPS_SetParent($vid, $id);
            IPS_SetName($vid, $name);
            IPS_SetIdent($vid, $ident);
            if ($profile !== '') {
                IPS_SetVariableCustomProfile($vid, $profile);
            }
        }
        return $vid;
    }

    private function CreateInstanceByIdent(int $id, string $ident, string $name, string $moduleid = DUMMY_MODULE_ID): int
    {
        $iid = @IPS_GetObjectIDByIdent($ident, $id);
        if ($iid === false) {
            $iid = IPS_CreateInstance($moduleid);
            IPS_SetParent($iid, $id);
            IPS_SetName($iid, $name);
            IPS_SetIdent($iid, $ident);
        }
        return $iid;
    }
}
