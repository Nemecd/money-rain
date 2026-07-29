<?php
/**
 * Minimal Supabase REST/Auth client for PHP.
 * No Composer / SDK needed — just native cURL, which every PHP install
 * (including shared hosting / cPanel) has enabled by default.
 *
 * Key rule baked into this class:
 *   - AUTH calls (signUp/signIn) use the ANON key. This key is meant to be
 *     public — it's the same key you'd put in frontend JS. It does NOT
 *     grant special access; Supabase's Auth service enforces its own rules.
 *   - DATABASE calls (insertRow/selectRow/updateRow) use the SERVICE ROLE
 *     key. This key bypasses Row Level Security entirely, which is why it
 *     must NEVER leave the server. Since PHP is the only thing calling
 *     these methods, and PHP owns the session, this is safe — just make
 *     sure config.php / .env is never web-accessible (see SETUP-GUIDE.md).
 */
class SupabaseClient
{
    private string $url;
    private string $anonKey;
    private string $serviceKey;

    public function __construct(string $url, string $anonKey, string $serviceKey)
    {
        $this->url = rtrim($url, '/');
        $this->anonKey = $anonKey;
        $this->serviceKey = $serviceKey;
    }

    private function request(string $method, string $path, array $headers = [], $body = null): array
    {
        $ch = curl_init($this->url . $path);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json'], $headers));

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['status' => 0, 'body' => null, 'error' => $error];
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => json_decode($response, true), 'error' => null];
    }

    // ---------------- AUTH (anon key) ----------------

    public function signUp(string $email, string $password): array
    {
        return $this->request('POST', '/auth/v1/signup', [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->anonKey,
        ], compact('email', 'password'));
    }

    public function signIn(string $email, string $password): array
    {
        return $this->request('POST', '/auth/v1/token?grant_type=password', [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->anonKey,
        ], compact('email', 'password'));
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->request('POST', '/auth/v1/token?grant_type=refresh_token', [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->anonKey,
        ], ['refresh_token' => $refreshToken]);
    }

    // ---------------- DATABASE (service role key — server-side only) ----------------

    public function insertRow(string $table, array $data): array
    {
        return $this->request('POST', '/rest/v1/' . $table, [
            'apikey: ' . $this->serviceKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Prefer: return=representation',
        ], $data);
    }

    public function selectRow(string $table, string $query): array
    {
        return $this->request('GET', '/rest/v1/' . $table . '?' . $query, [
            'apikey: ' . $this->serviceKey,
            'Authorization: Bearer ' . $this->serviceKey,
        ]);
    }

    public function updateRow(string $table, string $query, array $data): array
    {
        return $this->request('PATCH', '/rest/v1/' . $table . '?' . $query, [
            'apikey: ' . $this->serviceKey,
            'Authorization: Bearer ' . $this->serviceKey,
            'Prefer: return=representation',
        ], $data);
    }
}