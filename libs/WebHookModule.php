<?php

declare(strict_types=1);

class WebHookModule extends IPSModuleStrict
{
    private $hook;

    public function __construct(int $InstanceID, $hook)
    {
        parent::__construct($InstanceID);

        $this->hook = $hook;
    }

    public function Create(): void
    {

        //Never delete this line!
        parent::Create();

        //We need to call the RegisterHook function on Kernel READY
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {

        //Never delete this line!
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message === IPS_KERNELMESSAGE && $Data[0] === KR_READY) {
            $this->RegisterHook('/hook/' . $this->hook);
        }
    }

    public function ApplyChanges(): void
    {

        //Never delete this line!
        parent::ApplyChanges();

        //Only call this in READY state. On startup the WebHook instance might not be available yet
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->RegisterHook('/hook/' . $this->hook);
        }
    }

    protected function RegisterHook($HookPath): bool
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] === $HookPath) {
                    if ($hook['TargetID'] === $this->InstanceID) {
                        return true;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $HookPath, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
        return true;
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData(): void
    {
        $this->SendDebug('WebHook', 'Array POST: ' . print_r($_POST, true), 0);
    }
}