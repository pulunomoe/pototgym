<?php

namespace Com\Pulunomoe\PototGym\Model;

use Com\Pulunomoe\PototGym\Model\Model;
use PDO;

class UserModel extends Model
{
    const HASH_ALGO = 'sha256';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function login(string $email, string $password): array|string
    {
        $stmt = $this->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (empty($user) || !password_verify($password, $user['password'])) {
            return 'uh oh';
        }

        unset($user['email']);
        unset($user['username']);
        unset($user['password']);

        return $user;
    }

    public function register(?string $email, ?string $username, ?string $displayname, ?string $password, ?string $confirm): array|string
    {
        $errors = [];

        if (empty($email)) $errors[] = 'email is required';
        if (empty($username)) $errors[] = 'username is required';
        if (empty($displayname)) $errors[] = 'display name is required';
        if (empty($password)) $errors[] = 'password is required';
        if (empty($confirm)) $errors[] = 'password confirmation is required';
        if (!password_verify($password, password_hash($confirm, PASSWORD_DEFAULT))) $errors = 'password confirmation does not match';

        $emailHash = hash(self::HASH_ALGO, $email);

        $stmt = $this->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$emailHash]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'email already used';

        $stmt = $this->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'username already used';

        if (!empty($errors)) return $errors;

        $code = $this->generateCode();
        $password = password_hash($password, PASSWORD_DEFAULT);

        // TODO confirmation email

        $stmt = $this->prepare('INSERT INTO users (code, email, username, displayname, password, last_email) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, $emailHash, $username, $displayname, $password, date('Y-m-d H:i:s')]);

        return $code;
    }

    public function confirm(?string $code): bool
    {
        if (empty($code)) return false;

        $stmt = $this->prepare('SELECT * FROM users WHERE code = ?');
        $stmt->execute([$code]);
        $user = $stmt->fetch();
        if (empty($user)) return false;

        $stmt = $this->prepare('UPDATE users SET joindate = ? WHERE id = ?');
        $stmt->execute([date('Y-m-d'), $user['id']]);

        return true;
    }

    public function updateProfile(int $id, string $displayname, ?string $description): ?array
    {
        $errors = [];
        if (empty($displayname)) $errors[] = 'display name is required';
        if (!empty($errors)) return $errors;

        $stmt = $this->prepare('UPDATE users SET displayname = ?, description = ? WHERE id = ?');
        $stmt->execute([$displayname, strtolower($description), $id]);

        $_SESSION['user']['displayname'] = $displayname;

        return null;
    }

    public function updateEmail(int $id, string $email): ?array
    {
        $errors = [];

        if (empty($email)) $errors[] = 'email is required';
        $emailHash = hash(self::HASH_ALGO, $email);

        $stmt = $this->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$emailHash, $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'email already used';

        if (!empty($errors)) return $errors;

        $stmt = $this->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->execute([$emailHash, $id]);

        return null;
    }

    public function updateUsername(int $id, string $username): ?array
    {
        $errors = [];

        if (empty($username)) $errors[] = 'username is required';

        $stmt = $this->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$username, $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'username already used';

        if (!empty($errors)) return $errors;

        $stmt = $this->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$username, $id]);

        return null;
    }

    public function updatePassword(int $id, string $password, string $confirm): ?array
    {
        $errors = [];

        if (empty($password)) $errors[] = 'password is required';
        if (empty($confirm)) $errors[] = 'password confirmation is required';
        if (!password_verify($password, password_hash($confirm, PASSWORD_DEFAULT))) $errors = 'password confirmation does not match';

        if (!empty($errors)) return $errors;

        $password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$password, $id]);

        return null;
    }

    public function forgotUsername(?string $email): bool|string
    {
        if (empty($email)) return false;

        $email = hash(self::HASH_ALGO, $email);

        $stmt = $this->prepare('SELECT username FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $username = $stmt->fetchColumn();

        return $username;
    }

    public function forgotPassword(?string $email): bool
    {
        if (empty($email)) return false;

        $emailHash = hash(self::HASH_ALGO, $email);

        $stmt = $this->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$emailHash]);
        $user = $stmt->fetch();
        if (empty($user)) return false;

        // TODO new password email

        return true;
    }
}
