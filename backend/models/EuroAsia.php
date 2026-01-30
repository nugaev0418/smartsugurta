<?php
namespace backend\models;
class EuroAsia
{
    const
        ORDER_CHANNEL_ID = '-1003782162980',
        GATEWAY_CLICK = 'CLICK',
        GATEWAY_PAYME = 'PAYME';

    private function request($data)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://erp.eai.uz/query',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'accept: */*',
                'accept-language: uz',
                'authorization: ',
                'content-type: application/json',
                'origin: https://eai.uz',
                'priority: u=1, i',
                'referer: https://eai.uz/',
                'sec-ch-ua: "Chromium";v="142", "YaBrowser";v="25.12", "Not_A Brand";v="99", "Yowser";v="2.5"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: empty',
                'sec-fetch-mode: cors',
                'sec-fetch-site: same-site',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 YaBrowser/25.12.0.0 Safari/537.36',
                'x-app-name: Website',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    public function getVehicleByTechPassportAndLicensePlate($techPassportSeries, $techPassportNumber, $licensePlateNumber)
    {
        $query = 'query getVehicleByTechPassportAndLicensePlateV2($input: VehicleByTechPassportAndLicensePlateInputV2!) {
  vehicleByTechPassportAndLicensePlateV2(input: $input) {
    owner {
      ... on PersonV2 {
        firstName
        middleName
        lastName
        pinfl
        birthDate
        address
        district {
          id
          name
          __typename
        }
        region {
          id
          name
          __typename
        }
        __typename
      }
      ... on OrganizationV2 {
        inn
        name
        address
        __typename
      }
      __typename
    }
    techPassport {
      issuedAt
      series
      number
      __typename
    }
    useTerritoryRegion {
      id
      name
      __typename
    }
    licensePlate
    model
    engineNumber
    bodyNumber
    color
    seatCount
    vehicleType {
      id
      name
      vehicleGroup {
        id
        name
        __typename
      }
      __typename
    }
    __typename
  }
}';

        $variables = [
            'input' => [
                'techPassportSeries' => $techPassportSeries,
                'techPassportNumber' => $techPassportNumber,
                'licensePlateNumber' => $licensePlateNumber,
            ],
        ];

        $data = [
            'query' => $query,
            'variables' => $variables
        ];

        return $this->request($data);
    }

    public function CalculateOsagoV2($driverRestriction, $drivers, $useTerritoryRegionId, $vehicleGroupId, $seasonalInsuranceId)
    {
        $query = 'query CalculateOsagoV2($input: CalculateOsagoInputV2!) {
  calculateOsagoV2(input: $input) {
    premium {
      amount
      currency
      __typename
    }
    sum {
      amount
      currency
      __typename
    }
    __typename
  }
}';

        $variables = [
            'input' => [
                "driverRestriction" => $driverRestriction,
                "drivers" => $drivers,
                "useTerritoryRegionId" => $useTerritoryRegionId,
                "vehicleGroupId" => $vehicleGroupId,
                "seasonalInsuranceId" => $seasonalInsuranceId
            ],
        ];

        $data = [
            'query' => $query,
            'variables' => $variables
        ];

        return $this->request($data);
    }

    public function getPersonByPinflV2($pinfl, $number, $seria)
    {
        $query = 'query getPersonByPinflV2($input: PersonByPinflInputV2!) {
  personByPinflV2(input: $input) {
    firstName
    lastName
    middleName
    passport {
      seria
      number
      __typename
    }
    idCard {
      seria
      number
      __typename
    }
    driverLicense {
      seria
      number
      __typename
    }
    pinfl
    birthDate
    region {
      id
      __typename
    }
    district {
      id
      __typename
    }
    __typename
  }
}';

        $variables = [
            'input' => [
                'passport' => [
                    'number' => $number,
                    'seria' => $seria,
                ],
                'pinfl' => $pinfl,
            ],
        ];

        $data = [
            'query' => $query,
            'variables' => $variables
        ];

        return $this->request($data);
    }

    public function getPersonByBirthdate($birthdate, $number, $seria)
    {
        $query = 'query getPersonByBirthdateV2($input: PersonByBirthdateInputV2!) {
                      personByBirthdateV2(input: $input) {
                        firstName
                        lastName
                        middleName
                        passport {
                          seria
                          number
                          __typename
                        }
                        idCard {
                          seria
                          number
                          __typename
                        }
                        driverLicense {
                          seria
                          number
                          __typename
                        }
                        pinfl
                        birthDate
                        region {
                          id
                          __typename
                        }
                        district {
                          id
                          __typename
                        }
                        __typename
                      }
                    }';

        $variables = [
            'input' => [
                'passport' => [
                    'number' => $number,
                    'seria' => $seria,
                ],
                'birthdate' => $birthdate,
            ],
        ];

        $data = [
            'query' => $query,
            'variables' => $variables
        ];

        return $this->request($data);
    }

    public function RelativesV2()
    {
        $query = 'query RelativesV2 {
  relativesV2 {
    id
    name
    __typename
  }
}';

        $data = [
            'query' => $query,
            'variables' => new stdClass()
        ];

        $this->request($data);
    }

    public function CreateOsagoV2($data)
    {
        $query = 'mutation CreateOsagoV2($input: CreateOsagoInputV2!) {
              createOsagoV2(input: $input) {
                contractId
                policyId
                paymentId
                paymentLink
                __typename
              }
            }';

        $variables = [
            'input' => [
                "vehicle" => $data['vehicle'],
                'owner' => $data['owner'],
                'insurant' => $data['insurant'],
                'details' => [
                    'drivers' => $data['drivers'],
                    "billingGateway" => $data['billingGateway'],
                    "driverRestriction" => $data['driverRestriction'],
                    "seasonalInsuranceId" => $data['seasonalInsuranceId'],
                    "startAt" => $data['startAt'],
                    "promocode" => "LUQCK",
                    "source" => "WEBSITE"
                ]
            ],
        ];



        $data = [
            'query' => $query,
            'variables' => $variables
        ];


