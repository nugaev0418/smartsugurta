<?php

namespace backend\component;
class CalculateOsagoExtractor
{
    public static function fromApiResponse(array $apiResponse): CalculateOsagoDTO
    {
        $data = GraphQLExtractor::extract(
            $apiResponse,
            'data.calculateOsagoV2',
            [
                'premium'          => 'premium.amount',
                'sum'              => 'sum.amount',
            ],
            function ($result) {

                $result['success']   = true;

                return $result;
            }
        );

        // 🔴 MUHIM: agar extract xato bo‘lsa — shu yerda qaytamiz

        if (empty($data['success'])) {
            return new CalculateOsagoDTO([
                'success' => false,
                'error'   => $data['error'] ?? 'Unknown error',
            ]);
        }

        return new CalculateOsagoDTO($data);
    }
}


