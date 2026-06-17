<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ArenaGamer API Client Library
 * Handles all HTTP communication with the ArenaGamer REST API
 */
class ArenaGamer_api
{
    private $CI;
    private $api_url;
    private $token;
    private $last_error;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->api_url = rtrim(get_option('arenagamer_api_url'), '/');
        $this->token = get_option('arenagamer_api_token');
    }

    /**
     * Authenticate with the ArenaGamer API and store token
     */
    public function authenticate()
    {
        $email = get_option('arenagamer_admin_email');
        $password = get_option('arenagamer_admin_password');

        if (empty($email) || empty($password)) {
            $this->last_error = 'Credenciais da API não configuradas';
            return false;
        }

        $response = $this->request('POST', '/auth/login', [
            'email'    => $email,
            'password' => $password,
        ], false);

        if ($response && isset($response['data']['accessToken'])) {
            $this->token = $response['data']['accessToken'];
            update_option('arenagamer_api_token', $this->token);
            return true;
        }

        return false;
    }

    // --- Plans ---
    public function get_plans()
    {
        return $this->request('GET', '/admin/plans');
    }

    // --- Credit Tiers ---
    public function get_credit_tiers()
    {
        return $this->request('GET', '/admin/credit-tiers');
    }

    // --- Presets ---
    public function get_presets()
    {
        return $this->request('GET', '/admin/presets');
    }

    // --- Users ---
    public function get_users($page = 0, $size = 20)
    {
        return $this->request('GET', "/admin/users?page={$page}&size={$size}");
    }

    // --- Tournaments ---
    public function get_tournaments($page = 0, $size = 20)
    {
        return $this->request('GET', "/admin/tournaments?page={$page}&size={$size}");
    }

    public function get_tournament($slug)
    {
        return $this->request('GET', "/tournaments/{$slug}");
    }

    public function get_tournament_matches($slug)
    {
        return $this->request('GET', "/tournaments/{$slug}/matches");
    }

    public function get_tournament_participants($slug)
    {
        // Uses the public endpoint for participant listing
        return $this->request('GET', "/tournaments/{$slug}");
    }

    public function update_tournament_status($slug, $status)
    {
        return $this->request('PUT', "/tournaments/{$slug}/status?status={$status}");
    }

    public function generate_bracket($slug)
    {
        return $this->request('POST', "/tournaments/{$slug}/generate-bracket");
    }

    public function schedule_matches($slug)
    {
        return $this->request('POST', "/tournaments/{$slug}/schedule");
    }

    public function cancel_tournament($slug)
    {
        return $this->request('DELETE', "/tournaments/{$slug}");
    }

    // --- Audits ---
    public function get_audits($page = 0, $size = 50)
    {
        return $this->request('GET', "/admin/audits?page={$page}&size={$size}");
    }

    // --- Wallet (admin view) ---
    public function get_wallet_balance()
    {
        return $this->request('GET', '/wallet/balance');
    }

    /**
     * Make an HTTP request to the ArenaGamer API
     */
    private function request($method, $endpoint, $data = null, $auth = true)
    {
        $url = $this->api_url . $endpoint;

        $headers = ['Content-Type: application/json'];
        if ($auth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->last_error = 'Erro de conexão: ' . $curl_error;
            log_activity('ArenaGamer API Error: ' . $curl_error);
            return null;
        }

        $decoded = json_decode($response, true);

        // Handle 401 - try re-auth
        if ($http_code === 401 && $auth) {
            if ($this->authenticate()) {
                return $this->request($method, $endpoint, $data, true);
            }
        }

        if ($http_code >= 400) {
            $this->last_error = isset($decoded['message']) ? $decoded['message'] : "HTTP {$http_code}";
            return null;
        }

        return $decoded;
    }

    /**
     * Get last error message
     */
    public function get_last_error()
    {
        return $this->last_error;
    }

    /**
     * Test API connection
     */
    public function test_connection()
    {
        $result = $this->authenticate();
        return $result ? ['success' => true, 'message' => 'Conexão estabelecida com sucesso'] :
            ['success' => false, 'message' => $this->get_last_error()];
    }
}
