<?php

class APIClient {
    private $baseUrl;
    private $apiKey;

    public function __construct() {
        // TODO: Set your actual API base URL and API key here
        $this->baseUrl = 'https://national-gov-api.example.com';
        $this->apiKey = 'YOUR_API_KEY_HERE';
    }

    private function callAPI($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200) {
            throw new Exception("API request failed with status code " . $httpcode);
        }

        return json_decode($response, true);
    }

    public function getGovernmentServices() {
        // If baseUrl or apiKey placeholders are present, return mock data for development/testing
        if ($this->baseUrl === 'https://national-gov-api.example.com' || $this->apiKey === 'YOUR_API_KEY_HERE') {
            return [
                ['id' => 'electricity', 'name' => 'Electricity Bill'],
                ['id' => 'water', 'name' => 'Water Bill'],
                ['id' => 'tax', 'name' => 'Tax Payment'],
                ['id' => 'license', 'name' => 'License Fee'],
                ['id' => 'land_revenue', 'name' => 'Land Revenue'],
                ['id' => 'vehicle_registration', 'name' => 'Vehicle Registration'],
                ['id' => 'passport', 'name' => 'Passport Fee'],
                ['id' => 'education', 'name' => 'Education Fee'],
                ['id' => 'health_insurance', 'name' => 'Health Insurance'],
                ['id' => 'court_fee', 'name' => 'Court Fee'],
                ['id' => 'municipal_tax', 'name' => 'Municipal Tax'],
                ['id' => 'internet', 'name' => 'Internet'],
                ['id' => 'other', 'name' => 'Other']
            ];
        }
        // Call the real API if config is set
        return $this->callAPI('/services');
    }

    public function getGovernmentServiceDetails($serviceId) {
        // TODO: Update endpoint path to fetch government service detail by ID
        return $this->callAPI("/services/{$serviceId}");
    }
}
?>
