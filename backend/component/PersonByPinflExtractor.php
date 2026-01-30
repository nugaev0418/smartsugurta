<?php

namespace backend\component;
class PersonByPinflExtractor
{
    public static function fromApiResponse(array $apiResponse): PersonByPinflDTO
    {
        $data = GraphQLExtractor::extract(
            $apiResponse,
            'data.personByPinflV2',
            [
                'firstName'              => 'firstName',
                'middleName'             => 'middleName',
                'lastName'               => 'lastName',
                'pinfl'                  => 'pinfl',
                'birthDate'              => 'birthDate',
                'districtId'             => 'district.id',
                'passport'               => 'passport',
                'idCard'                 => 'idCard',
            ],
            function ($result) {

                $result['success']   = true;

                if (is_null($result['passport'])) {
                    $result['seria'] = $result['idCard']['seria'];
                    $result['number'] = $result['idCard']['number'];
                }else{
                    $result['seria'] = $result['passport']['seria'];
                    $result['number'] = $result['passport']['number'];
                }

                unset($result['passport'], $result['idCard']);

                return $result;
            }
        );

        // 🔴 MUHIM: agar extract xato bo‘lsa — shu yerda qaytamiz

        if (empty($data['success'])) {
            return new PersonByPinflDTO([
                'success' => false,
                'error'   => $data['error'] ?? 'Unknown error',
            ]);
        }

        return new PersonByPinflDTO($data);
    }
}


