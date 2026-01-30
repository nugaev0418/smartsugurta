<?php

namespace backend\component;
class VehicleOwnerExtractor
{
    public static function fromApiResponse(array $apiResponse): VehicleOwnerDTO
    {
        $data = GraphQLExtractor::extract(
            $apiResponse,
            'data.vehicleByTechPassportAndLicensePlateV2',
            [
                'ownerTypeRaw'           => 'owner.__typename',
                'inn'                    => 'owner.inn',
                'name'                   => 'owner.name',
                'firstName'              => 'owner.firstName',
                'middleName'             => 'owner.middleName',
                'lastName'               => 'owner.lastName',
                'pinfl'                  => 'owner.pinfl',
                'birthDate'              => 'owner.birthDate',
                'useTerritoryRegionId'   => 'useTerritoryRegion.id',
                'vehicleGroupId'         => 'vehicleType.vehicleGroup.id',
            ],
            function ($result) {
                $map = [
                    'PersonV2'       => 'PERSON',
                    'OrganizationV2' => 'ORGANIZATION',
                ];

                $result['success']   = true;
                $result['ownerType'] = $map[$result['ownerTypeRaw']] ?? 'UNKNOWN';
                $result['birthDate'] = $result['birthDate'] ?? null;
                unset($result['ownerTypeRaw']);

                return $result;
            }
        );

        // 🔴 MUHIM: agar extract xato bo‘lsa — shu yerda qaytamiz

        if (empty($data['success'])) {
            return new VehicleOwnerDTO([
                'success' => false,
                'error'   => $data['error'] ?? 'Unknown error',
            ]);
        }

        return new VehicleOwnerDTO($data);
    }
}


