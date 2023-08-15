<?php

/**
 * Handles making API calls to the ACA-Py agent and defines the business logic for verifying credentials.
 */
class Controller
{
    /**
     * Make a request to the ACA-Py agent's admin API based on the given url.
     * Return false if the request failed and the result of the request if it succeeded.
     * 
     * @param string $url
     * @param string $method optional method, default = "GET"
     * @param string $data optional data, default = null
     * 
     * @return mixed
     */
    function admin_request(
        string $url, 
        string $method = "GET",
        mixed $data = null
    )
    {
        $curl_array = [
            CURLOPT_URL => ControllerInit::$admin_url . $url, 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $curl_array);
        if ($method == 'POST' && $data) // Curl POST method requires data
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        
        if ($method == 'DELETE')
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        $resp = curl_exec($curl);

        $errno = curl_errno( $curl );
        if ($errno)
            return false;
        curl_close($curl);
        
        return json_decode($resp, true);
    }


    /**
     * Create the presentation request used to login to Wordpress.
     * Return false if creating the presentation request fails, otherwise return the presentation exchange id. 
     * 
     * @return bool|string
     */
    function create_pres_req() {

        $pres_req_arr = $this->build_proof_req();
        $pres_arr = $this->admin_request('/present-proof/create-request', method:"POST", data: $pres_req_arr);
        if (!$pres_arr) return false;

        return $pres_arr['presentation_exchange_id'];
    }


    /**
     * Create a presentation request array for webhooks URL call from the mobile agent.
     * Return false if any ACA-Py agent admin API requests fail, otherwise return the presentation request array.
     * 
     * @param string $pres_ex_id
     * @return bool|array
     */
    function wh_pres_req($pres_ex_id) {

        $pres_arr = $this->admin_request('/present-proof/records/' . $pres_ex_id);
        if (!$pres_arr) return false;
        $pres_req_arr = $pres_arr['presentation_request_dict'];

        $did_arr = $this->admin_request('/wallet/did/public');
        if (!$did_arr) return false;
        $did = $did_arr["result"]["did"];
        $verkey = $did_arr["result"]["verkey"];

        $did_ep_arr = $this->admin_request('/wallet/get-did-endpoint?did=' . $did);
        if (!$did_ep_arr) return false;
        $did_ep = $did_ep_arr['endpoint'];

        $pres_req_arr['~service'] = [
            'recipientKeys' => [$verkey],
            'serviceEndpoint' => $did_ep
        ];

        return $pres_req_arr;

    }


    /**
     * Verify a presentation based on presentation exchange id.
     * Return true on success verifying, false on failure.
     * 
     * @param string $pres_ex_id
     * @return bool
     */
    function verify_pres($pres_ex_id) {

        $status = $this->admin_request("/present-proof/records/" . $pres_ex_id . "/verify-presentation", method:'POST', data:[$pres_ex_id]);
        if (!$status) return false;
        return true;
    }


    /**
     * Get a presentation exchange record.
     * Return false if any ACA-Py agent admin API requests fail, otherwise return the presentation record.
     * @param string $pres_ex_id
     * @return bool|array
     */
    function get_record($pres_ex_id) {
        $pres_arr = $this->admin_request('/present-proof/records/' . $pres_ex_id);
    
        if (!$pres_arr) return false;
        return $pres_arr;
    }


    /**
     * Set the ACA-Py agent's DID endpoint.
     * Return false on failure, true on success.
     * 
     * @return bool
     */
    function set_did_ep()
    {
        $resp = $this->admin_request('/wallet/did/public');
        if (!$resp) return false;

        $did = $resp['result']['did'];
        $did_arr = [
            'did' => $did,
            'endpoint' => ControllerInit::$agent_url,
            'endpoint_type' => 'Endpoint'
        ];

        $resp = $this->admin_request('/wallet/set-did-endpoint', method:'POST', data:$did_arr);
        if (!$resp) return false;

        return true;
    }


    /**
     * Delete presentation based on presentation exchange id
     * Return false if deleting fails
     * @param string $pres_ex_id
     * @return mixed
     */
    function delete_pres($pres_ex_id) {
        $resp = $this->admin_request('/present-proof/records/' . $pres_ex_id, method: 'DELETE');
        if (!$resp) return false;

        return $resp;
    }


