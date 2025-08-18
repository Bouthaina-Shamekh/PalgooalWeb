<?php
namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ProvisioningService;

class HostingController extends Controller
{
    protected ProvisioningService $provisioning;

    public function __construct(ProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    public function createAccount()
    {
        // غير القيم التالية حسب الحاجة:
        $username = 'm21253';
        $domain = 'mak12.local';  // دومين وهمي
        $password = 'TestPass@12345';
        $plan = '';  // بدون باكج
        $quota = 512;    // نصف جيجا
        $bwlimit = 1024; // 1 جيجا
        $contactEmail = 'test@example.com';

        $result = $this->provisioning->createAccount(
            $domain,
            $username,
            $password,
            $plan,
            $quota,
            $bwlimit,
            $contactEmail
        );

        if ($result['metadata']['result'] === 1) {
            $accountData = [
                'username' => $result['metadata']['output']['raw'] ?? null, // أو يمكن تحليله لو بغيت
                'domain' => $result['data']['domain'] ?? null,
                'ip' => $result['data']['ip'] ?? null,
                'package' => $result['data']['package'] ?? null,
                'status' => $result['metadata']['result'] ?? 0,
                'message' => $result['metadata']['reason'] ?? '',
            ];
        }else{
            return response()->json([
                'status' => $result['metadata']['result'] ?? 0,
                'message' => $result['metadata']['reason'] ?? '',
            ]);
        }



        // اعرض النتيجة لتشوف الرد من WHM API
        return response()->json([
            'result' => $result,
            'accountData' => $accountData,
        ]);
    }
}
