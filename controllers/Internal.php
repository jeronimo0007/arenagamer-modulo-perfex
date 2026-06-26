<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Endpoints internos server-to-server (API Java -> Perfex).
 *
 * Não exige login de staff/cliente: estende App_Controller e valida um segredo
 * compartilhado (option `arenagamer_internal_secret`) enviado no header
 * `X-Arena-Internal-Secret`. Usado para garantir que TODA compra de créditos
 * passe pelo Perfex (geração de fatura), inclusive quando iniciada fora do
 * Perfex (ex.: app/integrações via API Java).
 */
class Internal extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('arenagamer/arenagamer');
    }

    /**
     * Cria (ou reaproveita) uma fatura Perfex de compra de créditos.
     *
     * Entrada (JSON): { clientUserId:int, contactId:int, credits:float }
     * Saída (JSON): { success, invoice_id, credits_amount, amount, url, pending }
     */
    public function credit_invoice()
    {
        if (strtoupper((string) $this->input->method(true)) !== 'POST') {
            return $this->respond(405, ['success' => false, 'message' => 'Método não permitido.']);
        }

        if (!$this->authorize_internal()) {
            return $this->respond(401, ['success' => false, 'message' => 'Não autorizado.']);
        }

        $payload = $this->json_input();
        $clientUserId = (int) ($payload['clientUserId'] ?? 0);
        $contactId    = (int) ($payload['contactId'] ?? 0);
        $credits      = round((float) ($payload['credits'] ?? 0), 2);

        if ($clientUserId <= 0) {
            return $this->respond(422, ['success' => false, 'message' => 'Cliente inválido.']);
        }

        if ($credits <= 0) {
            return $this->respond(422, ['success' => false, 'message' => 'Informe uma quantidade válida de créditos.']);
        }

        $this->load->model('arenagamer/arenagamer_credit_invoices_model');

        // Evita faturas pendentes duplicadas: reaproveita a fatura em aberto.
        $pending = $this->arenagamer_credit_invoices_model->resolve_pending_for_client($clientUserId);
        if ($pending) {
            $invoiceId = (int) ($pending['invoice_id'] ?? 0);

            return $this->respond(200, [
                'success'        => true,
                'invoice_id'     => $invoiceId,
                'credits_amount' => (float) ($pending['credits_amount'] ?? 0),
                'amount'         => (float) ($pending['amount'] ?? 0),
                'url'            => arenagamer_invoice_payment_url($invoiceId),
                'pending'        => true,
            ]);
        }

        $result = $this->arenagamer_credit_invoices_model->create_credit_invoice(
            $clientUserId,
            $contactId,
            $credits
        );

        if (empty($result['success'])) {
            return $this->respond(422, [
                'success' => false,
                'message' => $result['message'] ?? 'Não foi possível gerar a fatura.',
            ]);
        }

        return $this->respond(200, [
            'success'        => true,
            'invoice_id'     => (int) $result['invoice_id'],
            'credits_amount' => (float) $result['credits_amount'],
            'amount'         => (float) $result['amount'],
            'url'            => $result['url'],
            'pending'        => false,
        ]);
    }

    /**
     * Compara o segredo compartilhado em tempo constante.
     */
    private function authorize_internal()
    {
        $secret = (string) get_option('arenagamer_internal_secret');
        if ($secret === '') {
            return false;
        }

        $provided = (string) ($this->input->get_request_header('X-Arena-Internal-Secret') ?? '');
        if ($provided === '') {
            return false;
        }

        return hash_equals($secret, $provided);
    }

    /**
     * Lê o corpo JSON (com fallback para POST tradicional).
     */
    private function json_input()
    {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return $this->input->post() ?: [];
    }

    private function respond($status, array $body)
    {
        $this->output
            ->set_status_header((int) $status)
            ->set_content_type('application/json')
            ->set_output(json_encode($body));
    }
}
