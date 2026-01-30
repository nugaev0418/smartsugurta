<?php

namespace backend\component;
class PolicyByIdExtractor
{
    public static function fromApiResponse(array $apiResponse): policyByIdDTO
    {
        $data = GraphQLExtractor::extract(
            $apiResponse,
            'data.getPolicyByIdV2',
            [
                'pdfUrl'        => 'pdfUrl',
                'status'        => 'status',
                'productName'   => 'productName',
                'amount'        => 'premium.amount',
                'payments'        => 'payments',
            ],
            function ($result) {

                $result['success']   = true;
                $firstPayment = $result['payments'][0] ?? [];
                $result['paymentId'] = $firstPayment['id'] ?? null;
                $result['paymentStatus'] = $firstPayment['status'] ?? null;
                unset($result['payments']);

                return $result;
            }
        );

        // 🔴 MUHIM: agar extract xato bo‘lsa — shu yerda qaytamiz


        if (empty($data['success'])) {
            return new policyByIdDTO([
                'success' => false,
                'error'   => $data['error'] ?? 'Unknown error',
            ]);
        }


        return new policyByIdDTO($data);
    }
}


