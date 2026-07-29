<?php
/**
 * Call requireAuth() at the top of any page that should only be
 * visible to a logged-in user (e.g. dashboard.php, once it exists).
 */
function requireAuth(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.html');
        exit;
    }

    // Access tokens from Supabase expire (default 1hr). If it's expired,
    // use the refresh token to get a new one transparently.
    global $supabase;
    if (!empty($_SESSION['expires_at']) && time() >= $_SESSION['expires_at']) {
        $refreshed = $supabase->refreshToken($_SESSION['refresh_token']);
        if ($refreshed['status'] === 200 && !empty($refreshed['body']['access_token'])) {
            $_SESSION['access_token'] = $refreshed['body']['access_token'];
            $_SESSION['refresh_token'] = $refreshed['body']['refresh_token'];
            $_SESSION['expires_at'] = time() + ($refreshed['body']['expires_in'] ?? 3600);
        } else {
            // refresh token is dead too — force re-login
            $_SESSION = [];
            session_destroy();
            header('Location: /login.html?expired=1');
            exit;
        }
    }
}

function currentUserId(): ?string
{
    return $_SESSION['user_id'] ?? null;
}