    /**
     * Proof request array
     * @return array
     */
    function build_proof_req() {

        $data = [
            "proof_request" => [
                "name" => "Proof of Education",
                "version" => "1.0",
                "requested_attributes"=> [
                    "0_name_uuid"=> [
                        "name"=> "name",
                        "restrictions"=> [
                            [
                                "issuer_did"=> "VjJLSSJoRZgWzBC9KSEDRj",
                                "cred_def_id"=>"VjJLSSJoRZgWzBC9KSEDRj:3:CL:18547:faber.agent.degree_schema"
                            ]
                        ]
                    ],
                    "0_date_uuid"=> [
                        "name"=> "date",
                        "restrictions"=> [
                            [
                                "issuer_did"=> "VjJLSSJoRZgWzBC9KSEDRj",
                                "cred_def_id"=>"VjJLSSJoRZgWzBC9KSEDRj:3:CL:18547:faber.agent.degree_schema"
                            ]
                        ]
                    ],
                    "0_degree_uuid"=> [
                        "name"=> "degree",
                        "restrictions"=> [
                            [
                                "issuer_did"=> "VjJLSSJoRZgWzBC9KSEDRj",
                                "cred_def_id"=>"VjJLSSJoRZgWzBC9KSEDRj:3:CL:18547:faber.agent.degree_schema"
                            ]
                        ],
                        "non_revoked"=> [
                            "to"=> 1687802616
                        ]
                    ]
                        ],
                "requested_predicates"=> [
                    "0_birthdate_dateint_GE_uuid"=> [
                        "name"=> "birthdate_dateint",
                        "p_type"=> "<=",
                        "p_value"=> 20050626,
                        "restrictions"=> [
                            [
                                "issuer_did"=> "VjJLSSJoRZgWzBC9KSEDRj",
                                "cred_def_id"=>"VjJLSSJoRZgWzBC9KSEDRj:3:CL:18547:faber.agent.degree_schema"
                            ]
                        ]
                    ]
                ],
                "non_revoked"=> [
                    "to"=> 1687802617
                ]
            ],
            "trace"=> False,
            "connection_id"=> "b405cb55-7d6b-4432-bfe0-3cfc9edbc005"
        ];
        
        return $data;
    }
}

/**
 * Class for initializing the controller's static variables and setting the ACA-Py agent's DID endpoint.
 * Initializing the controller's urls and setting the DID endpoint should only happen once, so initialization is static.
 */
class ControllerInit {

    /**
     * ACA-Py agent's admin API
     */
    public static string $admin_url = 'http://ec2-107-21-44-71.compute-1.amazonaws.com:8031';

    /**
     * This plugin's url
     */
    public static string $webhook_url = 'http://ec2-107-21-44-71.compute-1.amazonaws.com:8888/wp-json/vc-api';

    /**
     * ACA-Py agent's endpoint
     */
    public static string $agent_url = 'http://ec2-107-21-44-71.compute-1.amazonaws.com:8030';

    /**
     * Sets the ACA-Py agent's DID endpoint
     */
    static function init() {
        $resp = self::admin_request('/wallet/did/public');
        if (!$resp) return false;

        $did = $resp['result']['did'];
        $did_arr = [
            'did' => $did,
            'endpoint' => self::$agent_url,
            'endpoint_type' => 'Endpoint'
        ];

        $resp = self::admin_request('/wallet/set-did-endpoint', method:'POST', data:$did_arr);
        if (!$resp) return false;

        return true;
    }

    /**
     * Static version of Controller->admin_request for making requests to the ACA-Py agent's admin API
     */
    static function admin_request(
        string $url, 
        string $method = "GET",
        mixed $data = null
    )
    {
        $curl_array = [
            CURLOPT_URL => self::$admin_url . $url, 
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, $curl_array);
        if ($method == 'POST' && $data) // Curl POST method requires data
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        
        if ($method == 'DELETE')
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        $resp = curl_exec($curl);
        $errno = curl_errno( $curl );
        if ($errno)
            return false;

        curl_close($curl);
        
        return json_decode($resp, true);
    }
}