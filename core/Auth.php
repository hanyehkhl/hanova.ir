<?php
class Auth {
    private $db;
    private static $instance = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isLoggedIn() {
        Session::start();
        return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getUser() {
    Session::start();
    if ($this->isLoggedIn()) {
        return [
            'id'           => (int)$_SESSION['user_id'],
            'username'     => $_SESSION['username'],
            'display_name' => $_SESSION['display_name'],
            'is_admin'     => $_SESSION['is_admin'] ?? false
        ];
    }
    return null;
}



    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, display_name, password, is_active FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است'];
        }
        if ((int)$user['is_active'] !== 1) {
            return ['success' => false, 'message' => 'حساب کاربری غیرفعال است'];
        }
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است'];
        }

        $isAdmin = ($username === 'admin');

        Session::start();
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['is_admin'] = $isAdmin;
        $_SESSION['logged_in'] = true;

        return [
            'success' => true,
            'message' => 'ورود موفقیت‌آمیز',
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'is_admin' => $isAdmin
            ]
        ];
    }

    public function check() {
        Session::start();
        if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return [
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'display_name' => $_SESSION['display_name'],
                    'is_admin' => $_SESSION['is_admin'] ?? false
                ]
            ];
        }
        return ['success' => true, 'logged_in' => false];
    }

    public function logout() {
        Session::destroy();
        return ['success' => true, 'message' => 'خروج موفقیت‌آمیز'];
    }
}
