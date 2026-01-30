<?php

namespace backend\component;
class CreateOsagoExtractor
{
    public static function fromApiResponse(array $apiResponse): CreateOsagoDTO
    {
        $data = GraphQLExtractor::extract(
            $apiResponse,
            'data.createOsagoV2',
            [
                'contractId'                => 'contractId',
                'policyId'                  => 'policyId',
                'paymentId'                 => 'paymentId',
                'paymentLink'               => 'paymentLink',
            ],
            function ($result) {

                $result['success']   = true;
                return $result;
            }
        );

        // 🔴 MUHIM: agar extract xato bo‘lsa — shu yerda qaytamiz

        if (empty($data['success'])) {
            return new CreateOsagoDTO([
                'success' => false,
                'error'   => $data['error'] ?? 'Unknown error',
            ]);
        }

        return new CreateOsagoDTO($data);
    }
}