//        return '{"data":{"createOsagoV2":{"contractId":"39db7471-fa97-4e15-8988-756be3bf3c79","policyId":"a6b1073f-d818-4d7b-932b-546865e962e7","paymentId":"0c69bc89-aa36-4a84-a150-ea04f24c1ece","paymentLink":"https://checkout.paycom.uz/Y3I9VVpTO2M9aHR0cHM6Ly9lcnAuZWFpLnV6L3BvcnRmb2xpby9jb250cmFjdHMvcG9saWN5L2E2YjEwNzNmLWQ4MTgtNGQ3Yi05MzJiLTU0Njg2NWU5NjJlNy9wZGY7bT02ODJhY2M2YzJjM2RkOGQ4MGJhMTFlYTY7YWMub3JkZXJfaWQ9cG9saWN5OmE2YjEwNzNmLWQ4MTgtNGQ3Yi05MzJiLTU0Njg2NWU5NjJlNzthPTE2MDAwMDAw","__typename":"CreateOsagoOutputV2"}}}';


        return $this->request($data);

    }

    public function policyByIdV2($id)
    {
        $query = 'query policyByIdV2($id: UUID!) {
                  getPolicyByIdV2(id: $id) {
                    id
                    startAt
                    endAt
                    pdfUrl
                    status
                    productName
                    payments {
                      id
                      gateway
                      status
                      paymentUrl
                      __typename
                    }
                    premium {
                      amount
                      __typename
                    }
                    __typename
                  }
                }';

        $variables = [
            'id' => $id,
        ];

        $data = [
            'query' => $query,
            'variables' => $variables
        ];


        return '{
    "data": {
        "getPolicyByIdV2": {
            "id": "7b739c2e-54ff-418e-b6a3-e452c39ac56c",
            "startAt": "2026-05-05T00:00:00Z",
            "endAt": "2026-05-25T00:00:00Z",
            "pdfUrl": "https://erp.eai.uz/portfolio/contracts/policy/7b739c2e-54ff-418e-b6a3-e452c39ac56c/pdf",
            "status": "ACTIVE",
            "productName": "OSAGO",
            "payments": [
                {
                    "id": "effd6a41-f152-4284-b4e2-1476fa477860",
                    "gateway": "PAYME",
                    "status": "COMPLETED",
                    "paymentUrl": "https://checkout.paycom.uz/YT0zMjAwMDAwO2NyPVVaUztjPWh0dHBzOi8vZXJwLmVhaS51ei9wb3J0Zm9saW8vY29udHJhY3RzL3BvbGljeS83YjczOWMyZS01NGZmLTQxOGUtYjZhMy1lNDUyYzM5YWM1NmMvcGRmO209NjgyYWNjNmMyYzNkZDhkODBiYTExZWE2O2FjLm9yZGVyX2lkPXBvbGljeTo3YjczOWMyZS01NGZmLTQxOGUtYjZhMy1lNDUyYzM5YWM1NmM=",
                    "__typename": "PaymentInfoV2"
                }
            ],
            "premium": {
                "amount": 3.2e+06,
                "__typename": "Money"
            },
            "__typename": "PolicyInfoV2"
        }
    }
}';
//
//        return '{
//    "data": {
//        "getPolicyByIdV2": {
//            "id": "7b739c2e-54ff-418e-b6a3-e452c39ac56c",
//            "startAt": "2026-05-05T16:54:15Z",
//            "endAt": "2026-05-25T16:54:15Z",
//            "pdfUrl": "https://erp.eai.uz/portfolio/contracts/policy/7b739c2e-54ff-418e-b6a3-e452c39ac56c/pdf",
//            "status": "NO_MOVEMENT",
//            "productName": "OSAGO",
//            "payments": [
//                {
//                    "id": "effd6a41-f152-4284-b4e2-1476fa477860",
//                    "gateway": "PAYME",
//                    "status": "PENDING",
//                    "paymentUrl": "https://checkout.paycom.uz/YT0zMjAwMDAwO2NyPVVaUztjPWh0dHBzOi8vZXJwLmVhaS51ei9wb3J0Zm9saW8vY29udHJhY3RzL3BvbGljeS83YjczOWMyZS01NGZmLTQxOGUtYjZhMy1lNDUyYzM5YWM1NmMvcGRmO209NjgyYWNjNmMyYzNkZDhkODBiYTExZWE2O2FjLm9yZGVyX2lkPXBvbGljeTo3YjczOWMyZS01NGZmLTQxOGUtYjZhMy1lNDUyYzM5YWM1NmM=",
//                    "__typename": "PaymentInfoV2"
//                }
//            ],
//            "premium": {
//                "amount": 3.2e+06,
//                "__typename": "Money"
//            },
//            "__typename": "PolicyInfoV2"
//        }
//    }
//}';

        return $this->request($data);
    }

    public static function download($id)
    {
        $pdfUrl = "https://erp.eai.uz/portfolio/contracts/policy/$id/pdf";
        $savePath = 'policeFiles/' . $id . '.pdf';

        $fp = fopen($savePath, 'w+');

        $ch = curl_init($pdfUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            echo "Xatolik: " . curl_error($ch);
        }

        curl_close($ch);
        fclose($fp);

        return true;
    }
}
