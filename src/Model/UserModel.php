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

        $stmt = $this->prepare('SELECT * FROM users WHERE SHA2(email, 256) = ?');
        $stmt->execute([hash(self::HASH_ALGO, $password)]);
        if (!empty($stmt->fetch())) $errors[] = 'email already used';

        $stmt = $this->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if (!empty($stmt->fetch())) $errors[] = 'username already used';

        if (!empty($errors)) return $errors;

        $code = $this->generateCode();
        $email = hash(self::HASH_ALGO, $email);
        $password = password_hash($password, PASSWORD_DEFAULT);

        // TODO confirmation email

        $stmt = $this->prepare('INSERT INTO users (code, email, username, displayname, password, last_email) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, $email, $username, $displayname, $password, date('Y-m-d H:i:s')]);

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

    public function update(int $id, string $displayname, ?string $description): void
    {
        $stmt = $this->prepare('UPDATE users SET displayname = ?, description = ? WHERE id = ?');
        $stmt->execute([$displayname, strtolower($description), $id]);
    }

    public function updateUsername(int $id, string $username): void
    {
        $stmt = $this->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$username, $id]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$password, $id]);
    }
}
