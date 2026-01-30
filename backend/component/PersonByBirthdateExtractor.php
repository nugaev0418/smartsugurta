<?php

namespace backend\component;
class PersonByBirthdateExtractor
{
    public static function fromApiResponse(array $apiResponse): PersonByBirthdateDTO
    {
        $data = GraphQLExtractor::extract(
            $apiResponse,
            'data.personByBirthdateV2',
            [
                'firstName'              => 'firstName',
                'middleName'             => 'middleName',
                'lastName'               => 'lastName',
                'pinfl'                  => 'pinfl',
                'birthDate'              => 'birthDate',
                'passport'               => 'passport',
                'idCard'                 => 'idCard',
                'districtId'             => 'district.id',
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
            return new PersonByBirthdateDTO([
                'success' => false,
                'error'   => $data['error'] ?? 'Unknown error',
            ]);
        }

        return new PersonByBirthdateDTO($data);
    }
}


